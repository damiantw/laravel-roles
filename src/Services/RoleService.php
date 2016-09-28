<?php

namespace DamianTW\LaravelRoles\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Class RoleService
 * @package DamianTW\LaravelRoles
 */
class RoleService
{
    protected $cache;

    const BASE_CACHE_KEY = 'damiantw:laravelroles:';

    function __construct(CacheFactory $cacheFactory)
    {
        $this->cache = $cacheFactory->store(config('role.cache_store'));
    }

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
        $userPrimaryKey = $user->getKeyName();
        $cacheKey = self::BASE_CACHE_KEY . $user->$userPrimaryKey;

        if(config('role.cache_authorities') && $this->cache->has($cacheKey)) {
            return collect(json_decode($this->cache->get($cacheKey)));
        }
        else {
            $authorities = array_unique(
                array_merge(
                $this->getUserRoleGroupsAuthorities($user),
                $this->getUserRoleAuthorities($user)
                ), SORT_STRING
            );
            if(config('role.cache_authorities')) {
                $this->cache->add(
                    $cacheKey,
                    json_encode($authorities),
                    Carbon::now()->addSeconds(config('role.cache_time_seconds'))
                );
            }
            return collect($authorities);
        }
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