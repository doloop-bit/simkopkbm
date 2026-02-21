# SIMKOPKBM - Development Steering Guide

> **Quick reference for AI assistant on HOW to develop features in this project.**
> 📖 For detailed specifications, see [KNOWLEDGE_BASE.md](./KNOWLEDGE_BASE.md)

---

## ⚡ Quick Start Checklist

Before starting any development:

```markdown
1. ☐ Read KNOWLEDGE_BASE.md for context
2. ☐ Check existing similar files for patterns
3. ☐ Use MCP tools to understand current state
4. ☐ Follow conventions strictly
5. ☐ Test with tinker before UI testing
```

---

## 🎯 Development Workflows

### **Workflow 1: Creating a New Page**

```
1. Create Volt Component
   └─> resources/views/livewire/admin/{module}/{name}.blade.php

2. Add Route
   └─> routes/{module}.php

3. Add to Sidebar (if needed)
   └─> resources/views/components/admin/sidebar.blade.php

4. Clear Cache
   └─> php artisan view:clear

5. Test in Browser
```

**Example Command Sequence:**

```bash
# 1. Create component
php artisan make:volt admin/module/feature-name --class

# 2. Edit route file (routes/module.php)
# 3. Edit sidebar (resources/views/components/admin/sidebar.blade.php)

# 4. Clear cache
php artisan view:clear

# 5. Start dev server
npm run dev
```

---

### **Workflow 2: Adding Database Table**

```
1. Create Migration
   └─> php artisan make:migration create_table_name_table

2. Define Schema
   └─> Edit migration file

3. Create Model
   └─> php artisan make:model ModelName

4. Add Relationships
   └─> Edit model file

5. Run Migration
   └─> php artisan migrate

6. Create Seeder (optional)
   └─> php artisan make:seeder ModelNameSeeder

7. Test with Tinker
   └─> mcp_laravel-boost_tinker()
```

⚠️ **Remember:** Test migrations work on both SQLite (dev) AND MySQL (production)!

---

### **Workflow 3: Modifying Existing Feature**

```
1. Find Existing Files
   └─> Use grep_search or find_by_name

2. Understand Current Pattern
   └─> view_file the existing code

3. Make Changes
   └─> Follow existing patterns exactly

4. Clear Cache
   └─> php artisan view:clear

5. Test
   └─> Browser or tinker
```

### **Workflow 4: Shared Admin/Teacher Features**

**DO NOT** rely on dynamic layout detection (e.g., `if(request()->is('teacher/*'))`). This is fragile.

**INSTEAD:**

1.  **Extract Logic**: Create a Trait in `app/Traits/Assessments/`.
2.  **Extract UI**: Create a Partial View in `resources/views/livewire/shared/_partials/`.
3.  **Create Two Components**:
    - `livewire/admin/.../feature.blade.php` -> uses `#[Layout('admin')]`
    - `livewire/teacher/.../feature.blade.php` -> uses `#[Layout('teacher')]`
4.  **Route Explicitly**:
    - Admin route -> Admin component
    - Teacher route -> Teacher component

---

## 📍 File Location Guide

### **Where to Put New Files?**

| Type            | Location                                                            |
| --------------- | ------------------------------------------------------------------- |
| Admin Page      | `resources/views/livewire/admin/{module}/{name}.blade.php`          |
| Public Page     | `resources/views/livewire/public/{name}.blade.php`                  |
| Model           | `app/Models/{ModelName}.php`                                        |
| Migration       | `database/migrations/{date}_create_{table}_table.php`               |
| Seeder          | `database/seeders/{ModelName}Seeder.php`                            |
| Route File      | `routes/{module}.php`                                               |
| Blade Component | `resources/views/components/admin/{name}.blade.php`                 |
| Blade Partial   | `resources/views/livewire/admin/{module}/partials/{name}.blade.php` |

### **Module Mapping**

| Module      | Route File               | View Directory                |
| ----------- | ------------------------ | ----------------------------- |
| Academic    | `routes/academic.php`    | `livewire/admin/academic/`    |
| Students    | `routes/students.php`    | `livewire/admin/students/`    |
| Assessments | `routes/assessments.php` | `livewire/admin/assessments/` |
| Report Card | `routes/report-card.php` | `livewire/admin/report-card/` |
| Financial   | `routes/financial.php`   | `livewire/admin/financial/`   |
| News        | `routes/news.php`        | `livewire/admin/news/`        |

---

## 🎨 UI Component Selection

### **Decision Tree: Which UI Library to Use?**

```
Need a component?
├─> Check Flux UI first
│   ├─> Available in Flux Free? → Use Flux
│   └─> Not available?
│       ├─> Check TallStack UI
│       │   ├─> Available? → Use TallStack
│       │   └─> Not available? → Build custom with Tailwind
```

### **Quick Reference**

| Need                         | Use                                                    |
| ---------------------------- | ------------------------------------------------------ |
| Button, Input, Select, Modal | **Flux** `<flux:*>`                                    |
| Date Picker, Time Picker     | **TallStack** `<x-ts-date-picker>`                     |
| Searchable Select            | **TallStack** `<x-ts-select.styled>`                   |
| Tabs, Steps/Wizard           | **TallStack** `<x-ts-tab>`, `<x-ts-step>`              |
| Rating, Color Picker         | **TallStack** `<x-ts-rating>`, `<x-ts-color-picker>`   |
| Table                        | **HTML + Tailwind** (Flux Free has no table component) |
| Toast Notifications          | **Flux** `\Flux::toast()`                              |

---

## 🔧 Code Patterns to Follow

### **Pattern 1: Page with Filters and Table**

Copy from: `grades.blade.php`

```blade
<?php
new #[Layout('components.admin.layouts.app')] class extends Component {
    public ?int $filter_id = null;

    public function mount(): void { }
    public function updatedFilterId(): void { }
    public function save(): void { }

    public function with(): array {
        return ['items' => Model::all()];
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Title</flux:heading>
            <flux:subheading>Description</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Filters --}}
    </div>

    @if($condition)
        <div class="border rounded-lg bg-white dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-sm text-left border-collapse">
                {{-- Table content --}}
            </table>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 text-zinc-500 border-2 border-dashed rounded-xl">
            <flux:icon icon="icon-name" class="w-12 h-12 mb-2 opacity-20" />
            <p>Please select filters.</p>
        </div>
    @endif
</div>
```

### **Pattern 2: CRUD with Modal**

Copy from: `students/index.blade.php`

### **Pattern 3: Form Page**

Copy from: `report-card/create.blade.php`

---

## ⚠️ Common Mistakes to Avoid

### **1. Wrong Component Location**

```blade
{{-- ❌ WRONG - Creates component-style file --}}
php artisan make:livewire admin/feature

{{-- ✅ CORRECT - Creates Volt component --}}
php artisan make:volt admin/module/feature --class
```

### **2. Not Using UI Libraries**

```blade
{{-- ❌ WRONG - Raw HTML --}}
<input type="text" wire:model="name" class="border rounded px-3 py-2">

{{-- ✅ CORRECT - Flux component --}}
<flux:input wire:model="name" label="Name" />
```

### **3. Forgetting to Clear Cache**

```bash
# Always run after changes
php artisan view:clear
```

### **4. Not Following Route Naming**

```php
// ❌ WRONG
Route::get('/competency', ...)->name('competency');

// ✅ CORRECT
Route::get('/assessments/competency', ...)->name('admin.assessments.competency');
```

### **5. Hardcoding Strings**

```blade
{{-- ❌ WRONG --}}
<span>Student Name</span>

{{-- ✅ CORRECT (for localization) --}}
{{ __('Nama Siswa') }}
```

### **6. Database-Specific SQL**

```php
// ❌ WRONG - MySQL-only syntax
DB::raw('JSON_EXTRACT(data, "$.field")')

// ✅ CORRECT - Use Eloquent casts
protected $casts = ['data' => 'array'];
```

---

## 🧪 Testing Commands

### **Quick Verification**

```bash
# Check if route exists
php artisan route:list --path=assessments

# Check if view compiles
php artisan view:cache

# Check last error
mcp_laravel-boost_last-error()
```

### **Test Data Creation (Tinker)**

```php
// Create test competency assessment
CompetencyAssessment::create([
    'student_id' => 2,
    'subject_id' => 1,
    'academic_year_id' => 1,
    'classroom_id' => 1,
    'semester' => '1',
    'competency_level' => 'BSH',
    'achievement_description' => 'Test',
]);

// Verify data
CompetencyAssessment::with('student', 'subject')->get();
```

---

## 🔄 Git Workflow

### **Branch Naming**

```
feature/kurikulum-merdeka  # Current branch
feature/p5-assessment
feature/report-generator
fix/blank-page-issue
```

### **Commit Message Format**

```
feat: Add P5 assessment form
fix: Resolve blank page on competency assessment
refactor: Extract modal into partial
docs: Update knowledge base
```

---

## 📞 How to Ask for Help

When reporting issues, include:

1. **What you expected:** "Page should show assessment form"
2. **What happened:** "Page is blank"
3. **Error message:** (if any from browser console)
4. **URL:** `/admin/assessments/competency`
5. **File:** `competency-assessment.blade.php`

---

## 🎯 Success Criteria

A feature is complete when:

- [ ] Code follows existing patterns
- [ ] Uses Flux/TallStack components appropriately
- [ ] Route is properly named
- [ ] Added to sidebar (if needed)
- [ ] Works in both light and dark mode
- [ ] Tested manually in browser
- [ ] Toast notifications for user feedback
- [ ] Proper validation and error messages
- [ ] Compatible with SQLite (dev) and MySQL (prod)

---

**Last Updated:** 2026-01-22
**Version:** 2.0

---

## 💻 Environment Cheat Sheet

- **Local URL**: `http://simkopkbm.test`
- **Laragon Project Name**: `simkopkbm`
- **OS**: Windows
- **Shell**: PowerShell
- **Test Credentials**:
    - **Username**: `admin@pkbm.com`
    - **Password**: `password`
