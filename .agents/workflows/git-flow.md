---
description: Alur Pengembangan Fitur (Git Flow) untuk SimkopKBM
---

Pahami alur kerja berikut untuk setiap penambahan fitur atau perbaikan bug agar konsisten dengan deployment Demo (develop) dan Produksi (main).

// turbo-all

1. **Persiapan Branch**
    - Pastikan berada di branch `develop` terbaru.
    - Buat branch baru dengan format: `feature/nama-fitur` atau `fix/nama-bug`.

    ```powershell
    git checkout develop
    git pull origin develop
    git checkout -b feature/nama-fitur
    ```

2. **Pengembangan & Formating**
    - Lakukan perubahan kode.
    - Sebelum commit, jalankan linter agar kode tetap rapi:

    ```powershell
    vendor/bin/pint --dirty --format agent
    ```

3. **Pengujian Lokal**
    - Jalankan unit/feature test yang relevan:

    ```powershell
    php artisan test --compact
    ```

4. **Penyelesaian Lokal**
    - Jika sudah OK secara lokal, gabungkan ke `develop`.

    ```powershell
    git add .
    git commit -m "feat: deskripsi singkat fitur"
    git checkout develop
    git merge feature/nama-fitur
    ```

    _Catatan: Jangan lakukan push ke origin. User akan menarik perubahan tersebut secara mandiri._

5. **Deployment ke Produksi (Saran Saja)**
    - Jika user ingin deploy ke main secara mandiri, mereka bisa menjalankan:

    ```powershell
    git checkout main
    git merge develop
    git push origin main
    ```

    _Catatan: AI tidak akan melakukan langkah ini. User yang akan mengeksekusinya._

6. **Pembersihan Lokal**
    - Hapus branch fitur yang sudah selesai.
    ```powershell
    git branch -d feature/nama-fitur
    ```
