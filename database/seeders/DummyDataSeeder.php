<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Reset tables (keep company profile)
        DB::statement('PRAGMA foreign_keys = OFF');
        foreach (['payments', 'sales', 'lots', 'projects', 'buyers', 'marketers'] as $table) {
            DB::table($table)->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
        }
        DB::statement('PRAGMA foreign_keys = ON');

        $jsonPath = storage_path('app/demo-seed.json');
        if (!file_exists($jsonPath)) {
            $jsonPath = public_path('assets/data-dummy.json');
        }
        if (!file_exists($jsonPath)) {
            $this->command->warn("File not found: {$jsonPath}");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            $this->command->error('Failed to decode JSON file');
            return;
        }

        $columns = [
            'projects' => array_flip(Schema::getColumnListing('projects')),
            'lots' => array_flip(Schema::getColumnListing('lots')),
            'buyers' => array_flip(Schema::getColumnListing('buyers')),
            'marketers' => array_flip(Schema::getColumnListing('marketers')),
            'sales' => array_flip(Schema::getColumnListing('sales')),
            'payments' => array_flip(Schema::getColumnListing('payments')),
        ];

        $now = Carbon::now()->toDateTimeString();

        $toDate = function ($value): ?string {
            if (!$value) {
                return null;
            }
            // Normalize to YYYY-MM-DD for SQLite date columns
            return substr((string) $value, 0, 10);
        };

        $filterRow = function (array $row, array $cols) use ($now): array {
            if (isset($cols['created_at']) && !isset($row['created_at'])) {
                $row['created_at'] = $now;
            }
            if (isset($cols['updated_at']) && !isset($row['updated_at'])) {
                $row['updated_at'] = $now;
            }
            return array_intersect_key($row, $cols);
        };

        $chunkInsert = function (string $table, array $rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        };

        // Projects
        $projectRows = [];
        foreach ($data['projects'] ?? [] as $p) {
            if (!isset($p['id'])) {
                continue;
            }
            $payload = [
                'id' => $p['id'],
                'name' => $p['name'] ?? 'Unnamed Project',
                'location' => $p['location'] ?? null,
                'notes' => $p['description'] ?? ($p['notes'] ?? null),
                'total_units' => 0,
                'sold_units' => 0,
            ];
            $projectRows[] = $filterRow($payload, $columns['projects']);
        }
        if ($projectRows) {
            $chunkInsert('projects', $projectRows);
        }

        // Lots
        $lotRows = [];
        foreach ($data['lots'] ?? [] as $lot) {
            if (!isset($lot['id'])) {
                continue;
            }
            $blockNumber = $lot['block_number'] ?? (isset($lot['block']) ? ($lot['block'] . '-' . ($lot['lot_number'] ?? '')) : null);
            $payload = [
                'id' => $lot['id'],
                'project_id' => $lot['project_id'] ?? null,
                'block_number' => $blockNumber ?: 'LOT',
                'area' => $lot['area'] ?? null,
                'base_price' => $lot['base_price'] ?? 0,
                'status' => $lot['status'] ?? 'available',
            ];
            $lotRows[] = $filterRow($payload, $columns['lots']);
        }
        if ($lotRows) {
            $chunkInsert('lots', $lotRows);
        }

        // Buyers
        $buyerRows = [];
        foreach ($data['customers'] ?? [] as $b) {
            if (!isset($b['id'])) {
                continue;
            }
            $payload = [
                'id' => $b['id'],
                'name' => $b['name'] ?? 'Buyer',
                'phone' => $b['phone'] ?? null,
                'email' => $b['email'] ?? null,
                'address' => $b['address'] ?? null,
            ];
            $buyerRows[] = $filterRow($payload, $columns['buyers']);
        }
        if ($buyerRows) {
            $chunkInsert('buyers', $buyerRows);
        }

        // Marketers
        $marketerRows = [];
        foreach ($data['salesmen'] ?? [] as $m) {
            if (!isset($m['id'])) {
                continue;
            }
            $payload = [
                'id' => $m['id'],
                'name' => $m['name'] ?? 'Sales',
                'phone' => $m['phone'] ?? null,
            ];
            $marketerRows[] = $filterRow($payload, $columns['marketers']);
        }
        if ($marketerRows) {
            $chunkInsert('marketers', $marketerRows);
        }

        // Sales
        $saleRows = [];
        $paymentRows = [];
        foreach ($data['sales'] ?? [] as $s) {
            if (!isset($s['id'])) {
                continue;
            }
            $price = $s['grand_total'] ?? $s['harga_netto'] ?? 0;
            $dp = $s['dp_terbayar'] ?? $s['uang_muka_rp'] ?? 0;
            $tenor = $s['tenor'] ?? 0;
            $bookingDate = $toDate($s['invoice_date'] ?? null);
            $saleRows[] = $filterRow([
                'id' => $s['id'],
                'lot_id' => $s['kavling_id'] ?? null,
                'buyer_id' => $s['customer_id'] ?? null,
                'marketer_id' => $s['sales_id'] ?? null,
                'booking_date' => $bookingDate,
                'payment_method' => $tenor > 0 ? 'installment' : 'cash',
                'price' => $price,
                'down_payment' => $dp,
                'tenor_months' => $tenor,
                'due_day' => $s['jatuh_tempo_hari'] ?? null,
                'paid_amount' => 0,
                'outstanding_amount' => $price,
                'status' => 'active',
            ], $columns['sales']);

            if ($dp > 0) {
                $paymentRows[] = $filterRow([
                    'sale_id' => $s['id'],
                    'due_date' => $bookingDate,
                    'amount' => $dp,
                    'status' => 'paid',
                    'note' => 'Down Payment',
                    'paid_at' => $bookingDate,
                ], $columns['payments']);
            }
        }
        if ($saleRows) {
            $chunkInsert('sales', $saleRows);
        }
        if ($paymentRows) {
            $chunkInsert('payments', $paymentRows);
        }

        // Installments
        foreach ($data['installments'] ?? [] as $ins) {
            if (!isset($ins['sale_id'])) {
                continue;
            }
            $paymentRows[] = $filterRow([
                'sale_id' => $ins['sale_id'],
                'due_date' => $toDate($ins['due_date'] ?? null),
                'amount' => $ins['amount'] ?? 0,
                'status' => ($ins['status'] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid',
                'note' => 'Angsuran ke-' . ($ins['installment_number'] ?? '-'),
                'paid_at' => $toDate($ins['payment_date'] ?? null),
            ], $columns['payments']);
        }
        if ($paymentRows) {
            $chunkInsert('payments', $paymentRows);
        }

        // Recalculate totals
        foreach (DB::table('projects')->get() as $project) {
            $totalUnits = DB::table('lots')->where('project_id', $project->id)->count();
            $soldUnits = DB::table('lots')->where('project_id', $project->id)->where('status', 'sold')->count();
            DB::table('projects')->where('id', $project->id)->update([
                'total_units' => $totalUnits,
                'sold_units' => $soldUnits,
            ]);
        }

        foreach (DB::table('sales')->get() as $sale) {
            $paidSum = DB::table('payments')->where('sale_id', $sale->id)->where('status', 'paid')->sum('amount');
            $outstanding = max(0, ($sale->price ?? 0) - $paidSum);

            $update = [];
            if (isset($columns['sales']['paid_amount'])) {
                $update['paid_amount'] = min($sale->price ?? 0, $paidSum);
            }
            if (isset($columns['sales']['outstanding_amount'])) {
                $update['outstanding_amount'] = $outstanding;
            }
            if (isset($columns['sales']['status'])) {
                $update['status'] = $outstanding <= 0 ? 'paid_off' : 'active';
            }
            if ($update) {
                DB::table('sales')->where('id', $sale->id)->update($update);
            }
        }

        $this->command->info('Dummy data seeding completed!');
    }
}
