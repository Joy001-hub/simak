# Quick Start: Menggunakan Dummy Data di Aplikasi

## File yang Telah Ditambahkan

```
src/
├── assets/
│   └── data-dummy.json              # File data dummy (dari backup JSON)
├── services/
│   └── dummy-data.service.ts        # Service untuk load & manage dummy data
├── models/
│   └── dummy-data.models.ts         # TypeScript interfaces
└── components/
    └── dummy-data-example/          # Example component
        ├── dummy-data-example.component.ts
        ├── dummy-data-example.component.html
        └── dummy-data-example.component.css
```

## Setup (Jika belum ada HttpClientModule)

Tambahkan `HttpClientModule` ke app module/component Anda:

```typescript
import { HttpClientModule } from '@angular/common/http';

@NgModule({
  imports: [
    // ... other imports
    HttpClientModule
  ]
})
export class AppModule { }
```

Atau di component (jika standalone):

```typescript
import { HttpClientModule } from '@angular/common/http';
import { DummyDataExampleComponent } from './components/dummy-data-example/dummy-data-example.component';

@Component({
  selector: 'app-root',
  imports: [HttpClientModule, DummyDataExampleComponent],
  template: `<app-dummy-data-example></app-dummy-data-example>`
})
export class AppComponent { }
```

## Cara Menggunakan di Component Anda

### Opsi 1: Gunakan Example Component Langsung

Di template Anda, tambahkan:

```html
<app-dummy-data-example></app-dummy-data-example>
```

Ini akan menampilkan dashboard dengan semua data dari JSON.

### Opsi 2: Inject Service ke Component Anda Sendiri

```typescript
import { Component, OnInit } from '@angular/core';
import { DummyDataService } from '../../services/dummy-data.service';

@Component({
  selector: 'app-my-component',
  template: `
    <div>
      <h1>{{ companyProfile?.nama }}</h1>
      <ul>
        <li *ngFor="let project of projects">
          {{ project.name }}
        </li>
      </ul>
    </div>
  `
})
export class MyComponent implements OnInit {
  companyProfile: any;
  projects: any[] = [];

  constructor(private dummyDataService: DummyDataService) {}

  ngOnInit() {
    this.dummyDataService.data$.subscribe(data => {
      if (data) {
        this.companyProfile = data.companyProfile;
        this.projects = data.projects;
      }
    });
  }
}
```

## Struktur Data JSON

File `data-dummy.json` memiliki struktur:

```json
{
  "companyProfile": { /* Company info */ },
  "projects": [ /* Array of projects */ ],
  "lots": [ /* Array of lots/kavling */ ],
  "sales": [ /* Array of sales records */ ],
  "salesmen": [ /* Array of salesmen */ ],
  "customers": [ /* Array of customers */ ],
  "...": "... dan data lainnya"
}
```

Lihat `DUMMY_DATA.md` untuk detail lengkap struktur data.

## Testing

1. Build aplikasi:
   ```bash
   npm run build:web
   ```

2. Jalankan aplikasi (dev atau production exe)

3. Buka Console di Developer Tools (F12)

4. Cari log "Dummy data loaded" - ini menunjukkan data berhasil dimuat

5. Component atau page akan menampilkan data dari JSON

## Integrasi dengan Component Existing

Untuk menggunakan dummy data di component yang sudah ada (misalnya `dashboard.component.ts`):

```typescript
// Tambahkan import
import { DummyDataService } from '../services/dummy-data.service';

// Inject service di constructor
constructor(private dummyDataService: DummyDataService) {}

// Di ngOnInit, subscribe ke data
ngOnInit() {
  this.dummyDataService.data$.subscribe(data => {
    if (data) {
      // Gunakan data untuk populate dashboard
      this.renderData(data);
    }
  });
}
```

## Troubleshooting

**Q: Data tidak tampil**
- Periksa Console (F12) untuk error
- Pastikan HttpClientModule sudah di-import
- Pastikan file `src/assets/data-dummy.json` ada

**Q: 404 when loading data-dummy.json**
- Pastikan file path benar: `src/assets/data-dummy.json`
- Cek base href di `index.html`

**Q: TypeScript error**
- Import interface yang benar dari `src/models/dummy-data.models.ts`

## Produksi

Ketika siap untuk production:

1. Replace data dummy dengan API backend:
   ```typescript
   // Ganti di service
   // this.http.get<DummyData>('assets/data-dummy.json')
   // dengan:
   // this.http.get<DummyData>('/api/data')
   ```

2. Hapus file `src/assets/data-dummy.json` jika tidak perlu

3. Update interface sesuai dengan response API backend

## Referensi

- `src/services/dummy-data.service.ts` - Service implementation
- `src/models/dummy-data.models.ts` - Data models
- `src/components/dummy-data-example/` - Example component
- `DUMMY_DATA.md` - Dokumentasi lengkap
