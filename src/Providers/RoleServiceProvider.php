<?php

namespace DamianTW\LaravelRoles\Providers;

use Illuminate\Support\ServiceProvider;
use DamianTW\LaravelRoles\Services\RoleService;
use DamianTW\LaravelRoles\Services\RoleGroupSeederService;

class RoleServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('DamianTW\LaravelRoles\RoleService', function ($app) {
            return new RoleService();
        });

        $this->app->singleton('DamianTW\LaravelRoles\RoleSeederService', function ($app) {
            return new RoleGroupSeederService();
        });


        $configPath = __DIR__ . '/../../config/role.php';
        $this->mergeConfigFrom($configPath, 'role');


        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../../config/role.php' => config_path('role.php')
            ], 'config');

            $this->publishes([
                __DIR__ . '/../Migrations/' => database_path('migrations')
            ], 'migrations');

            $this->publishes([
                __DIR__ . '/../Eloquent/' => app_path()
            ], 'models');

            $this->publishes([
                __DIR__ . '/../Seeders/' => database_path('seeds')
            ], 'seeds');
        }
    }
}
