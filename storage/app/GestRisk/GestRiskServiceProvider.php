<?php

namespace GestRisk;

use App\Models\Consulta;
use App\Observers\ConsultaObserver;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class GestRiskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once dirname(__DIR__).'/gestrisk_load.php';

        $this->mergeConfigFromStorage();
    }

    public function boot(): void
    {
        View::prependLocation(storage_path('app/patches/resources/views'));

        Consulta::observe(ConsultaObserver::class);

        $this->registerWebMiddleware();
        $this->registerRoutes();
        $this->registerFaviconRoutes();
    }

    private function registerWebMiddleware(): void
    {
        $this->app->booted(function () {
            $this->app->make(HttpKernel::class)
                ->appendMiddlewareToGroup('web', InjectFaviconLinks::class);
        });
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['web'])->group(function () {
            Route::get('/dashboard/graficos/{slug}', [\App\Http\Controllers\GraficosController::class, 'descritivo'])
                ->name('dashboard.graficos');
            Route::get('/assets/gestrisk/chart.js', [\App\Http\Controllers\GraficosController::class, 'chartJs'])
                ->name('gestrisk.chartjs');
        });
    }

    private function registerFaviconRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" role="img" aria-label="Cardioprenatal">
  <rect width="32" height="32" rx="7" fill="#7f0c1a"/>
  <path fill="#fff" d="M7.5 10.2c0-1.55 1.25-2.8 2.8-2.8.85 0 1.65.38 2.1.98.45-.6 1.25-.98 2.1-.98 1.55 0 2.8 1.25 2.8 2.8 0 2.05-2.35 3.82-4.9 6.05L10.3 17.8 7.5 15.5C5.15 13.05 7.5 11.35 7.5 10.2z"/>
  <circle cx="22.5" cy="11" r="3.2" fill="#fff"/>
  <path fill="#fff" d="M17.2 24.5c0-3.1 2.4-5.6 5.3-5.6s5.3 2.5 5.3 5.6v1.5H17.2v-1.5z"/>
</svg>
SVG;

        $handler = function () use ($svg) {
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        };

        Route::get('/favicon.ico', $handler);
        Route::get('/favicon.svg', $handler);
    }

    private function mergeConfigFromStorage(): void
    {
        $baseUrl = rtrim((string) env('CCF_API_URL', env('PYTHON_API_URL', 'http://127.0.0.1:8010')), '/');
        $figuresPath = env('CCF_FIGURES_PATH');
        if (! $figuresPath) {
            $sibling = dirname(base_path()).DIRECTORY_SEPARATOR.'AnalisePython-main'
                .DIRECTORY_SEPARATOR.'analise_descritiva'.DIRECTORY_SEPARATOR.'figuras';
            $figuresPath = is_dir($sibling) ? $sibling : null;
        }
        $metaJson = env('CCF_META_JSON');
        if (! $metaJson && $figuresPath) {
            $candidate = dirname($figuresPath).DIRECTORY_SEPARATOR.'analise_descritiva.json';
            $metaJson = is_readable($candidate) ? $candidate : null;
        }

        config([
            'services.ccf_api' => [
                'url' => $baseUrl,
                'api_key' => env('CCF_API_KEY'),
                'timeout' => (int) env('CCF_API_TIMEOUT', 120),
                'figures_path' => $figuresPath,
                'meta_json' => $metaJson,
                'sync_analise' => filter_var(
                    env('GESTRISK_ANALISE_SYNC', env('APP_ENV', 'production') === 'local'),
                    FILTER_VALIDATE_BOOL
                ),
            ],
        ]);
    }
}
