<?php

namespace DamianTW\LaravelRoles\Services;

class RoleService
{

    public function userHasAuthority($user, $authority)
    {
        return $this->getAllUserAuthorities($user)->contains($authority);
    }

    public function userHasAnyAuthority($user, $authorities)
    {
        foreach ($authorities as $authority) {

            if($this->userHasAuthority($user, $authority)) {
                return true;
            }

        }

        return false;
    }

    public function userHasAllAuthorities($user, $authorities)
    {
        foreach ($authorities as $authority) {

            if(!$this->userHasAuthority($user, $authority)) {
                return false;
            }

        }

        return true;
    }

    public function getAllUserAuthorities($user)
    {
        return collect(
            array_unique(
                array_merge(
                    $this->getUserRoleGroupsAuthorities($user),
                    $this->getUserRoleAuthorities($user)
                ), SORT_STRING
            )
        );
    }

    public function getUserRoleGroupsAuthorities($user)
    {
        $roleGroupsRoles = [];
        $roleGroups = $user->roleGroups()->with('roles')->get();

        foreach ($roleGroups as $roleGroup) {
            $roleGroupsRoles = array_merge($roleGroupsRoles, $roleGroup->roles->pluck('authority')->all());
        }

        return array_unique($roleGroupsRoles, SORT_STRING);
    }

    private function getUserRoleAuthorities($user) {
        return $user->roles->pluck('authority')->all();
    }

}