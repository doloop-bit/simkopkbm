# SIMKOPKBM - Knowledge Base & Technical Specification

> **This document serves as the primary reference for understanding and developing the SIMKOPKBM application.**
> Always consult this document before making changes to ensure consistency.

---

## 📚 Table of Contents

1. [Application Overview](#-application-overview)
2. [Technical Stack](#-technical-stack)
3. [UI Library Guide](#-ui-library-guide)
4. [Directory Structure](#-directory-structure)
5. [Domain Knowledge](#-domain-knowledge-kurikulum-merdeka)
6. [Development Patterns](#-development-patterns)
7. [Route & Navigation](#-route--navigation)
8. [Database Guidelines](#-database-guidelines)
9. [Testing & Tools](#-testing--tools)
10. [Implementation Status](#-implementation-status)
11. [Future Plans](#-future-plans)

---

## 📌 Application Overview

### **What is SIMKOPKBM?**

**Sistem Informasi Manajemen PKBM (Pusat Kegiatan Belajar Masyarakat)** - A comprehensive school management system for PKBM Pusat Kegiatan Belajar Masyarakat, with features for student management, academic assessment, financial tracking, and public website.

### **Core Features**

| Feature                    | Description                                            |
| -------------------------- | ------------------------------------------------------ |
| **Student Management**     | Student data, enrollment, profiles                     |
| **Student Registration**   | Online registration form, admin review, enrollment     |
| **Academic Management**    | Years, levels, classrooms, subjects                    |
| **Assessment System**      | Grades (numeric) & Competency (Kurikulum Merdeka-PAUD) |
| **Report Card Generation** | PDF report cards                                       |
| **Financial Management**   | Billing, payments, transactions                        |
| **PTK Management**         | Teacher and staff data                                 |
| **Public Website**         | School profile, news, gallery, programs, contact form  |
| **SEO Optimization**       | Sitemap.xml, meta tags, slug-based URLs                |

---

## 🛠️ Technical Stack

> **Last verified:** 2026-01-22 via `mcp_laravel-boost_application-info`

### **Backend**

| Technology      | Version          | Purpose                  | Notes                                      |
| --------------- | ---------------- | ------------------------ | ------------------------------------------ |
| PHP             | **8.4.11**       | Runtime                  | -                                          |
| Laravel         | **12.48.1**      | Framework                | -                                          |
| Livewire        | **4.0.0**        | Reactive Components      | ⚠️ Major upgrade from v3                   |
| Livewire Volt   | **1.10.1**       | Single-file components   | Optional with Livewire 4 (see notes below) |
| Livewire Blaze  | **1.0.0-beta.1** | Performance optimization | New addition                               |
| Laravel Fortify | **1.33.0**       | Authentication           | -                                          |

#### 📝 Notes on Livewire 4 vs Volt

**Livewire 4 Native Single-File Components:**

- Livewire 4 now natively supports combining logic and HTML in single files
- Volt is **optional** but still useful for its syntactic sugar and functional API
- Current codebase uses **Volt pattern** for consistency

**Recommended approach:**

```blade
{{-- Continue using Volt pattern for new components --}}
<?php
new #[Layout('components.admin.layouts.app')] class extends Component {
    // Component logic here
}; ?>

<div>
    {{-- View content here --}}
</div>
```

### **Frontend**

| Technology   | Version | Purpose            | Notes                                 |
| ------------ | ------- | ------------------ | ------------------------------------- |
| Tailwind CSS | **4.x** | Styling            | v4 with new config syntax             |
| Mary UI      | **2.x** | Primary UI Library | Based on DaisyUI                      |
| DaisyUI      | **5.x** | CSS Components     | Used by Mary UI                       |
| Alpine.js    | **3.x** | JavaScript         | Included via Livewire                 |
| Vite         | **6.x** | Asset Bundling     | -                                     |

### **Database**

| Environment           | Engine       | Notes                         |
| --------------------- | ------------ | ----------------------------- |
| **Local/Development** | SQLite 3.49+ | URL: `http://simkopkbm.test1` |
| **Production**        | MySQL 8.x    | Full RDBMS features           |

⚠️ **Cross-Database Compatibility Notes:**

- Avoid SQLite-specific or MySQL-specific SQL syntax
- Use Laravel Query Builder or Eloquent instead of raw SQL
- Test migrations work on both engines before pushing
- For JSON columns, use `$casts` in models for consistency

### **Additional Packages**

| Package                      | Version | Purpose             |
| ---------------------------- | ------- | ------------------- |
| `maatwebsite/excel`          | 3.1.x   | Excel import/export |
| `intervention/image-laravel` | 1.5.x   | Image processing    |
| `barryvdh/laravel-dompdf`    | -       | PDF generation      |

---

## 🎨 UI Library Guide

### **Component Library**

This project uses **Mary UI** as the primary component library. It is built on top of **DaisyUI** and provides a rich set of reactive components for Livewire.

### **Mary UI - Core Components**

```blade
{{-- Header & Layout --}}
<x-header title="Page Title" subtitle="Description text" separator>
    <x-slot:actions>
        <x-button label="Add New" icon="o-plus" class="btn-primary" />
    </x-slot:actions>
</x-header>

{{-- Cards --}}
<x-card title="Card Title" shadow separator>
    Content here
</x-card>

{{-- Buttons --}}
<x-button label="Save" icon="o-check" class="btn-primary" />
<x-button label="Delete" icon="o-trash" class="btn-error" />

{{-- Form Inputs --}}
<x-input label="Name" wire:model="name" />
<x-select label="Option" :options="$options" wire:model="option" />
<x-textarea label="Description" wire:model="description" hint="Max 255 chars" />
<x-choices label="Multiple select" wire:model="selected_ids" :options="$users" />

{{-- Tables --}}
<x-table :headers="$headers" :rows="$rows" striped with-pagination />

{{-- Feedback --}}
<x-badge label="Active" class="badge-success" />
<x-icon name="o-check-circle" class="text-success" />

{{-- Modals --}}
<x-modal wire:model="showModal" title="Modal Title">
    <div>Modal content</div>
    <x-slot:actions>
        <x-button label="Cancel" @click="$wire.showModal = false" />
        <x-button label="Confirm" class="btn-primary" />
    </x-slot:actions>
</x-modal>

{{-- Toast notifications (using Trait) --}}
$this->success('Saved successfully.');
$this->error('Something went wrong.');
```

### **Iconography**

We use **Heroicons** via Mary UI's `x-icon` component. Use the `o-*` prefix for outline icons (Solid icons are also available with `s-*`).

```blade
<x-icon name="o-user" class="w-5 h-5" />
```

### **Page Structure Template**

```blade
<div class="p-6">
    <x-header title="Page Title" subtitle="Page description." separator>
        <x-slot:actions>
            <x-button label="Add New" icon="o-plus" class="btn-primary" spinner />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        {{-- Filters/Content --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <x-input label="Search" wire:model.live="search" icon="o-magnifying-glass" />
        </div>

        <x-table :headers="$headers" :rows="$rows" striped />
    </x-card>
</div>
```

### **Color Conventions**

| Purpose        | Light Mode        | Dark Mode              |
| -------------- | ----------------- | ---------------------- |
| Background     | `bg-white`        | `dark:bg-zinc-900`     |
| Card/Panel     | `bg-zinc-50`      | `dark:bg-zinc-800`     |
| Text Primary   | `text-zinc-900`   | `dark:text-white`      |
| Text Secondary | `text-zinc-600`   | `dark:text-zinc-400`   |
| Border         | `border-zinc-200` | `dark:border-zinc-700` |

---

## 📁 Directory Structure

### **Key Directories**

````
app/
├── Models/                    # Eloquent models
├── Imports/                   # Excel imports
├── Exports/                   # Excel exports
├── Http/Controllers/          # (Minimal - using Livewire)

resources/
├── views/
│   ├── livewire/
```markdown
│   │   ├── admin/             # Admin panel pages
│   │   │   ├── academic/      # Academic management
│   │   │   ├── assessments/   # Assessment forms
│   │   │   ├── data-master/   # Master data management
│   │   │   ├── financial/     # Financial management
│   │   │   ├── report-card/   # Report card generation
│   │   │   └── ...
````

│ │ └── public/ # Public website pages
│ └── components/
│ └── admin/
│ ├── layouts/ # Admin layouts
│ ├── sidebar.blade.php # Navigation sidebar
│ └── header.blade.php # Header component

routes/
├── web.php # Main routes (imports others)
├── academic.php # Academic routes
├── students.php # Student routes
├── assessments.php # Assessment routes
└── ...

database/
├── migrations/ # Database migrations
├── seeders/ # Database seeders
└── database.sqlite # SQLite database file (dev only)

```

---

## 📊 Domain Knowledge: Kurikulum Merdeka

### **Competency Levels for PAUD (Capaian Kompetensi)**

| Code    | Name                      | Description                                      |
| ------- | ------------------------- | ------------------------------------------------ |
| **BB**  | Belum Berkembang          | Student has not shown expected achievement       |
| **MB**  | Mulai Berkembang          | Student is starting to show expected achievement |
| **BSH** | Berkembang Sesuai Harapan | Student shows achievement as expected            |
| **SB**  | Sangat Berkembang         | Student exceeds expected achievement             |

### **Learning Phases (Fase Pembelajaran)**

| Phase | Paket         | Class Level (Tingkat Kelas) |
| ----- | ------------- | --------------------------- |
| **-** | PAUD          | -                           |
| **A** | Paket A (SD)  | Kelas 1-2                   |
| **B** | Paket A (SD)  | Kelas 3-4                   |
| **C** | Paket A (SD)  | Kelas 5-6                   |
| **D** | Paket B (SMP) | Kelas 1-3                   |
| **E** | Paket C (SMA) | Kelas 1                     |
| **F** | Paket C (SMA) | Kelas 2-3                   |

### **CP → TP Hierarchy (Implemented)**

```

Level (Paket A/B/C) → has phase_map JSON
└── Classroom → has class_level (tingkat kelas)
└── resolves to Phase (Fase A-F) via level.phase_map
└── CP (learning_achievements) per Fase per Subject
└── TP (subject_tps) per CP

````

**Key tables:**

- `levels.phase_map` — JSON mapping class_level → phase, e.g. `{"1": "A", "2": "A", "3": "B"}`
- `classrooms.class_level` — Integer representing "tingkat kelas" within the paket
- `learning_achievements` — CP per subject per phase (`subject_id` + `phase`)
- `subject_tps` — TP under a CP (`learning_achievement_id`)

**Phase resolution flow (for grading form):**

1. Teacher selects Classroom → `Classroom->getPhase()` resolves the phase
2. Based on phase + subject → find CP (learning_achievement)
3. Load TPs from that CP → show in dropdown



### **PAUD Developmental Aspects (6 Aspek)**

1. **Nilai Agama dan Budi Pekerti** - Religious values and character
2. **Fisik-Motorik** - Physical-motor (gross & fine motor)
3. **Kognitif** - Cognitive development
4. **Bahasa** - Language development
5. **Sosial-Emosional** - Social-emotional development
6. **Seni** - Arts and creativity

### **Assessment Types by Level**

| Level          | Competency Assessment  | P5 Assessment | Extracurricular | Notes                                                  |
| -------------- | ---------------------- | ------------- | --------------- | ------------------------------------------------------ |
| **PAUD**       | BB/MB/BSH/SB           | BB/MB/BSH/SB  | -               | PAUD has no extracurricular                            |
| **SD/SMP/SMA** | Numeric grades (0-100) | BB/MB/BSH/SB  | Descriptive     | P5 uses BB/MB/BSH/SB, Ekskul uses Baik/Sangat Baik/etc |

**Additional Notes:**

- **PAUD Developmental Assessment**: Uses narrative descriptions (6 aspects: Agama, Fisik-Motorik, Kognitif, Bahasa, Sosial-Emosional, Seni)
- **Competency Assessment**: PAUD-only feature (filtered by `education_level = 'PAUD'`)

- **Extracurricular Assessment**: Uses descriptive scale (Sangat Baik, Baik, Cukup, Perlu Ditingkatkan).

---

## 🔄 Development Patterns

### **Livewire Volt Component Pattern**

```blade
<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.admin.layouts.app')] class extends Component {
    // Properties
    public ?int $model_id = null;
    public array $data = [];

    // Lifecycle
    public function mount(): void
    {
        // Initialize data
    }

    // Watchers
    public function updatedModelId(): void
    {
        // React to changes
    }

    // Actions
    public function save(): void
    {
        // Validate and save
        $this->success('Saved successfully.');
    }

    // Computed data for view
    public function with(): array
    {
        return [
            'items' => Model::all(),
        ];
    }
}; ?>

<div class="p-6">
    {{-- View content --}}
</div>
```

### **Loading Related Data Pattern**

```php
// When dropdown changes, load related data
public function updatedClassroomId(): void
{
    $this->loadStudents();
}

public function loadStudents(): void
{
    if (!$this->classroom_id) {
        $this->students = [];
        return;
    }

    $this->students = User::where('role', 'siswa')
        ->whereHas('latestProfile.profileable', fn($q) =>
            $q->where('classroom_id', $this->classroom_id)
        )
        ->get();
}
```

### **Bulk Save Pattern**

```php
public function save(): void
{
    DB::transaction(function () {
        foreach ($this->data as $id => $value) {
            Model::updateOrCreate(
                ['unique_key' => $id],
                ['value' => $value]
            );
        }
    });

    $this->success('Data saved successfully.');
}
```

### **Shared Component Architecture (Multi-Role)**

**Problem:** Using a single component with dynamic `getLayout()` detection often fails to render the correct sidebar/navbar due to race conditions or route ambiguity.

**Solution:** Use **Explicit Context Wrappers**.

1. **Logic**: Extract shared logic into a `Trait`.
2. **UI**: Extract shared Blade markup into a `_partial`.
3. **Context**: Create two separate Volt components (Admin/Teacher) that use the Trait + Partial but **explicitly define their layout**.

**Example:**

**1. The Logic (Trait)**

```php
// app/Traits/Assessments/HandlesGrading.php
trait HandlesGrading {
    public function save() { ... }
}
```

**2. The UI (Partial)**

```blade
// resources/views/livewire/shared/_partials/grading-ui.blade.php
<div>
    <!-- Table Markup -->
</div>
```

**3. The Components (Explicit Layouts)**

```php
// Teacher Component
new #[Layout('components.teacher.layouts.app')] class extends Component {
    use HandlesGrading;
}
```

```php
// Admin Component
new #[Layout('components.admin.layouts.app')] class extends Component {
    use HandlesGrading;
}
```

---

## 🔗 Route & Navigation

### **Route Naming Pattern**

```
{prefix}.{module}.{action}
```

**Examples:**

| Route Name                     | URL                             | Purpose               |
| ------------------------------ | ------------------------------- | --------------------- |
| `dashboard`                    | `/admin/dashboard`              | Dashboard             |
| `students.index`               | `/admin/students`               | Student list          |
| `academic.grades`              | `/admin/academic/grades`        | Numeric grades        |
| `admin.assessments.competency` | `/admin/assessments/competency` | Competency assessment |
| `admin.report-card.create`     | `/admin/report-card/create`     | Create report card    |

### **Route File Mapping**

| File                           | Routes                                                                             |
| ------------------------------ | ---------------------------------------------------------------------------------- |
| `routes/web.php`               | Main routes (imports all other route files)                                        |
| `routes/public.php`            | Public website routes (homepage, about, news, programs, gallery, contact, sitemap) |
| `routes/academic.php`          | Academic module routes                                                             |
| `routes/students.php`          | Student module routes                                                              |
| `routes/assessments.php`       | Assessment module routes (4 assessment forms)                                      |
| `routes/report-card.php`       | Report card routes                                                                 |
| `routes/financial.php`         | Financial module routes                                                            |
| `routes/news.php`              | Admin news management                                                              |
| `routes/programs.php`          | Admin programs management                                                          |
| `routes/gallery.php`           | Admin gallery management                                                           |
| `routes/school-profile.php`    | Admin school profile management                                                    |
| `routes/contact-inquiries.php` | Admin contact inquiries                                                            |
| `routes/ptk.php`               | PTK (Teacher & Staff) management                                                   |
| `routes/settings.php`          | System settings                                                                    |
| `routes/teacher.php`           | Teacher-specific routes                                                            |

### **Sidebar Navigation Groups**

1. **Dashboard** - Main dashboard
2. **Data Master** - Siswa, PTK
3. **Akademik** - Years, Levels, Classrooms, Subjects, Assignments, Attendance (6 items)
4. **Penilaian & Raport** - Grades, Competency Assessment, P5 Assessment, Extracurricular Assessment, Attendance Summary, Report Card Generator (6 items)
5. **Keuangan** - Payments, Billings, Discounts, Categories (4 items)
6. **Konten Web** - School Profile, News, Gallery, Programs, Contact Inquiries (5 items)
7. **Laporan** - Reports and analytics

### **Adding New Menu Items**

Edit: `resources/views/components/admin/sidebar.blade.php`

```blade
<flux:sidebar.item
    icon="icon-name"
    :href="route('route.name')"
    :current="request()->routeIs('route.*')"
    wire:navigate.hover
>
    {{ __('Menu Label') }}
</flux:sidebar.item>
```

---

## 🗄️ Database Guidelines

### **Database Overview**

- **Total Migrations**: 40 migration files
- **Total Models**: 35 Eloquent models
- **Key Tables**: Users, Profiles (polymorphic), Academic data, Assessments, Financial, Public website content

### **Naming Conventions**

- Tables: `snake_case`, plural (e.g., `academic_years`, `student_profiles`)
- Columns: `snake_case` (e.g., `created_at`, `academic_year_id`)
- Foreign Keys: `{related_table_singular}_id` (e.g., `user_id`, `classroom_id`)

### **Standard Columns**

Every table should have:

- `id` - Primary key (auto-increment)
- `created_at` - Timestamp
- `updated_at` - Timestamp

### **Foreign Key Pattern**

```php
$table->foreignId('classroom_id')->constrained()->onDelete('cascade');
```

### **Enum Pattern**

```php
$table->enum('status', ['active', 'inactive'])->default('active');
$table->enum('competency_level', ['BB', 'MB', 'BSH', 'SB']);
$table->enum('education_level', ['PAUD', 'SD', 'SMP', 'SMA']);
```

### **Kurikulum Merdeka Tables**

| Table                    | Purpose                                       |
| ------------------------ | --------------------------------------------- |
| `competency_assessments` | Student competency assessments (BB/MB/BSH/SB) |

|
| `extracurricular_activities` | Extracurricular activity definitions |
| `extracurricular_assessments` | Student extracurricular assessments |
| `developmental_aspects` | PAUD developmental aspect definitions |
| `developmental_assessments` | PAUD student developmental assessments |
| `report_attendances` | Attendance summary for report cards |
| `learning_achievements` | Learning achievement records |

### **Cross-Database Compatibility**

```php
// ❌ DON'T use database-specific syntax
DB::raw('JSON_EXTRACT(data, "$.field")') // MySQL only

// ✅ DO use Eloquent casts
protected $casts = [
    'data' => 'array',
];

// ❌ DON'T use SQLite-specific functions
// ✅ DO use Query Builder for complex queries
```

---

## 🧪 Testing & Tools

### **MCP Tools Available**

```php
// Check app info
mcp_laravel-boost_application-info()

// Query database
mcp_laravel-boost_database-query("SELECT * FROM users LIMIT 5")

// View schema
mcp_laravel-boost_database-schema()

// List routes
mcp_laravel-boost_list-routes()

// Execute PHP code
mcp_laravel-boost_tinker("return User::count();")

// Check errors
mcp_laravel-boost_last-error()
```

### **Using Tinker for Quick Tests**

```php
// Test model creation
$assessment = CompetencyAssessment::create([
    'student_id' => 2,
    'subject_id' => 1,
    'academic_year_id' => 1,
    'classroom_id' => 1,
    'semester' => '1',
    'competency_level' => 'BSH',
    'achievement_description' => 'Test description',
]);

// Test relationships
$student = User::find(2);
$assessments = CompetencyAssessment::where('student_id', 2)->with('subject')->get();
```

### **Quick Commands**

```bash
# Clear all caches
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# Run specific seeder
php artisan db:seed --class=SeederName

# Check routes
php artisan route:list --path=admin

# Create migration
php artisan make:migration create_table_name_table

# Create model
php artisan make:model ModelName

# Create Volt component
php artisan make:volt path/component-name --class
```

---

## 🌐 Public Website Structure

### **Public Pages**

| Route                        | File                      | Description                                 |
| ---------------------------- | ------------------------- | ------------------------------------------- |
| `/`                          | `public.homepage`         | Homepage with hero, stats, programs preview |
| `/tentang-kami`              | `public.about.index`      | About school page                           |
| `/struktur-organisasi`       | `public.about.staff`      | Staff and organizational structure          |
| `/fasilitas`                 | `public.about.facilities` | School facilities                           |
| `/program-pendidikan`        | `public.programs.index`   | Programs listing                            |
| `/program-pendidikan/{slug}` | `public.programs.show`    | Program detail page                         |
| `/berita`                    | `public.news.index`       | News/articles listing                       |
| `/berita/{slug}`             | `public.news.show`        | News article detail                         |
| `/galeri`                    | `public.gallery`          | Photo gallery                               |
| `/kontak`                    | `public.contact`          | Contact form                                |
| `/sitemap.xml`               | `sitemap`                 | SEO sitemap (XML)                           |

### **SEO Implementation**

- **Sitemap.xml**: Dynamically generated from database (school profile, news, programs)
- **Slug-based URLs**: News and programs use SEO-friendly slugs
- **Meta Tags**: Each page has proper title and description
- **Semantic HTML**: Proper heading hierarchy and semantic elements

### **Admin CMS**

All public content is manageable via admin panel:

- School Profile editor
- News/Articles CRUD
- Gallery photo management with WebP optimization
- Programs CRUD
- Contact inquiry viewer

---

## 🔮 Future Plans

### **Fitur Surat (Letter Feature)**

> _Planned: Official letter generation for school administration_

**Requirements:**

- Create, edit, and print official letters
- Pre-defined templates for common letter types
- WYSIWYG editor for rich text editing

**WYSIWYG Editor Options (To Be Decided):**

| Option       | Notes                                         |
| ------------ | --------------------------------------------- |
| **Tiptap**   | Modern, Livewire-friendly, good customization |
| **TinyMCE**  | Feature-rich, widely used                     |
| **Quill**    | Lightweight, open-source                      |
| **CKEditor** | Enterprise features, collaborative editing    |

**Recommended: Tiptap** - Best integration with Laravel Livewire ecosystem.

### **Other Future Enhancements**

- [ ] SMS/WhatsApp notification integration
- [ ] Parent portal (readonly access to student data)
- [ ] Multi-tenant support (multiple schools)
- [ ] Mobile app (React Native or Flutter)

---

## 💻 Environment Information

- **Local Development URL**: `http://simkopkbm.test`
- **Web Server**: Laragon (Windows)
- **Database Engine**: SQLite (Local)
- **Test Credentials**:
    - **Username**: `admin@pkbm.com`
    - **Password**: `password`

---

## 📚 Reference Files

### **For UI Patterns:**

- `resources/views/livewire/admin/academic/grades.blade.php` - Table with inputs
- `resources/views/livewire/admin/students/index.blade.php` - CRUD with modals
- `resources/views/components/admin/sidebar.blade.php` - Navigation
- `resources/views/livewire/public/homepage.blade.php` - Homepage layout
- `resources/views/livewire/public/contact.blade.php` - Contact form with validation

### **For Data Patterns:**

- `app/Models/User.php` - Complex relationships, teacher access control methods
- `app/Models/StudentProfile.php` - Polymorphic relationships
- `app/Models/CompetencyAssessment.php` - Assessment model
- `app/Models/NewsArticle.php` - Slug-based routing, image optimization
- `app/Models/Program.php` - Public content model

### **For Route Patterns:**

- `routes/academic.php` - Module routes example
- `routes/assessments.php` - Assessment routes (4 forms)
- `routes/public.php` - Public website routes with slug routing
- `resources/views/sitemap.blade.php` - SEO sitemap generation

### **For Assessment Forms:**

- `resources/views/livewire/admin/assessments/competency-assessment.blade.php` - Competency form
- `resources/views/livewire/admin/assessments/p5-assessment.blade.php` - P5 assessment
- `resources/views/livewire/admin/assessments/extracurricular-assessment.blade.php` - Extracurricular
- `resources/views/livewire/admin/assessments/attendance-input.blade.php` - Attendance summary

---

## 🎯 Development Checklist

### **When Creating New Features:**

1. [ ] Check existing patterns in similar files
2. [ ] Use Mary UI components
3. [ ] Follow naming conventions
4. [ ] Add route to appropriate route file
5. [ ] Add menu item to sidebar (if needed)
6. [ ] Test with tinker before UI testing
7. [ ] Clear cache after changes (`php artisan view:clear`)
8. [ ] Test on both light and dark mode
9. [ ] Update this knowledge base if needed

### **When Modifying Database:**

1. [ ] Create migration file
2. [ ] Create/update model
3. [ ] Add relationships
4. [ ] Run migration
5. [ ] Test with tinker
6. [ ] Create seeder if needed
7. [ ] Verify compatibility with SQLite AND MySQL

---

## 🔑 User Model Helper Methods

### **Teacher Access Control**

The `User` model includes several helper methods for teacher access control:

```php
// Check if teacher teaches PAUD level
$user->teachesPaudLevel(): bool

// Check if teacher has access to specific classroom
$user->hasAccessToClassroom(int $classroomId): bool

// Check if teacher has access to specific subject
$user->hasAccessToSubject(int $subjectId): bool

// Get array of assigned classroom IDs
$user->getAssignedClassroomIds(): array

// Get array of assigned subject IDs (includes homeroom subjects)
$user->getAssignedSubjectIds(): array

// Check user roles
$user->isAdmin(): bool
$user->isGuru(): bool
```

**Usage Example:**

```php
// In sidebar - show competency assessment only for PAUD teachers
@if(auth()->user()->isAdmin() || auth()->user()->teachesPaudLevel())
    <x-menu-item icon="o-academic-cap" :href="route('admin.assessments.competency')" label="{{ __('Penilaian Kompetensi') }}" />
@endif
```

---

**Last Updated:** 2026-02-27
**Version:** 2.3
**Maintained By:** Antigravity AI Assistant
