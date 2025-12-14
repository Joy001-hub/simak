# Integrasi Data Dummy

Aplikasi ini sekarang mendukung data dummy yang dimuat dari file JSON untuk testing dan development.

## File Data Dummy

- **Lokasi**: `src/assets/data-dummy.json`
- **Ukuran**: ~14,800 baris
- **Format**: JSON dengan struktur:
  - `companyProfile` - Profil perusahaan
  - `projects` - Daftar proyek
  - `lots` - Daftar kavling
  - `sales` - Daftar penjualan
  - `salesmen` - Daftar sales person
  - `customers` - Daftar pelanggan

## Service

### DummyDataService

Lokasi: `src/services/dummy-data.service.ts`

Service ini menangani loading dan penyediaan data dummy ke seluruh aplikasi.

#### Methods

```typescript
// Load data dari file (otomatis dipanggil saat service diinisialisasi)
loadDummyData(): void

// Ambil semua data
getData(): DummyData | null

// Ambil data spesifik
getCompanyProfile(): Observable<DummyData>
getProjects(): Observable<DummyData>
getLots(): Observable<DummyData>
getSales(): Observable<DummyData>
getSalesmen(): Observable<DummyData>
getCustomers(): Observable<DummyData>

// Observable untuk subscribe
data$: Observable<DummyData | null>
```

## Model/Interface

Lokasi: `src/models/dummy-data.models.ts`

Berisi TypeScript interfaces:
- `CompanyProfile`
- `Project`
- `Lot`
- `Sale`
- `Salesman`
- `Customer`
- `DummyDataModel`

## Cara Pakai di Component

### 1. Import service dan injectable decorator

```typescript
import { Component, OnInit } from '@angular/core';
import { DummyDataService } from '../services/dummy-data.service';
import { DummyDataModel, Project, Lot } from '../models/dummy-data.models';

@Component({
  selector: 'app-example',
  templateUrl: './example.component.html',
  styleUrls: ['./example.component.css']
})
export class ExampleComponent implements OnInit {
  companyProfile: any;
  projects: Project[] = [];
  lots: Lot[] = [];

  constructor(private dummyDataService: DummyDataService) {}

  ngOnInit() {
    // Subscribe ke data dummy
    this.dummyDataService.data$.subscribe((data: DummyDataModel | null) => {
      if (data) {
        this.companyProfile = data.companyProfile;
        this.projects = data.projects;
        this.lots = data.lots;
        console.log('Data loaded:', data);
      }
    });
  }

  // Atau ambil data spesifik
  loadProjects() {
    this.dummyDataService.getProjects().subscribe(data => {
      this.projects = data.projects;
    });
  }
}
```

### 2. Gunakan di template

```html
<div>
  <h2>{{ companyProfile?.nama }}</h2>
  <p>{{ companyProfile?.alamat }}</p>
  
  <h3>Projects</h3>
  <ul>
    <li *ngFor="let project of projects">
      {{ project.name }} - {{ project.location }}
    </li>
  </ul>

  <h3>Lots</h3>
  <table>
    <thead>
      <tr>
        <th>Block</th>
        <th>Lot</th>
        <th>Area</th>
        <th>Price</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr *ngFor="let lot of lots">
        <td>{{ lot.block }}</td>
        <td>{{ lot.lot_number }}</td>
        <td>{{ lot.area }} m²</td>
        <td>Rp {{ lot.base_price | number }}</td>
        <td>{{ lot.status }}</td>
      </tr>
    </tbody>
  </table>
</div>
```

## Struktur Data JSON

### companyProfile
```json
{
  "nama": "Nama Perusahaan",
  "alamat": "Cisarua, Kabupaten Bogor, Jawa Barat 16750",
  "telepon": "021-12345678",
  "email": "asee@gmail.com",
  "website": "www.propertisejahtera.com",
  "npwp": "01.234.567.8-901.000",
  "logo_url": "https://...",
  "ttd_admin_nama": "Admin Keuangan",
  "footer_cetak": "Terima kasih atas pembayaran Anda.",
  "nomor_format": {
    "faktur": "INV/{YYYY}/{MM}/{####}",
    "kuitansi": "KW/{YYYY}/{MM}/{####}"
  }
}
```

### projects (array)
```json
{
  "id": 1,
  "name": "Kavling Harmoni Alam",
  "location": "Ciawi, Bogor",
  "description": "Pengembangan tahap 1 seluas 2 hektar."
}
```

### lots (array)
```json
{
  "id": 1,
  "project_id": 1,
  "block": "A",
  "lot_number": "1",
  "area": 120,
  "base_price": 120000000,
  "status": "sold"
}
```

### sales, salesmen, customers
Format mirip, dengan fields relevan seperti `id`, `name`, `phone`, `email`, dll.

## Build & Deploy

Untuk build aplikasi:

```bash
npm run build:web
```

Data dummy akan otomatis disertakan di output build di folder `dist/browser/assets/`.

Untuk Electron packaging:

```bash
npm run package:win    # Untuk Windows exe
```

## Testing

Untuk memastikan data dummy ter-load dengan benar:

1. Jalankan aplikasi (dev atau production)
2. Buka Developer Tools (F12)
3. Cek Console untuk log "Dummy data loaded" dan structure datanya
4. Verifikasi component menampilkan data dengan benar

## Troubleshooting

### Data tidak ter-load
- Periksa Console di Developer Tools untuk error message
- Pastikan `HttpClientModule` diimpor di app module/component
- Pastikan file `src/assets/data-dummy.json` ada

### File JSON tidak ditemukan
- Pastikan file ada di `src/assets/data-dummy.json`
- Pastikan base href di `index.html` benar
- Cek di Network tab Developer Tools apakah request ke `assets/data-dummy.json` return 404

### Type errors di component
- Impor interface yang benar dari `src/models/dummy-data.models.ts`
- Pastikan types di template cocok dengan interface

## Next Steps

1. Gunakan data dummy ini di component manapun yang membutuhkan (dashboard, master data, sales, dll)
2. Sesuaikan interface jika struktur data berubah
3. Untuk production, replace dengan API backend atau database nyata
