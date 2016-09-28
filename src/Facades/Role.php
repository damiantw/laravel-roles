<?php

namespace DamianTW\LaravelRoles\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Role
 * @package DamianTW\LaravelRoles
 */
class Role extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \DamianTW\LaravelRoles\Services\RoleService::class;
    }

}