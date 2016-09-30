<?php

namespace DamianTW\LaravelRoles\Providers;

use Illuminate\Support\ServiceProvider;
use DamianTW\LaravelRoles\Services\RoleService;
use DamianTW\LaravelRoles\Services\RoleGroupSeederService;
use DamianTW\LaravelRoles\Services\RoleControllerService;
use Illuminate\Support\Facades\Blade;

/**
 * Class RoleServiceProvider
 * @package DamianTW\LaravelRoles
 */
class RoleServiceProvider extends ServiceProvider
{

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * Blade Directives
         */

        Blade::directive('hasAuthority', function($expression) {
            return str_replace('**AUTHORITY**', $expression, file_get_contents(__DIR__ . '/../Blade/hasAuthority.txt'));
        });

        Blade::directive('hasAnyAuthority', function($expression) {
            return str_replace('**AUTHORITIES**', $expression, file_get_contents(__DIR__ . '/../Blade/hasAnyAuthority.txt'));
        });

        Blade::directive('hasAllAuthorities', function($expression) {
            return str_replace('**AUTHORITIES**', $expression, file_get_contents(__DIR__ . '/../Blade/hasAllAuthorities.txt'));
        });

        Blade::directive('endHasAuthority', function($expression) {
            return file_get_contents(__DIR__ . '/../Blade/endHasAuthority.txt');
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('DamianTW\LaravelRoles\RoleService', function ($app) {
            return new RoleService($app->make('Illuminate\Contracts\Cache\Factory'));
        });

        $this->app->singleton('DamianTW\LaravelRoles\RoleSeederService', function ($app) {
            return new RoleGroupSeederService($app->make(RoleService::class));
        });

        $this->app->singleton('DamianTW\LaravelRoles\RoleControllerService', function ($app) {
            return new RoleControllerService;
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
