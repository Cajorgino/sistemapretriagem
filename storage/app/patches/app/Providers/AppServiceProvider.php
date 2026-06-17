<?php

namespace App\Providers;

use App\Models\Consulta;
use App\Models\Gestante;
use App\Observers\ConsultaObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.cardioprenatal');

        Consulta::observe(ConsultaObserver::class);
    }
}
