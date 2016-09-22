<?php

namespace DamianTW\LaravelRoles\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class HasAuthority
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string $authorities
     * @param  string $guard
     * @throws AuthenticationException
     * @return mixed
     */
    public function handle($request, Closure $next, $authorities, $guard = 'web')
    {
        $authorities = explode('|', $authorities);
        $user = $request->user($guard);

        if(!$user) {
            throw new AuthenticationException;
        }

        if(!$user->hasAnyAuthority($authorities)) {
            abort(401);
        }

        return $next($request);
    }
}
