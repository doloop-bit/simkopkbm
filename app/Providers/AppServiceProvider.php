<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            WhatsAppService::class,
            FonnteService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('scope', function ($expression) {
            // Split expression like "'cell_name', $user, $extraVar"
            $parts = array_map('trim', explode(',', $expression));
            $name = $parts[0];
            $variable = $parts[1] ?? '$item';
            $extraVars = array_slice($parts, 2);

            $useVars = array_merge(['$__env'], $extraVars);
            $useClause = implode(', ', array_unique($useVars));

            return "<?php \$__env->slot({$name}, function({$variable}) use ({$useClause}) { ?>";
        });

        Blade::directive('endscope', function () {
            return '<?php }); ?>';
        });
    }
}
