<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\WhatsApp\WhatsAppService::class,
            \App\Services\WhatsApp\FonnteService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Blade::directive('scope', function ($expression) {
            // Split expression like "'cell_name', $user"
            $parts = explode(',', $expression);
            $name = trim($parts[0]);
            $variable = isset($parts[1]) ? trim($parts[1]) : '$item';
            
            return "<?php \$__env->slot({$name}, function({$variable}) use (\$__env) { ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endscope', function () {
            return '<?php }); ?>';
        });
    }
}
