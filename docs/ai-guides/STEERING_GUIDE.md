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
1. Create Livewire SFC Component
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
# 1. Create component (Native SFC)
php artisan make:livewire admin/module/feature-name --sfc

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
    - `livewire/admin/.../feature.blade.php` -> uses `#[Layout('components.layouts.app')]`
    - `livewire/teacher/.../feature.blade.php` -> uses `#[Layout('components.layouts.app')]`
4.  **Route Explicitly**:
    - Admin route -> Admin component
    - Teacher route -> Teacher component

---

### **Workflow 5: Handling Multi-Role Access**

1. **Check Role**: Use helper methods on the `User` model rather than raw string checks.
   - ✅ `if (auth()->user()->isTreasurer())`
   - ❌ `if (auth()->user()->role === 'bendahara')`

2. **Middleware**: Routes are protected by `auth` and often by specific role check logic in components or `bootstrap/app.php`.

3. **Active Role**: Access the active role via `auth()->user()->activeRole()`.

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
├─> Check Custom UI components (resources/views/components/ui)
│   └─> Use <x-ui.*> components
└─> Need specialized functional component?
    └─> Check Mary UI available in project (if prefix is set)
```

### **Quick Reference**

| Need                         | Use                                                 |
| ---------------------------- | --------------------------------------------------- |
| Button, Input, Select, Modal | **Custom UI** `<x-ui.button>`, `<x-ui.input>`, etc. |
| Header / Title               | **Custom UI** `<x-ui.header>`                       |
| Badge                        | **Custom UI** `<x-ui.badge>`                        |
| Card                         | **Custom UI** `<x-ui.card>`                         |
| Table                        | **Custom UI** `<x-ui.table>`                        |
| Icons                        | **Custom UI** `<x-ui.icon>` (uses Heroicons)        |

---

## 🔧 Code Patterns to Follow

### **Pattern 1: Page with Filters and Table**

```blade
<?php
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch(): void { }
    public function save(): void { }

    public function with(): array {
        return ['items' => Model::paginate(10)];
    }
}; ?>

<div class="p-6">
    <x-ui.header title="Title" subtitle="Description">
        <x-slot:actions>
             <x-ui.button label="Action" icon="o-plus" class="btn-primary" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card shadow padding="false">
        <x-ui.table :headers="$headers" :rows="$items">
            @scope('cell_actions', $item)
                <x-ui.button icon="o-pencil" wire:click="edit({{ $item->id }})" ghost sm />
            @endscope
        </x-ui.table>
    </x-ui.card>
</div>
```

---

## ⚠️ Common Mistakes to Avoid

### **1. Using Volt Syntax**

```blade
{{-- ❌ WRONG - Volt functionally is not used --}}
<?php
use function Livewire\Volt\{state};
state(['name' => '']);
?>

{{-- ✅ CORRECT - Native Livewire 4 SFC --}}
<?php
new class extends Component {
    public $name = '';
}; ?>
```

### **2. Wrong Component Tag**

```blade
{{-- ❌ WRONG - Raw HTML or outdated tag --}}
<button class="btn btn-primary">Save</button>

{{-- ✅ CORRECT - Custom UI component --}}
<x-ui.button label="Save" class="btn-primary" type="submit" />
```

---

**Last Updated:** 2026-04-08
**Version:** 3.1 (Native SFC + Multi-Role Update)
