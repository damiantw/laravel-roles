<?php

namespace DamianTW\LaravelRoles\Facades;

use Illuminate\Support\Facades\Facade;

class RoleGroupSeeder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \DamianTW\LaravelRoles\Services\RoleGroupSeederService::class;
    }

}