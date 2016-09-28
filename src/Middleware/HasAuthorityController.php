<?php

namespace DamianTW\LaravelRoles\Middleware;

use Closure;
use DamianTW\LaravelRoles\Services\RoleControllerService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Routing\Route;

/**
 * Class HasAuthorityController
 * @package DamianTW\LaravelRoles
 */
class HasAuthorityController
{

    protected $route;
    protected $roleControllerService;

    /**
     * HasAuthorityController constructor.
     * @param  \Illuminate\Routing\Route $route
     * @param \DamianTW\LaravelRoles\Services\RoleControllerService $roleControllerService
     */
    function __construct(Route $route, RoleControllerService $roleControllerService)
    {
        $this->route = $route;
        $this->roleControllerService = $roleControllerService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  $guard
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, $guard = 'web')
    {
        $authority = $this->roleControllerService->getAuthorityFromActionName($this->route->getActionName());

        $user = $request->user($guard);

        if(!$user) {
            throw new AuthenticationException;
        }

        if(!$user->hasAuthority($authority)) {
            abort(401);
        }

        return $next($request);
    }
}
