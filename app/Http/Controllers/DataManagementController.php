<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\CompanyProfile;
use App\Models\Lot;
use App\Models\Marketer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use Database\Seeders\DataDummySeeders;
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
        try {
            DB::transaction(function () {
                $this->resetData();
                // Gunakan seeder dummy terbaru (2023-2025) agar konsisten dengan logika aplikasi
                (new DataDummySeeders())->run();
                $this->seedDefaultCompanyProfile();
            });

            $this->clearCaches();

            return redirect()->route('data.index')->with('success', 'Data demo berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->route('data.index')->with('error', 'Gagal membuat data demo: ' . $e->getMessage());
        }
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
            'company_profile' => CompanyProfile::first(),
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
            // Restore Company Profile
            if (isset($data['company_profile'])) {
                $cpData = $data['company_profile'];
                // Since resetData truncated it, we create a new one
                CompanyProfile::create([
                    'name' => $cpData['name'] ?? 'Perusahaan Properti',
                    'npwp' => $cpData['npwp'] ?? null,
                    'email' => $cpData['email'] ?? null,
                    'phone' => $cpData['phone'] ?? null,
                    'address' => $cpData['address'] ?? null,
                    'signer_name' => $cpData['signer_name'] ?? 'Admin Keuangan',
                    'footer_note' => $cpData['footer_note'] ?? 'Terima kasih atas pembayaran Anda.',
                    'invoice_format' => $cpData['invoice_format'] ?? 'INV/{YYYY}/{MM}/{####}',
                    'receipt_format' => $cpData['receipt_format'] ?? 'KW/{YYYY}/{MM}/{####}',
                    'logo_path' => $cpData['logo_path'] ?? null,
                ]);
            } else {
                $this->seedDefaultCompanyProfile();
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

    /**
     * Generate demo data programmatically (no external JSON files).
     */
    private function generateDemoData(): void
    {
        $now = now()->toDateTimeString();
        $today = now()->startOfDay();

        // Projects
        $projects = [
            ['name' => 'Kavling Harmoni Alam', 'location' => 'Ciawi, Bogor', 'notes' => 'Pengembangan tahap 1 seluas 2 hektar.'],
            ['name' => 'Kavling Mutiara Residence', 'location' => 'Sentul, Bogor', 'notes' => 'Kawasan premium dengan pemandangan pegunungan.'],
            ['name' => 'Kavling Permata Hills', 'location' => 'Puncak, Bogor', 'notes' => 'Investasi properti premium di kawasan wisata.'],
            ['name' => 'Kavling Surya Garden', 'location' => 'Jonggol, Bogor', 'notes' => 'Perumahan asri dengan konsep hijau.'],
        ];

        $projectMap = [];
        foreach ($projects as $p) {
            $project = Project::create($p + ['total_units' => 0, 'sold_units' => 0]);
            $projectMap[] = $project->id;
        }

        // Lots
        $blocks = ['A', 'B', 'C', 'D', 'E'];
        $lotCounts = [40, 35, 25, 30];
        $lotMap = [];

        foreach ($projectMap as $idx => $projectId) {
            $totalLots = $lotCounts[$idx] ?? 30;
            $lotsPerBlock = (int) ceil($totalLots / count($blocks));
            $count = 0;

            foreach ($blocks as $block) {
                for ($num = 1; $num <= $lotsPerBlock && $count < $totalLots; $num++) {
                    $area = rand(80, 200);
                    $lot = Lot::create([
                        'project_id' => $projectId,
                        'block_number' => "{$block}-{$num}",
                        'area' => $area,
                        'base_price' => $area * rand(1000000, 1500000),
                        'status' => 'available',
                    ]);
                    $lotMap[] = $lot->id;
                    $count++;
                }
            }
        }

        // Marketers
        $marketerNames = ['Andi Firmansyah', 'Bima Sakti', 'Citra Dewi', 'Denny Pratama', 'Eka Putra', 'Fauzi Rahman'];
        $marketerMap = [];
        foreach ($marketerNames as $i => $name) {
            $marketer = Marketer::create(['name' => $name, 'phone' => '08123456700' . ($i + 1)]);
            $marketerMap[] = $marketer->id;
        }

        // Buyers
        $firstNames = ['Ahmad', 'Budi', 'Cahya', 'Dewi', 'Eka', 'Fitri', 'Galih', 'Hana', 'Irfan', 'Joko', 'Kartika', 'Lina', 'Maya', 'Nanda', 'Oscar', 'Putri', 'Reza', 'Sari', 'Taufik', 'Umi'];
        $lastNames = ['Wijaya', 'Santoso', 'Kusuma', 'Purnama', 'Pratama', 'Hidayat', 'Saputra', 'Wibowo', 'Setiawan', 'Nugraha'];
        $buyerMap = [];

        for ($i = 0; $i < 60; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $buyer = Buyer::create([
                'name' => "{$firstName} {$lastName}",
                'phone' => '08' . rand(1, 9) . rand(10000000, 99999999),
                'email' => strtolower("{$firstName}.{$lastName}" . rand(1, 99) . '@email.com'),
                'address' => 'Jl. Sudirman No. ' . rand(1, 100) . ', Jakarta',
            ]);
            $buyerMap[] = $buyer->id;
        }

        // Sales & Payments (2023-2025)
        shuffle($lotMap);
        $salesDistribution = [2023 => 25, 2024 => 30, 2025 => 30];
        $lotIndex = 0;

        foreach ($salesDistribution as $year => $count) {
            for ($i = 0; $i < $count && $lotIndex < count($lotMap); $i++) {
                $lotId = $lotMap[$lotIndex++];
                $lot = Lot::find($lotId);

                $bookingDate = \Carbon\Carbon::create($year, rand(1, 12), rand(1, 28));
                if ($bookingDate->gt($today)) {
                    $bookingDate = $today->copy()->subDays(rand(1, 30));
                }

                $price = (int) ($lot->base_price * (1 + rand(-5, 10) / 100));

                // 60% installment, 30% cash, 10% KPR
                $methodRand = rand(1, 100);
                if ($methodRand <= 60) {
                    $paymentMethod = 'installment';
                    $tenor = [12, 24, 36, 48][array_rand([12, 24, 36, 48])];
                    $dpPercent = rand(20, 40);
                } elseif ($methodRand <= 90) {
                    $paymentMethod = 'cash';
                    $tenor = 0;
                    $dpPercent = 100;
                } else {
                    $paymentMethod = 'kpr';
                    $tenor = 0;
                    $dpPercent = rand(15, 30);
                }

                $downPayment = (int) ($price * $dpPercent / 100);
                $paidAmount = 0;

                $sale = Sale::create([
                    'lot_id' => $lotId,
                    'buyer_id' => $buyerMap[array_rand($buyerMap)],
                    'marketer_id' => $marketerMap[array_rand($marketerMap)],
                    'booking_date' => $bookingDate->format('Y-m-d'),
                    'payment_method' => $paymentMethod,
                    'price' => $price,
                    'down_payment' => $downPayment,
                    'tenor_months' => $tenor,
                    'due_day' => rand(1, 28),
                    'paid_amount' => 0,
                    'outstanding_amount' => $price,
                    'status' => 'active',
                ]);

                // Create payments
                if ($paymentMethod === 'cash') {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $price,
                        'status' => 'paid',
                        'note' => 'Pembayaran Cash',
                        'paid_at' => $bookingDate->format('Y-m-d'),
                    ]);
                    $paidAmount = $price;
                } elseif ($paymentMethod === 'kpr') {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $downPayment,
                        'status' => 'paid',
                        'note' => 'DP (KPR)',
                        'paid_at' => $bookingDate->format('Y-m-d'),
                    ]);
                    Payment::create([
                        'sale_id' => $sale->id,
                        'due_date' => $bookingDate->copy()->addDays(30)->format('Y-m-d'),
                        'amount' => $price - $downPayment,
                        'status' => 'paid',
                        'note' => 'Pelunasan KPR Bank',
                        'paid_at' => $bookingDate->copy()->addDays(30)->format('Y-m-d'),
                    ]);
                    $paidAmount = $price;
                } else {
                    // Installment
                    Payment::create([
                        'sale_id' => $sale->id,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $downPayment,
                        'status' => 'paid',
                        'note' => 'Down Payment',
                        'paid_at' => $bookingDate->format('Y-m-d'),
                    ]);
                    $paidAmount = $downPayment;

                    $remaining = $price - $downPayment;
                    $monthly = (int) ceil($remaining / $tenor);

                    for ($inst = 1; $inst <= $tenor; $inst++) {
                        $dueDate = $bookingDate->copy()->addMonths($inst);
                        $amount = ($inst === $tenor) ? $remaining - ($monthly * ($tenor - 1)) : $monthly;

                        if ($dueDate->lte($today)) {
                            $isPaid = rand(1, 100) <= 85;
                            $status = $isPaid ? 'paid' : 'unpaid';
                            $paidAt = $isPaid ? $dueDate->format('Y-m-d') : null;
                            if ($isPaid)
                                $paidAmount += $amount;
                        } else {
                            $status = 'unpaid';
                            $paidAt = null;
                        }

                        Payment::create([
                            'sale_id' => $sale->id,
                            'due_date' => $dueDate->format('Y-m-d'),
                            'amount' => $amount,
                            'status' => $status,
                            'note' => "Angsuran ke-{$inst}",
                            'paid_at' => $paidAt,
                        ]);
                    }
                }

                // Update sale totals
                $outstanding = max(0, $price - $paidAmount);
                $sale->update([
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstanding,
                    'status' => $outstanding <= 0 ? 'paid_off' : 'active',
                ]);

                // Update lot status
                $lot->update(['status' => 'sold']);
            }
        }

        // Update project statistics
        foreach (Project::all() as $project) {
            $project->total_units = $project->lots()->count();
            $project->sold_units = $project->lots()->where('status', 'sold')->count();
            $project->save();
        }
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
