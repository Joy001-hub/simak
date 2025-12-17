<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign keys for the entire seeding process
        DB::statement('PRAGMA foreign_keys = OFF');

        // Reset tables (keep company profile)
        foreach (['payments', 'sales', 'lots', 'projects', 'buyers', 'marketers'] as $table) {
            DB::table($table)->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
        }

        $now = Carbon::now()->toDateTimeString();

        // ==========================================
        // PROJECTS - 4 Proyek Kavling
        // ==========================================
        $projects = [
            [
                'id' => 1,
                'name' => 'Kavling Harmoni Alam',
                'location' => 'Ciawi, Bogor',
                'notes' => 'Pengembangan tahap 1 seluas 2 hektar. Lokasi strategis dekat jalan raya utama dan akses tol.',
                'total_units' => 40,
                'sold_units' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Kavling Mutiara Residence',
                'location' => 'Sentul, Bogor',
                'notes' => 'Kawasan premium dengan fasilitas lengkap dan pemandangan pegunungan.',
                'total_units' => 35,
                'sold_units' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Kavling Permata Hills',
                'location' => 'Puncak, Bogor',
                'notes' => 'Investasi properti premium di kawasan wisata dengan udara sejuk pegunungan.',
                'total_units' => 25,
                'sold_units' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Kavling Surya Garden',
                'location' => 'Jonggol, Bogor',
                'notes' => 'Perumahan asri dengan konsep hijau dan ramah lingkungan. Cocok untuk keluarga muda.',
                'total_units' => 30,
                'sold_units' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        DB::table('projects')->insert($projects);
        $this->command->info('Projects seeded: ' . count($projects));

        // ==========================================
        // LOTS - Kavling untuk setiap proyek
        // ==========================================
        $lots = [];
        $lotId = 1;
        $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
        $projectLotCounts = [1 => 40, 2 => 35, 3 => 25, 4 => 30];

        foreach ($projectLotCounts as $projectId => $totalLots) {
            $lotsPerBlock = (int) ceil($totalLots / count($blocks));
            $lotCount = 0;
            
            foreach ($blocks as $block) {
                for ($num = 1; $num <= $lotsPerBlock && $lotCount < $totalLots; $num++) {
                    $area = rand(80, 200);
                    $pricePerMeter = rand(1000000, 1500000);
                    $lots[] = [
                        'id' => $lotId,
                        'project_id' => $projectId,
                        'block_number' => "{$block}-{$num}",
                        'area' => $area,
                        'base_price' => $area * $pricePerMeter,
                        'status' => 'available',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $lotId++;
                    $lotCount++;
                }
            }
        }
        DB::table('lots')->insert($lots);
        $this->command->info('Lots seeded: ' . count($lots));

        // ==========================================
        // MARKETERS - Tim Marketing
        // ==========================================
        $marketers = [
            ['id' => 1, 'name' => 'Andi Firmansyah', 'phone' => '081234567001', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Bima Sakti', 'phone' => '081234567002', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Citra Dewi', 'phone' => '081234567003', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Denny Pratama', 'phone' => '081234567004', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Eka Putra', 'phone' => '081234567005', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Fauzi Rahman', 'phone' => '081234567006', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'Gina Marlina', 'phone' => '081234567007', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'Hadi Santoso', 'phone' => '081234567008', 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('marketers')->insert($marketers);
        $this->command->info('Marketers seeded: ' . count($marketers));

        // ==========================================
        // BUYERS - Pembeli (80 orang)
        // ==========================================
        $firstNames = ['Ahmad', 'Budi', 'Cahya', 'Dewi', 'Eka', 'Fitri', 'Galih', 'Hana', 'Irfan', 'Joko', 
                       'Kartika', 'Lina', 'Maya', 'Nanda', 'Oscar', 'Putri', 'Reza', 'Sari', 'Taufik', 'Umi',
                       'Vera', 'Wawan', 'Yani', 'Zainal', 'Agus', 'Bambang', 'Clara', 'Dian', 'Endang', 'Fajar',
                       'Gunawan', 'Hendra', 'Indah', 'Jihan', 'Kurnia', 'Lukman', 'Mega', 'Nurul', 'Oki', 'Pandu'];
        $lastNames = ['Wijaya', 'Santoso', 'Kusuma', 'Purnama', 'Pratama', 'Hidayat', 'Saputra', 'Wibowo', 
                      'Setiawan', 'Nugraha', 'Permana', 'Gunawan', 'Susanto', 'Budiman', 'Hartono', 'Suryadi',
                      'Prasetyo', 'Ramadhan', 'Utami', 'Handoko'];
        $cities = ['Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara', 'Jakarta Pusat',
                   'Bogor', 'Depok', 'Tangerang', 'Bekasi', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta'];
        $streets = ['Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. Rasuna Said', 'Jl. Kuningan',
                    'Jl. Kemang', 'Jl. Pondok Indah', 'Jl. Kelapa Gading', 'Jl. Pluit', 'Jl. Pantai Indah'];

        $buyers = [];
        for ($i = 1; $i <= 80; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $buyers[] = [
                'id' => $i,
                'name' => "{$firstName} {$lastName}",
                'phone' => '08' . rand(1, 9) . rand(10000000, 99999999),
                'email' => strtolower(str_replace(' ', '.', "{$firstName}.{$lastName}") . rand(1, 99) . "@email.com"),
                'address' => $streets[array_rand($streets)] . ' No. ' . rand(1, 100) . ', ' . $cities[array_rand($cities)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('buyers')->insert($buyers);
        $this->command->info('Buyers seeded: ' . count($buyers));

        // ==========================================
        // SALES & PAYMENTS - Penjualan dari 2023-2025
        // ==========================================
        $sales = [];
        $payments = [];
        $saleId = 1;
        $paymentId = 1;
        $today = Carbon::today();

        // Distribusi penjualan per tahun: 2023 (30), 2024 (35), 2025 (35)
        $salesDistribution = [
            2023 => 30,
            2024 => 35,
            2025 => 35,
        ];

        $availableLots = collect($lots)->pluck('id')->toArray();
        shuffle($availableLots);
        $lotIndex = 0;

        foreach ($salesDistribution as $year => $count) {
            for ($i = 0; $i < $count && $lotIndex < count($availableLots); $i++) {
                $lotId = $availableLots[$lotIndex];
                $lot = collect($lots)->firstWhere('id', $lotId);
                $lotIndex++;

                // Random booking date dalam tahun tersebut
                $month = rand(1, 12);
                $day = rand(1, 28);
                $bookingDate = Carbon::create($year, $month, $day);
                
                // Skip jika booking date di masa depan
                if ($bookingDate->gt($today)) {
                    $bookingDate = $today->copy()->subDays(rand(1, 30));
                }

                $buyerId = rand(1, 80);
                $marketerId = rand(1, 8);
                $basePrice = $lot['base_price'];
                
                // Variasi harga (diskon atau markup)
                $priceVariation = rand(-5, 10) / 100;
                $price = (int) ($basePrice * (1 + $priceVariation));

                // Metode pembayaran: 60% angsuran, 30% cash, 10% KPR
                $paymentMethodRand = rand(1, 100);
                if ($paymentMethodRand <= 60) {
                    $paymentMethod = 'installment';
                    $tenorOptions = [12, 24, 36, 48, 60, 120];
                    $tenorMonths = $tenorOptions[array_rand($tenorOptions)];
                    $dpPercent = rand(20, 40);
                } elseif ($paymentMethodRand <= 90) {
                    $paymentMethod = 'cash';
                    $tenorMonths = 0;
                    $dpPercent = 100;
                } else {
                    $paymentMethod = 'kpr';
                    $tenorMonths = 0;
                    $dpPercent = rand(15, 30);
                }

                $downPayment = (int) ($price * $dpPercent / 100);
                $dueDay = rand(1, 28);

                // Hitung status berdasarkan waktu
                $paidAmount = 0;
                $paymentRecords = [];

                if ($paymentMethod === 'cash') {
                    // Cash - langsung lunas
                    $paidAmount = $price;
                    
                    $paymentRecords[] = [
                        'id' => $paymentId++,
                        'sale_id' => $saleId,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $price,
                        'status' => 'paid',
                        'note' => 'Pembayaran Cash',
                        'paid_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } elseif ($paymentMethod === 'kpr') {
                    // KPR - DP dibayar, sisanya via bank
                    $paidAmount = $price; // Dianggap lunas karena dilanjut bank
                    
                    $paymentRecords[] = [
                        'id' => $paymentId++,
                        'sale_id' => $saleId,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $downPayment,
                        'status' => 'paid',
                        'note' => 'Down Payment (KPR)',
                        'paid_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    $paymentRecords[] = [
                        'id' => $paymentId++,
                        'sale_id' => $saleId,
                        'due_date' => $bookingDate->copy()->addDays(30)->format('Y-m-d'),
                        'amount' => $price - $downPayment,
                        'status' => 'paid',
                        'note' => 'Pelunasan via KPR Bank',
                        'paid_at' => $bookingDate->copy()->addDays(30)->format('Y-m-d H:i:s'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    // Angsuran
                    $remainingAfterDP = $price - $downPayment;
                    $monthlyInstallment = (int) ceil($remainingAfterDP / $tenorMonths);
                    
                    // DP Payment
                    $paymentRecords[] = [
                        'id' => $paymentId++,
                        'sale_id' => $saleId,
                        'due_date' => $bookingDate->format('Y-m-d'),
                        'amount' => $downPayment,
                        'status' => 'paid',
                        'note' => 'Down Payment',
                        'paid_at' => $bookingDate->format('Y-m-d H:i:s'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $paidAmount = $downPayment;

                    // Generate angsuran
                    for ($installment = 1; $installment <= $tenorMonths; $installment++) {
                        $dueDate = $bookingDate->copy()->addMonths($installment)->day($dueDay);
                        $amount = ($installment === $tenorMonths) 
                            ? $remainingAfterDP - ($monthlyInstallment * ($tenorMonths - 1))
                            : $monthlyInstallment;

                        // Tentukan status pembayaran
                        if ($dueDate->lte($today)) {
                            // Sudah jatuh tempo - 85% dibayar, 15% tunggakan
                            $isPaid = rand(1, 100) <= 85;
                            
                            if ($isPaid) {
                                $paymentStatus = 'paid';
                                $paidAt = $dueDate->copy()->addDays(rand(-5, 10))->format('Y-m-d H:i:s');
                                $paidAmount += $amount;
                            } else {
                                $paymentStatus = 'unpaid';
                                $paidAt = null;
                            }
                        } else {
                            // Belum jatuh tempo
                            $paymentStatus = 'unpaid';
                            $paidAt = null;
                        }

                        $paymentRecords[] = [
                            'id' => $paymentId++,
                            'sale_id' => $saleId,
                            'due_date' => $dueDate->format('Y-m-d'),
                            'amount' => $amount,
                            'status' => $paymentStatus,
                            'note' => "Angsuran ke-{$installment}",
                            'paid_at' => $paidAt,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                $outstandingAmount = max(0, $price - $paidAmount);
                $status = $outstandingAmount <= 0 ? 'paid_off' : 'active';

                // Random beberapa penjualan dibatalkan (5%)
                if (rand(1, 100) <= 5 && $status === 'active') {
                    $cancelTypes = ['canceled_hapus', 'canceled_refund'];
                    $status = $cancelTypes[array_rand($cancelTypes)];
                }

                $sales[] = [
                    'id' => $saleId,
                    'lot_id' => $lotId,
                    'buyer_id' => $buyerId,
                    'marketer_id' => $marketerId,
                    'booking_date' => $bookingDate->format('Y-m-d'),
                    'payment_method' => $paymentMethod,
                    'price' => $price,
                    'down_payment' => $downPayment,
                    'tenor_months' => $tenorMonths,
                    'due_day' => $dueDay,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstandingAmount,
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $payments = array_merge($payments, $paymentRecords);

                // Update lot status
                if (!in_array($status, ['canceled_hapus', 'canceled_refund'])) {
                    DB::table('lots')->where('id', $lotId)->update(['status' => 'sold']);
                }

                $saleId++;
            }
        }

        // Insert sales
        foreach (array_chunk($sales, 100) as $chunk) {
            DB::table('sales')->insert($chunk);
        }
        $this->command->info('Sales seeded: ' . count($sales));

        // Insert payments
        foreach (array_chunk($payments, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
        $this->command->info('Payments seeded: ' . count($payments));

        // ==========================================
        // Update Project Statistics
        // ==========================================
        foreach ($projects as $project) {
            $soldCount = DB::table('lots')
                ->where('project_id', $project['id'])
                ->where('status', 'sold')
                ->count();
            
            DB::table('projects')
                ->where('id', $project['id'])
                ->update(['sold_units' => $soldCount]);
        }

        // ==========================================
        // Statistik Akhir
        // ==========================================
        $overdueCount = DB::table('payments')
            ->where('status', 'unpaid')
            ->whereDate('due_date', '<', $today)
            ->count();
        
        $paidOffCount = DB::table('sales')->where('status', 'paid_off')->count();
        $activeCount = DB::table('sales')->where('status', 'active')->count();
        $canceledCount = DB::table('sales')->whereIn('status', ['canceled_hapus', 'canceled_refund'])->count();

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('       STATISTIK DATA DUMMY');
        $this->command->info('========================================');
        $this->command->info("Proyek        : " . count($projects));
        $this->command->info("Kavling       : " . count($lots));
        $this->command->info("Buyer         : " . count($buyers));
        $this->command->info("Marketer      : " . count($marketers));
        $this->command->info("Penjualan     : " . count($sales));
        $this->command->info("  - Lunas     : {$paidOffCount}");
        $this->command->info("  - Aktif     : {$activeCount}");
        $this->command->info("  - Dibatalkan: {$canceledCount}");
        $this->command->info("Pembayaran    : " . count($payments));
        $this->command->info("Tunggakan     : {$overdueCount}");
        $this->command->info('========================================');

        // Re-enable foreign keys
        DB::statement('PRAGMA foreign_keys = ON');

        $this->command->info('');
        $this->command->info('Data dummy berhasil dibuat!');
    }
}
