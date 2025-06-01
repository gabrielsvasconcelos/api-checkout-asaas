<?php

namespace App\Providers;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\AsaasService;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(OrderService::class, function ($app) {
            return new OrderService(
                new CustomerRepository(),
                new OrderRepository(),
                new ProductRepository(),
                new AsaasService()
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
            Route::prefix('api')
             ->middleware(['api'])
             ->group(base_path('routes/api.php'));
    
    }
}
