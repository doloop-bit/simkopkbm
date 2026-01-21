# Fitur Report Card (Rapor)

## Deskripsi
Fitur Report Card memungkinkan admin untuk membuat, mengelola, dan mengekspor rapor siswa dalam format PDF. Nilai dihitung otomatis dari data penilaian yang sudah diinput.

## Komponen yang Dibuat

### 1. Model & Database
- **Model**: `App\Models\ReportCard`
- **Migration**: `2026_01_21_125659_create_report_cards_table`
- **Tabel**: `report_cards`

Struktur tabel:
```
- id (bigint, primary key)
- student_id (foreign key → users)
- classroom_id (foreign key → classrooms)
- academic_year_id (foreign key → academic_years)
- scores (json) - Nilai per mata pelajaran
- gpa (decimal 5,2) - Grade Point Average (IPK)
- semester (string) - Semester 1 atau 2
- teacher_notes (text) - Catatan dari guru
- principal_notes (text) - Catatan dari kepala sekolah
- status (string) - draft, finalized, printed
- created_at, updated_at (timestamps)
```

Unique constraint: `(student_id, classroom_id, academic_year_id, semester)`

### 2. Livewire Volt Component
**Path**: `resources/views/livewire/admin/report-card/create.blade.php`

Fitur:
- Pilih tahun ajaran, kelas, dan semester
- Pilih siswa yang akan dibuat rapornya (multi-select)
- Input catatan guru dan kepala sekolah (opsional)
- Tombol "Buat Rapor" untuk generate rapor
- Preview rapor dalam modal
- Export ke PDF dari preview

**Methods:**
- `generateReportCards()` - Generate rapor untuk siswa terpilih
- `previewReportCard($studentProfileId)` - Tampilkan preview rapor
- `closePreview()` - Tutup modal preview
- `exportPdf($reportCardId)` - Export rapor ke PDF

### 3. PDF Template
**Path**: `resources/views/pdf/report-card.blade.php`

Template PDF berisi:
- Header dengan judul "RAPOR SISWA" dan informasi tahun ajaran/semester
- Data siswa (nama, NIS, NISN, kelas, tahun ajaran)
- Tabel nilai per mata pelajaran dengan kolom: No, Mata Pelajaran, Nilai, Keterangan (Grade A/B/C/D)
- Box IPK (GPA) dengan format besar dan menonjol
- Catatan guru (jika ada)
- Catatan kepala sekolah (jika ada)
- Area tanda tangan (Guru Kelas, Orang Tua/Wali, Kepala Sekolah)
- Footer dengan informasi sistem dan waktu cetak

### 4. Routes
**Path**: `routes/report-card.php`

Routes:
```
GET /admin/report-card/create
  - Halaman pembuatan rapor
  - Middleware: auth, verified
  - Name: admin.report-card.create
```

### 5. Factory
**Path**: `database/factories/ReportCardFactory.php`

Untuk testing dan seeding dengan data dummy.

### 6. Tests
**Path**: `tests/Feature/ReportCardTest.php`

Test cases:
- Admin dapat mengakses halaman pembuatan rapor
- Rapor dapat dibuat untuk siswa
- Rapor dapat diexport ke PDF
- Siswa dapat melihat rapor mereka sendiri
- Non-admin tidak dapat mengakses halaman pembuatan rapor

## Cara Penggunaan

### 1. Membuat Rapor
1. Login sebagai admin
2. Klik menu "Buat Rapor" di sidebar (grup "Penilaian & Raport")
3. Pilih tahun ajaran dari dropdown
4. Pilih kelas dari dropdown (akan otomatis filter berdasarkan tahun ajaran)
5. Pilih semester (1 atau 2)
6. Pilih siswa yang akan dibuat rapornya (checkbox multi-select)
7. (Opsional) Masukkan catatan guru di field "Catatan Guru"
8. (Opsional) Masukkan catatan kepala sekolah di field "Catatan Kepala Sekolah"
9. Klik tombol "Buat Rapor"
10. Sistem akan menampilkan notifikasi sukses

### 2. Preview Rapor
Setelah rapor dibuat, admin dapat melihat preview dengan:
1. Klik tombol preview (jika ada) atau buat rapor baru
2. Modal akan menampilkan:
   - Informasi siswa (nama, NIS, kelas, IPK)
   - Tabel nilai per mata pelajaran
   - Catatan guru dan kepala sekolah (jika ada)

### 3. Export ke PDF
1. Dari preview modal, klik tombol "Export PDF"
2. File PDF akan diunduh dengan nama format: `rapor-[nama-siswa]-[semester].pdf`
3. PDF siap untuk dicetak atau disimpan

## Kalkulasi Nilai

### Proses Kalkulasi:
1. **Nilai per Mata Pelajaran**: Rata-rata dari semua kategori penilaian untuk mata pelajaran tersebut
2. **IPK (GPA)**: Rata-rata dari semua nilai mata pelajaran

### Contoh:
```
Matematika:
  - Kategori 1: 85
  - Kategori 2: 90
  - Kategori 3: 88
  Rata-rata: (85 + 90 + 88) / 3 = 87.67

Bahasa Indonesia:
  - Kategori 1: 80
  - Kategori 2: 85
  - Kategori 3: 82
  Rata-rata: (80 + 85 + 82) / 3 = 82.33

IPK: (87.67 + 82.33) / 2 = 85.00
```

## Relasi Model

```
ReportCard
├── belongsTo(User) - student
├── belongsTo(Classroom)
└── belongsTo(AcademicYear)

User
├── hasMany(ReportCard) - reportCards
└── hasOneThrough(StudentProfile) - studentProfile

StudentProfile
└── hasMany(ReportCard) - via User relationship
```

## Authorization

Authorization di-handle langsung di Volt component menggunakan `$this->authorize()`:
- **Admin**: Dapat membuat, melihat, dan export semua rapor
- **Student**: Hanya dapat melihat dan export rapor mereka sendiri
- **Guru**: Tidak dapat mengakses halaman pembuatan rapor

## Integrasi dengan Sidebar

Menu "Buat Rapor" sudah ditambahkan ke sidebar:
- **Lokasi**: Grup "Penilaian & Raport"
- **Icon**: document-text
- **Route Name**: admin.report-card.create
- **Middleware**: auth, verified

## Catatan Teknis

- Menggunakan Livewire Volt (class-based component)
- PDF generation menggunakan Barryvdh DomPDF
- Scores disimpan dalam format JSON untuk fleksibilitas
- Unique constraint mencegah duplikasi rapor untuk siswa yang sama di kelas/tahun/semester yang sama
- Middleware `auth` dan `verified` melindungi semua routes

## Fitur yang Dapat Dikembangkan

- [ ] Finalize rapor (ubah status dari draft ke finalized)
- [ ] Print rapor langsung dari browser
- [ ] Bulk export rapor untuk satu kelas
- [ ] Email rapor ke orang tua
- [ ] Signature digital untuk guru dan kepala sekolah
- [ ] History/versioning rapor
- [ ] Approval workflow sebelum finalize
