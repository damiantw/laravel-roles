<?php

namespace DamianTW\LaravelRoles\Facades;

use Illuminate\Support\Facades\Facade;

class Role extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \DamianTW\LaravelRoles\Services\RoleService::class;
    }

}