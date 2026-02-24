<div align="center">
  <img src="public/img/logo.png" alt="SIMKOPKBM Logo" width="120">
  <h1>SIMKOPKBM</h1>
  <p><strong>Sistem Informasi Manajemen Koperasi Pendidikan KB/TK Baitusyukur Malang</strong></p>

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-4.x-FB70A9?style=for-the-badge&logo=livewire)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?style=for-the-badge&logo=tailwindcss)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)](https://php.net)

</div>

---

## 📌 Overview

**SIMKOPKBM** is a comprehensive school management system designed specifically for **KB/TK (Kelompok Bermain/Taman Kanak-kanak) Baitusyukur Malang**. It streamlines academic processes, student management, assessment reporting based on Kurikulum Merdeka, and financial tracking into a single integrated platform.

Built with the latest Laravel 12 ecosystem, it focuses on ease of use for teachers and administrative staff while providing a professional public presence for the school.

## ✨ Core Features

### 🏫 Academic & Student Management

- **Student Information System**: Complete student data lifecycle from enrollment to graduation.
- **PTK Management**: Data management for teachers and education staff (Pendidik dan Tenaga Kependidikan).
- **Classroom & Subject Control**: Flexible organization of education levels, classrooms, and subject mapping.

### 📝 Kurikulum Merdeka Assessment

- **PAUD/TK Specific Grading**: Narrative and competency-based assessments covering the 6 developmental aspects:
    - _Nilai Agama dan Budi Pekerti_
    - _Fisik-Motorik_
    - _Kognitif_
    - _Bahasa_
    - _Sosial-Emosional_
    - _Seni_
- **Competency Tracking**: Integrated tracking for **BB** (Belum Berkembang), **MB** (Mulai Berkembang), **BSH** (Berkembang Sesuai Harapan), and **SB** (Sangat Berkembang).
- **P5 & Extracurriculars**: Integrated assessment for the _Projek Penguatan Profil Pelajar Pancasila_ (P5) and extracurricular activities.

### 🖨️ Reporting & Finance

- **Automated Report Cards**: High-quality PDF generation for student progress reports (Raport).
- **Financial Module**: Management of billing, payments, and student fee discounts.

### 🌐 Public Website

- **Responsive CMS**: SEO-friendly management of school profile, news/articles, programs, and photo gallery.
- **SEO Optimized**: Dynamic `sitemap.xml`, meta tags, and slug-based URLs.

## 🛠️ Tech Stack

- **Backend**: [Laravel 12](https://laravel.com), [Livewire 4](https://livewire.laravel.com), [Volt](https://livewire.laravel.com/docs/volt), [Fortify](https://laravel.com/docs/fortify)
- **Frontend**: [Tailwind CSS 4](https://tailwindcss.com), [Flux UI](https://fluxui.dev), [TallStack UI](https://tallstackui.com), [Alpine.js](https://alpinejs.dev)
- **Tooling**: [Vite](https://vitejs.dev), [Pest](https://pestphp.com) (Testing), [Laravel Pint](https://laravel.com/docs/pint)
- **Infrastructure**: SQLite (Local), MySQL (Production), GitHub Actions (Autodeploy to Hostinger)

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Composer
- Node.js & NPM

### Installation

1. **Clone the repository**

    ```bash
    git clone https://github.com/doloop-bit/simkopkbm.git
    cd simkopkbm
    ```

2. **Setup environment**

    ```bash
    cp .env.example .env
    # Update your .env file as needed
    ```

3. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

4. **Initialize application**

    ```bash
    php artisan key:generate
    # For local SQLite setup
    touch database/database.sqlite
    php artisan migrate --seed
    ```

5. **Running locally**
    ```bash
    npm run dev
    ```
    _The application will be available at `http://localhost:8000` or your configured domain._

## 📅 Deployment

The project is configured with GitHub Actions (`.github/workflows/deploy.yml`) for automated deployment.

- **Main Branch**: `master` / `main`
- **Development Branch**: `develop`
- **Deployment Target**: Hostinger (via Rsync/SSH)

## 🤝 UI Guidelines & Patterns

This project follows a strict UI hierarchy to ensure consistency:

1. **Flux UI (Primary)**: Always check if a component is available in Flux Free first.
2. **TallStack UI (Secondary)**: Use for specialized components (Date Pickers, searchable selects) that Flux Free does not provide.
3. **Custom Tailwind**: Used for tables and unique layout elements.

For detailed technical documentation, please refer to the files in the `docs/` directory.

---

© 2026 KB/TK Baitusyukur Malang. Built with ❤️ by doloop-bit.
