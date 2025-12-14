# Integrasi Data Dummy - Kavling Management Pro

File data dummy telah diintegrasikan ke dalam aplikasi. Ada **2 cara** untuk menggunakan data ini:

## Opsi 1: Populate Database dengan Seeder (Rekomendasi untuk Testing/Demo)

### Step 1: Jalankan Seeder

```bash
php artisan db:seed --class=DummyDataSeeder
```

Ini akan:
- Membaca file `public/assets/data-dummy.json`
- Insert/update data projects dan lots ke dalam database
- Semua data siap diakses via API dan Views

### Step 2: Verifikasi Data

```bash
# Check projects
php artisan tinker
>>> App\Models\Project::count()
>>> App\Models\Lot::count()
```

### Kelebihan:
- Data stored di database, tidak di client
- Bisa di-filter, search, edit via Laravel ORM
- API endpoints langsung bisa serve data
- Views bisa iterate data dari Eloquent

---

## Opsi 2: Load Data dari JSON di Frontend (untuk Demo Cepat)

File JSON sudah tersedia di `public/assets/data-dummy.json`. Bisa diakses via JavaScript:

### Di Blade View (Server-side):

```php
<script>
  const dummyData = @json(json_decode(file_get_contents(public_path('assets/data-dummy.json')), true));
  console.log('Projects:', dummyData.projects);
  console.log('Lots:', dummyData.lots);
</script>
```

### Di JavaScript Frontend:

```javascript
// Import service
import { loadDummyData, getProjects, getLots, getProjectById } from '/js/services/dummy-data.js';

// Load all data
const data = await loadDummyData();
console.log(data); // { projects: [...], lots: [...] }

// Get specific data
const projects = await getProjects();
const lots = await getLots();
const project = await getProjectById(1);
const projectLots = await getLotsByProject(1);
```

### Atau via Fetch API:

```javascript
fetch('/assets/data-dummy.json')
  .then(r => r.json())
  .then(data => {
    console.log('Projects:', data.projects);
    console.log('Lots:', data.lots);
  })
  .catch(err => console.error('Error:', err));
```

---

## Struktur Data

### Projects
```json
{
  "id": 1,
  "name": "Kavling Harmoni Alam",
  "location": "Ciawi, Bogor",
  "notes": "Pengembangan tahap 1 seluas 2 hektar.",
  "total_units": 30,
  "sold_units": 29
}
```

### Lots
```json
{
  "id": 1,
  "project_id": 1,
  "block_number": "A-1",
  "area": 120,
  "base_price": 120000000,
  "status": "sold"
}
```

---

## File Locations

- **Data JSON**: `/public/assets/data-dummy.json` (accessible via browser at `/assets/data-dummy.json`)
- **JavaScript Service**: `/resources/js/services/dummy-data.js`
- **PHP Seeder**: `/database/seeders/DummyDataSeeder.php`

---

## Panduan Cepat untuk Testing

### Untuk Testing Data Display

1. **Populate database:**
   ```bash
   php artisan db:seed --class=DummyDataSeeder
   ```

2. **Kunjungi halaman:**
   - Projects: http://localhost:8000/projects
   - Lots: http://localhost:8000/kavling

3. **Semua data dari JSON sekarang muncul di tabel aplikasi**

### Untuk Testing Frontend/API Development

1. **Data sudah tersedia di `/assets/data-dummy.json`**

2. **Akses di JavaScript:**
   ```javascript
   // Di console browser
   fetch('/assets/data-dummy.json').then(r => r.json()).then(d => console.log(d));
   ```

---

## Catatan

- File `data-dummy.json` sudah di-copy ke `public/assets/` dan siap diakses
- Struktur JSON sudah match dengan schema database Laravel (Projects & Lots tables)
- Seeder akan mengupdate data yang sudah ada (tidak duplicate)
- Untuk clear data sebelum seed ulang: `php artisan db:seed --class=DummyDataSeeder --force`

---

## Troubleshooting

**Q: Seeder tidak nemu file JSON**
- Pastikan file ada di `public/assets/data-dummy.json`
- Cek permissions folder public

**Q: Data tidak muncul di tabel**
- Jalankan `php artisan migrate` untuk create tables
- Pastikan seeder sudah selesai tanpa error

**Q: Ingin reset data**
- Jalankan `php artisan migrate:fresh --seed --class=DummyDataSeeder`

---

## Next Steps

1. ✅ Data dummy siap digunakan
2. Customize seeder jika ada field tambahan di database
3. Setup API endpoints untuk serve data
4. Create views/components untuk display data
