<?php

namespace DamianTW\LaravelRoles\Services;

class RoleGroupSeederService
{
    protected $roleControllerService;

    function __construct(RoleControllerService $roleControllerService)
    {
        $this->roleControllerService = $roleControllerService;
    }

    public function defineRoleGroupAuthorities(array $roleGroupRoleDefinitions)
    {
        foreach ($roleGroupRoleDefinitions as $roleGroupId => $authorities) {
            $this->syncRoleGroupDefinition($roleGroupId, $authorities);
        }
    }

    public function syncRoleGroupDefinition($roleGroupId, $authorities)
    {
        $roleGroupClass = config('role.role_group_class');

        $roleGroup = $roleGroupClass::findOrFail($roleGroupId);

        $roleGroup->roles()->sync($this->getRoleIdsForAuthorities($authorities));
    }

    private function getRoleIdsForAuthorities($authorities)
    {
        $baseControllerClass = config('role.base_controller_class');
        $roleIds = [];
        foreach ($authorities as $authority) {

            if(is_subclass_of($authority,$baseControllerClass)) {
                $roleIds = array_merge($roleIds,$this->createOrGetRoleIdsForController($authority));
            }
            else {
                $roleIds[] = $this->createOrGetRoleIdByAuthority($authority);
            }

        }
        return $roleIds;
    }

    private function createOrGetRoleIdsForController($controllerClass)
    {
        $controllerMethodNames = $this->getPublicClassMethodsNonInherited($controllerClass);

        $controllerRoleIds = [];

        foreach ($controllerMethodNames as $controllerMethodName) {

            if($this->isMagicMethod($controllerMethodName))
                continue;

            $authority = $this->roleControllerService->buildAuthorityStringForControllerMethod($controllerClass, $controllerMethodName);

            $controllerRoleIds[] = $this->createOrGetRoleIdByAuthority($authority);
        }

        return $controllerRoleIds;

    }

    private function createOrGetRoleIdByAuthority($authority)
    {
        $roleClass = config('role.role_class');
        return $roleClass::firstOrCreate(['authority' => $authority])->id;
    }

    private function isMagicMethod($methodName)
    {
        return substr($methodName,0,2) === '__';
    }

    private function getPublicClassMethodsNonInherited($controllerClass)
    {
        $reflection = new \ReflectionClass($controllerClass);
        $methods = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

            if ($method->class == $reflection->getName()) {
                $methods[] = $method->name;
            }

        }

        return $methods;
    }

}