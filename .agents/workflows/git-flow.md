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

4. **Deployment ke Demo (Testing Site)**
    - Jika sudah OK secara lokal, gabungkan ke `develop` dan push.

    ```powershell
    git add .
    git commit -m "feat: deskripsi singkat fitur"
    git checkout develop
    git merge feature/nama-fitur
    git push origin develop
    ```

    _Catatan: GitHub Actions akan otomatis men-deploy branch develop ke situs Demo._

5. **Deployment ke Produksi (Live Site)**
    - **PENTING**: Langkah ini HANYA boleh dilakukan jika user sudah memberikan instruksi eksplisit (seperti: "Deploy ke produksi" atau "Merge ke main").
    - Jangan pernah menyarankan atau melakukan merge ke `main` secara otomatis sebelum user memverifikasi situs Demo.

    ```powershell
    git checkout main
    git merge develop
    git push origin main
    ```

    _Catatan: GitHub Actions akan otomatis men-deploy branch main ke situs Produksi._

6. **Pembersihan**
    - Hapus branch fitur yang sudah selesai untuk menjaga kerapihan repositori.
    ```powershell
    git branch -d feature/nama-fitur
    git push origin --delete feature/nama-fitur
    ```
