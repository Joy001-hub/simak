<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\CompanyProfile;
use App\Models\Lot;
use App\Models\Marketer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataManagementController extends Controller
{
    public function index()
    {
        $stats = [
            'projects' => Project::count(),
            'lots' => Lot::count(),
            'buyers' => Buyer::count(),
            'sales' => Sale::count(),
            'payments' => Payment::count(),
        ];

        return view('data-management.index', compact('stats'));
    }

    public function loadDemo()
    {
        $path = storage_path('app/demo-seed.json');
        if (!file_exists($path)) {
            // Fallback to public assets
            $path = public_path('assets/data-dummy.json');
        }
        if (!file_exists($path)) {
            return redirect()->route('data.index')->with('error', 'File data dummy tidak ditemukan.');
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!$data || !is_array($data)) {
            return redirect()->route('data.index')->with('error', 'File data dummy tidak valid.');
        }

        DB::transaction(function () use ($data) {
            $this->resetData();
            $this->importDemoData($data);
            $this->seedDefaultCompanyProfile();
        });

        $this->clearCaches();

        return redirect()->route('data.index')->with('success', 'Data dummy berhasil dimuat dari backup JSON.');
    }

    public function reset()
    {
        DB::transaction(function () {
            $this->resetData();
        });

        $this->clearCaches();

        return redirect()->route('data.index')->with('success', 'Data aplikasi berhasil direset ke keadaan kosong.');
    }

    public function backup()
    {
        $payload = [
            'projects' => Project::all(),
            'lots' => Lot::all(),
            'buyers' => Buyer::all(),
            'marketers' => Marketer::all(),
            'sales' => Sale::all(),
            'payments' => Payment::all(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $filename = 'backup-' . now()->format('Ymd-His') . '.json';

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json', 'max:51200'],
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            return back()->with('error', 'File backup tidak valid.');
        }

        DB::transaction(function () use ($data) {
            $this->resetData();

            $projectMap = [];
            foreach ($data['projects'] ?? [] as $p) {
                $project = Project::create([
                    'name' => $p['name'] ?? 'Project',
                    'location' => $p['location'] ?? null,
                    'notes' => $p['notes'] ?? null,
                    'total_units' => $p['total_units'] ?? 0,
                    'sold_units' => $p['sold_units'] ?? 0,
                ]);
                $projectMap[$p['id'] ?? null] = $project->id;
            }

            $lotMap = [];
            foreach ($data['lots'] ?? [] as $l) {
                $lot = Lot::create([
                    'project_id' => $projectMap[$l['project_id'] ?? null] ?? null,
                    'block_number' => $l['block_number'] ?? 'BLK',
                    'area' => $l['area'] ?? 0,
                    'base_price' => $l['base_price'] ?? 0,
                    'status' => $l['status'] ?? 'available',
                ]);
                $lotMap[$l['id'] ?? null] = $lot->id;
            }

            $buyerMap = [];
            foreach ($data['buyers'] ?? [] as $b) {
                $buyer = Buyer::create([
                    'name' => $b['name'] ?? 'Buyer',
                    'phone' => $b['phone'] ?? null,
                    'email' => $b['email'] ?? null,
                    'address' => $b['address'] ?? null,
                ]);
                $buyerMap[$b['id'] ?? null] = $buyer->id;
            }

            $marketerMap = [];
            foreach ($data['marketers'] ?? [] as $m) {
                $marketer = Marketer::create([
                    'name' => $m['name'] ?? 'Marketer',
                    'phone' => $m['phone'] ?? null,
                ]);
                $marketerMap[$m['id'] ?? null] = $marketer->id;
            }

            $saleMap = [];
            foreach ($data['sales'] ?? [] as $s) {
                // Support both old format (kavling_id) and new format (lot_id)
                $lotIdKey = $s['lot_id'] ?? $s['kavling_id'] ?? null;
                $lotId = $lotMap[$lotIdKey] ?? null;
                
                // Support both old format (customer_id) and new format (buyer_id)
                $buyerIdKey = $s['buyer_id'] ?? $s['customer_id'] ?? null;
                $buyerId = $buyerMap[$buyerIdKey] ?? null;
                
                // Support both old format (sales_id) and new format (marketer_id)
                $marketerIdKey = $s['marketer_id'] ?? $s['sales_id'] ?? null;
                $marketerId = $marketerMap[$marketerIdKey] ?? null;
                
                // Skip sale if lot_id is null (required field)
                if (!$lotId) {
                    logger()->warning('[Restore] Skipping sale with invalid lot_id', ['sale_data' => $s]);
                    continue;
                }
                
                $price = $s['price'] ?? $s['grand_total'] ?? $s['harga_netto'] ?? 0;
                $dp = $s['down_payment'] ?? $s['dp_terbayar'] ?? $s['uang_muka_rp'] ?? 0;
                $tenor = $s['tenor_months'] ?? $s['tenor'] ?? 0;
                $dueDay = $s['due_day'] ?? $s['jatuh_tempo_hari'] ?? null;
                $bookingDate = $s['booking_date'] ?? $s['invoice_date'] ?? null;
                $paymentMethod = $s['payment_method'] ?? ($tenor > 0 ? 'installment' : 'cash');
                $status = $s['status'] ?? 'active';
                $paidAmount = $s['paid_amount'] ?? 0;
                $outstandingAmount = $s['outstanding_amount'] ?? $price;
                
                $sale = Sale::create([
                    'lot_id' => $lotId,
                    'buyer_id' => $buyerId,
                    'marketer_id' => $marketerId,
                    'booking_date' => $bookingDate,
                    'payment_method' => $paymentMethod,
                    'price' => $price,
                    'down_payment' => $dp,
                    'tenor_months' => $tenor,
                    'due_day' => $dueDay,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstandingAmount,
                    'status' => $status,
                ]);
                $saleMap[$s['id'] ?? null] = $sale->id;

                if ($dp > 0) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'due_date' => $bookingDate,
                        'amount' => $dp,
                        'status' => 'paid',
                        'note' => 'Down Payment',
                        'paid_at' => $bookingDate,
                    ]);
                }
            }

            // Support both old format (installments) and new format (payments)
            $paymentsList = $data['payments'] ?? $data['installments'] ?? [];
            foreach ($paymentsList as $pmt) {
                $saleId = $saleMap[$pmt['sale_id'] ?? null] ?? null;
                if (!$saleId) {
                    continue;
                }

                // Determine status
                $status = $pmt['status'] ?? 'unpaid';
                if (!in_array($status, ['paid', 'unpaid', 'overdue'])) {
                    $status = $status === 'paid' ? 'paid' : 'unpaid';
                }

                Payment::create([
                    'sale_id' => $saleId,
                    'due_date' => $pmt['due_date'] ?? null,
                    'amount' => $pmt['amount'] ?? 0,
                    'status' => $status,
                    'note' => $pmt['note'] ?? (isset($pmt['installment_number']) ? 'Angsuran ke-' . $pmt['installment_number'] : null),
                    'paid_at' => $pmt['paid_at'] ?? $pmt['payment_date'] ?? null,
                ]);
            }

            $projects = Project::with('lots')->get();
            foreach ($projects as $project) {
                $project->total_units = $project->lots->count();
                $project->sold_units = $project->lots->where('status', 'sold')->count();
                $project->save();
            }

            foreach (Sale::all() as $sale) {
                $paidSum = $sale->payments()->where('status', 'paid')->sum('amount');
                $sale->paid_amount = min($sale->price, $paidSum);
                $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);
                $sale->status = $sale->outstanding_amount <= 0 ? 'paid_off' : 'active';
                $sale->save();
            }
        });

        $this->clearCaches();

        return redirect()->route('data.index')->with('success', 'Backup berhasil dipulihkan.');
    }

    private function clearCaches(): void
    {
        Cache::flush();
    }

    private function resetData(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        Payment::truncate();
        Sale::truncate();
        Lot::truncate();
        Project::truncate();
        Buyer::truncate();
        Marketer::truncate();
        CompanyProfile::truncate();
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function seedDefaultCompanyProfile(): void
    {
        $defaults = config('company');
        CompanyProfile::firstOrCreate([], [
            'name' => $defaults['name'] ?? 'Perusahaan Properti',
            'npwp' => '01.234.567.8-901.000',
            'email' => $defaults['email'] ?? null,
            'phone' => $defaults['phone'] ?? null,
            'address' => $defaults['address'] ?? null,
            'signer_name' => 'Admin Keuangan',
            'footer_note' => 'Terima kasih atas pembayaran Anda.',
            'invoice_format' => 'INV/{YYYY}/{MM}/{####}',
            'receipt_format' => 'KW/{YYYY}/{MM}/{####}',
            'logo_path' => null,
        ]);
    }

    private function importDemoData(array $data): void
    {
        $projectMap = [];
        foreach ($data['projects'] ?? [] as $p) {
            $project = Project::create([
                'name' => $p['name'] ?? 'Project',
                'location' => $p['location'] ?? null,
                'notes' => $p['description'] ?? ($p['notes'] ?? null),
                'total_units' => 0,
                'sold_units' => 0,
            ]);
            $projectMap[$p['id'] ?? null] = $project->id;
        }

        $lotMap = [];
        foreach ($data['lots'] ?? [] as $l) {
            // Support both formats: block_number OR block + lot_number
            $blockNumber = $l['block_number'] ?? trim(($l['block'] ?? '') . '-' . ($l['lot_number'] ?? ''));
            $lot = Lot::create([
                'project_id' => $projectMap[$l['project_id'] ?? null] ?? null,
                'block_number' => $blockNumber ?: 'LOT',
                'area' => $l['area'] ?? 0,
                'base_price' => $l['base_price'] ?? 0,
                'status' => $l['status'] ?? 'available',
            ]);
            $lotMap[$l['id'] ?? null] = $lot->id;
        }

        // Support both formats: buyers OR customers
        $buyerMap = [];
        $buyerData = $data['buyers'] ?? $data['customers'] ?? [];
        foreach ($buyerData as $b) {
            $buyer = Buyer::create([
                'name' => $b['name'] ?? 'Buyer',
                'phone' => $b['phone'] ?? null,
                'email' => $b['email'] ?? null,
                'address' => $b['address'] ?? null,
            ]);
            $buyerMap[$b['id'] ?? null] = $buyer->id;
        }

        // Support both formats: marketers OR salesmen
        $marketerMap = [];
        $marketerData = $data['marketers'] ?? $data['salesmen'] ?? [];
        foreach ($marketerData as $m) {
            $marketer = Marketer::create([
                'name' => $m['name'] ?? 'Sales',
                'phone' => $m['phone'] ?? null,
            ]);
            $marketerMap[$m['id'] ?? null] = $marketer->id;
        }

        $saleMap = [];
        foreach ($data['sales'] ?? [] as $s) {
            // Support both formats for field names
            $lotIdKey = $s['lot_id'] ?? $s['kavling_id'] ?? null;
            $buyerIdKey = $s['buyer_id'] ?? $s['customer_id'] ?? null;
            $marketerIdKey = $s['marketer_id'] ?? $s['sales_id'] ?? null;
            
            $lotId = $lotMap[$lotIdKey] ?? null;
            $buyerId = $buyerMap[$buyerIdKey] ?? null;
            $marketerId = $marketerMap[$marketerIdKey] ?? null;
            
            // Skip if lot_id is null (required field)
            if (!$lotId) {
                continue;
            }
            
            $price = $s['price'] ?? $s['grand_total'] ?? $s['harga_netto'] ?? 0;
            $dp = $s['down_payment'] ?? $s['dp_terbayar'] ?? $s['uang_muka_rp'] ?? 0;
            $tenor = $s['tenor_months'] ?? $s['tenor'] ?? 0;
            $bookingDate = $s['booking_date'] ?? $s['invoice_date'] ?? null;
            $paymentMethod = $s['payment_method'] ?? ($tenor > 0 ? 'installment' : 'cash');
            $dueDay = $s['due_day'] ?? $s['jatuh_tempo_hari'] ?? null;
            $paidAmount = $s['paid_amount'] ?? 0;
            $outstandingAmount = $s['outstanding_amount'] ?? $price;
            $status = $s['status'] ?? 'active';
            
            $sale = Sale::create([
                'lot_id' => $lotId,
                'buyer_id' => $buyerId,
                'marketer_id' => $marketerId,
                'booking_date' => $bookingDate,
                'payment_method' => $paymentMethod,
                'price' => $price,
                'down_payment' => $dp,
                'tenor_months' => $tenor,
                'due_day' => $dueDay,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'status' => $status,
            ]);
            $saleMap[$s['id'] ?? null] = $sale->id;

            // Only create down payment record if not already in payments data
            if ($dp > 0 && empty($data['payments'])) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'due_date' => $bookingDate,
                    'amount' => $dp,
                    'status' => 'paid',
                    'note' => 'Uang Muka',
                    'paid_at' => $bookingDate,
                ]);
            }
        }

        // Support both formats: payments OR installments
        $paymentData = $data['payments'] ?? $data['installments'] ?? [];
        foreach ($paymentData as $pmt) {
            $saleId = $saleMap[$pmt['sale_id'] ?? null] ?? null;
            if (!$saleId) {
                continue;
            }

            $status = $pmt['status'] ?? 'unpaid';
            if (!in_array($status, ['paid', 'unpaid', 'overdue'])) {
                $status = $status === 'paid' ? 'paid' : 'unpaid';
            }

            Payment::create([
                'sale_id' => $saleId,
                'due_date' => $pmt['due_date'] ?? null,
                'amount' => $pmt['amount'] ?? 0,
                'status' => $status,
                'note' => $pmt['note'] ?? (isset($pmt['installment_number']) ? 'Angsuran ke-' . $pmt['installment_number'] : null),
                'paid_at' => $pmt['paid_at'] ?? $pmt['payment_date'] ?? null,
            ]);
        }

        $projects = Project::with('lots')->get();
        foreach ($projects as $project) {
            $project->total_units = $project->lots->count();
            $project->sold_units = $project->lots->where('status', 'sold')->count();
            $project->save();
        }

        // Recalculate paid amounts if payments were included
        if (!empty($data['payments'])) {
            foreach (Sale::all() as $sale) {
                $paidSum = $sale->payments()->where('status', 'paid')->sum('amount');
                $sale->paid_amount = min($sale->price, $paidSum);
                $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);
                $sale->status = $sale->outstanding_amount <= 0 ? 'paid_off' : 'active';
                $sale->save();
            }
        }
    }
}
