<?php

namespace DamianTW\LaravelRoles\Traits;

use DamianTW\LaravelRoles\Services\RoleService;

/**
 * Trait HoldsAuthorities
 * @package DamianTW\LaravelRoles
 */
trait HoldsAuthorities
{

    public function hasAuthority($authority)
    {
        return $this->getRoleService()->userHasAuthority($this, $authority);
    }

    public function hasAnyAuthority($authorities)
    {
        return $this->getRoleService()->userHasAnyAuthority($this, $authorities);
    }

    public function hasAllAuthorities($authorities)
    {
        return $this->getRoleService()->userHasAllAuthorities($this, $authorities);
    }

    public function authorities()
    {
        return $this->getRoleService()->getAllUserAuthorities($this);
    }

    public function roles()
    {
        return $this->belongsToMany(config('role.role_class'), config('role.role_user_role_table'))->withTimestamps();
    }

    public function roleGroups()
    {
        return $this->belongsToMany(config('role.role_group_class', config_path('role.role_role_group_table')))->withTimestamps();
    }

    protected function getRoleService()
    {
        return resolve(RoleService::class);
    }
}