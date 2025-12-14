<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Lot;
use App\Models\Buyer;
use App\Models\Marketer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan profil perusahaan ada
        CompanyProfile::firstOrCreate([], [
            'name' => 'Nama Perusahaan',
            'npwp' => '01.234.567.8-901.000',
            'email' => 'emailperusahaan@gmail.com',
            'phone' => '021-12345678',
            'address' => 'Cisarua, Kabupaten Bogor, Jawa Barat 16750',
            'signer_name' => 'Admin Keuangan',
            'footer_note' => 'Terima kasih atas pembayaran Anda.',
            'invoice_format' => 'INV/{YYYY}/{MM}/{####}',
            'receipt_format' => 'KW/{YYYY}/{MM}/{####}',
            'logo_path' => null,
        ]);

        // Seed demo data lengkap
        $this->call(DummyDataSeeder::class);

        // Optional: seed a placeholder logo if exists
        if (Storage::disk('public')->exists('logos/company-logo.png') === false) {
            Storage::disk('public')->makeDirectory('logos');
        }
    }
}
