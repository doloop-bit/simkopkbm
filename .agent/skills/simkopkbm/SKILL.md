# SIMKOPKBM Specialist Skill

This skill provides the AI with deep knowledge and operational procedures for developing the **SIMKOPKBM** (Sistem Informasi Manajemen Koperasi Pendidikan KB/TK Baitusyukur Malang) application.

## 🚀 Core Mission

Maintain high code quality, follow existing patterns strictly, and leverage the modern Laravel 12 / Livewire 4 ecosystem to build a robust school management system.

## 🛠 Technical Stack

- **Framework**: Laravel 12 (Streamlined structure)
- **Frontend**: Livewire 4 + Volt (Single File Components)
- **UI Libraries**:
    - **Flux UI (Free)**: Primary choice for Button, Input, Select, Modal.
    - **TallStack UI**: Secondary choice for Table, DatePicker, Searchable Select, Tabs.
- **Styling**: Tailwind CSS 4 (CSS-first config)
- **Database**: SQLite (Dev) / MySQL (Prod)

## 📖 Operational Guidelines

### 1. Consult Documentation First

Before implementing any feature, especially if unsure about syntax or best practices:

- Use `mcp_laravel-boost_search-docs` with specific package names if needed.
- Refer to local guides:
    - `docs/ai-guides/STEERING_GUIDE.md`: Workflows and patterns.
    - `docs/ai-guides/KNOWLEDGE_BASE.md`: Domain knowledge and technical specs.

### 2. Follow Development Workflows

- **New Page**:
    1. `php artisan make:volt admin/{module}/{name} --class`
    2. Add route in `routes/{module}.php`
    3. Add to `resources/views/components/admin/sidebar.blade.php`
    4. `php artisan view:clear`
- **Database**:
    1. `php artisan make:migration create_{table}_table`
    2. `php artisan make:model {ModelName}`
    3. `php artisan migrate`
    4. Test with `mcp_laravel-boost_tinker`

### 3. Structural Patterns

- **Layouts**: Use `#[Layout('components.admin.layouts.app')]`.
- **Volt Components**: Use class-based Volt components by default for complexity.
- **Multi-Role**: For shared features between Admin and Teacher:
    - Extract logic to Traits.
    - Extract UI to partials in `resources/views/livewire/shared/_partials/`.
    - Create separate components with explicit layouts.

### 4. UI Library Decision Tree

1. **Flux UI Free** available? → Use it (`<flux:*>` tag).
2. **TallStack UI** available? → Use it (`<x-ts-*>` tag).
3. Neither? → Custom Tailwind + Headless UI/Alpine.js.

### 5. Essential Tools

- `mcp_laravel-boost_application-info`: Verify current stack.
- `mcp_laravel-boost_database-schema`: Check table structure.
- `mcp_laravel-boost_tinker`: Test logic/queries.
- `mcp_laravel-boost_last-error`: Debug backend issues.
- `mcp_laravel-boost_list-routes`: Verify URL mappings.

## ⚠️ Critical Rules

- **Localization**: Never hardcode strings. Use `{{ __('Key') }}`.
- **Cache**: Run `php artisan view:clear` after modifying Blade/Volt components.
- **Database**: Ensure migrations are compatible with both SQLite and MySQL.
- **Formatting**: Run `vendor/bin/pint --dirty` before finalizing.
- **N+1**: Always eager load relationships in `with()` method of Volt components.

## 📂 Reference Directory

- `app/Models/`: Model relationships and logic.
- `resources/views/livewire/admin/`: Admin pages.
- `routes/`: Module-based route files.
- `docs/`: Master documentation.
