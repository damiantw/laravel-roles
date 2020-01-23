<?php

namespace DamianTW\LaravelRoles\Services;

use Illuminate\Support\Str;

/**
 * Class RoleControllerService
 * @package DamianTW\LaravelRoles
 */
class RoleControllerService
{
    public function buildAuthorityStringForControllerMethod($controllerClass, $controllerMethodName)
    {
        $authorityBaseName = $this->getAuthorityBaseName($controllerClass);
        return strtoupper(Str::snake($authorityBaseName) . '_' . Str::snake($controllerMethodName));
    }

    public function getAuthorityFromActionName($actionName)
    {
        $parsed = explode('@', $actionName);
        return $this->buildAuthorityStringForControllerMethod($parsed[0], $parsed[1]);
    }

    private function getAuthorityBaseName($controllerClass)
    {
        return str_replace('Controller' , '' , class_basename($controllerClass));
    }

}