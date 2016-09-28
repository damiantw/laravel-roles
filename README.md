Role Based Authorization For Laravel 5
===================================


1. [Purpose](#purpose)
2. [Concept](#concept)
3. [Installation](#installation)
4. [Usage](#usage)
    * [Middleware](#middleware)
        * [Route Middleware](#route-middleware)
        * [Controller Middleware](#controller-middleware)
    * [API](#api)
    * [Seeding RoleGroups](#seeding-role-groups)
        * [Controller Based RoleGroup Seeding](#controller-based-role-group-seeding)
    * [Blade Directives](#blade-directives)    

Purpose <div id="purpose"></div>
-------

This package aims to provide a granular, clearly defined and easily accessible authority set for association with the 
Laravel User model. Out of the box Laravel offers some very [powerful tools](https://laravel.com/docs/5.3/authorization 
"Laravel 5.3 Authorization Docs") for handling checks on whether a user can complete a certain action. Often the
determining factor is the result of a simple boolean calculation (ex. does the id of a user match the user_id 
of the Post being edited?). A User's defined authority set can easily be factored into these calculations to provide 
a protection front using the provided API. We can then only allow users with the correct authorities access to actions 
using the Laravel Authorization tools or the provided Middleware.
 
Concept <div id="concept"></div>
-------

Each user in the application has a defined authority set. An authority is nothing more then a unique string that
hints at a permitted action or a level of privilege. Authorities live inside Roles, with each Role holding exactly one 
authority value. Roles can be associated with specific Users or with any number of RoleGroups. RoleGroups provide a way 
to define a common set of authorities that can be shared by many users. A User can be associated with many RoleGroups. 


A user's final defined authorities consists of the set of authorities from the Roles directly associated with the user 
merged with all of the authorities associated with a User's RoleGroups. This approach allows for the flexibility to
handle special case scenarios (such as needing to offer a specific lower privileged User access to single 
administrative action.) while providing the convenience of common assignable authority sets.

Installation <div id="installation"></div>
------------

Install via **Composer**

`composer require damiantw/laravel-roles`

Next add the ServiceProvider to the Package Service Providers in `config/app.php`

```php
        /*
         * Package Service Providers...
         */
        DamianTW\LaravelRoles\Providers\RoleServiceProvider::class,
```

Add the HoldsAuthorities traits to the User model

```php
<?php

namespace App;

use DamianTW\LaravelRoles\Traits\HoldsAuthorities;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    use HoldsAuthorities;
    
    //...
```

If you plan to make use of the hasAuthority or hasAuthorityController Middleware you will need to add them to your 
`$routeMiddleware` array in `app/Http/Kernel.php`

```php
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'hasAuthority' => \DamianTW\LaravelRoles\Middleware\HasAuthority::class,
        'hasAuthorityController' => \DamianTW\LaravelRoles\Middleware\HasAuthorityController::class
        // ...
    ];
```

Running `php artisan vendor:publish` will install the role configuration file, database migrations, Role/RoleGroup 
Eloquent models and RoleGroupsTableSeeder boilerplate to your application. At the very minimum you should install the
migrations and Eloquent models with: `php artisan vendor:publish --tag=migrations --tag=models`.

Now just run the migrations =) `php artisan migrate`

Usage <div id="usage"></div>
-----

A User's authority set pairs nicely with Laravel's built in authorization tools such as 
[Policies](https://laravel.com/docs/5.3/authorization#creating-policies).

For example lets make a policy for a Post model:

```
    php artisan make:policy PostPolicy --model=Post
```

We can then query a User's authority set within our Policy methods. This creates a front protection that ensures a User 
has the authority to participate in this action at all. We can then provide additional logic to determine if this 
specific instance of the action should be allowed.

```php
<?php

namespace App\Policies;

use App\User;
use App\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;
    
    public function before($user, $ability)
    {
        //If the User has the SUPER_ADMIN authority they will always pass all Policy checks
        if ($user->hasAuthority('SUPER_ADMIN')) {
            return true;
        }
    }

    public function view(User $user, Post $post)
    {
        // To view a Post:
        // A User must have the authority POST_SHOW
        // and cannot view private Posts unless they are their own.
        return $user->hasAuthority('POST_SHOW') && (!$post->private || $user->id === $post->user_id);
    }

    public function create(User $user)
    {
        // To create a Post:
        // A User must have the authority POST_CREATE **AND** POST_STORE
        return $user->hasAllAuthorities(['POST_CREATE', 'POST_STORE']);
    }

    public function update(User $user, Post $post)
    {
        // To update a Post:
        // A user must have the authority POST_EDIT **OR** POST_UPDATE
        // and can only edit their own Posts
        return $user->hasAnyAuthority(['POST_EDIT', 'POST_UPDATE']) && $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post)
    {
        // To delete a Post:
        // A user must have the authority POST_DESTROY
        // and either must be deleting their own Post or have the authority POST_CLEANER
        return $user->hasAuthority('POST_DESTROY') && ($user->id === $post->user_id || $user->hasAuthority('POST_CLEANER'));
    }
}
```

After registering our policy in the AuthServiceProvider, our PostController can make use of the authorize() Controller 
helper. If the Policy check does not pass an `Illuminate\Auth\Access\AuthorizationException` will be thrown causing the 
default Laravel exception handler to issue a HTTP 403 status code as the response. 

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;

class PostController extends Controller
{
    
    public function create()
    {
        $this->authorize('create', Post::class);
        //...
    }

    public function store(Request $request)
    {
        $this->authorize('create', Post::class);
        //...
    }

    public function show(Post $post)
    {
        $this->authorize('view', $post);
        //...
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        //...
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        //...
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        //...
    }
}

```


### Middleware <div id="middleware"></div>

Creating a Policy for certain actions may seem unnecessary if the only requirement to complete the actions is to hold 
a certain a authority(ies). For these situations you can make use of the provided hasAuthority and hasAuthorityController 
Middleware. 

#### Route Middleware <div id="route-middleware"></div>

Individual routes can be protected by applying the hasAuthority Middleware. If the user does not have the required 
authority(ies) for a route an `Symfony\Component\HttpKernel\Exception\HttpException` will be thrown with a status code 
of 401. 

```php

// User must have the USER_UPDATE authority to access the route
Route::put('/user/{user}', 'UserController@update')->middleware('hasAuthority:USER_UPDATE');

// More then one authority can be specificed using the pipe.
// If the user has the authority USER_DESTROY **OR** USER_MODERATOR they will be allowed access to the route
Route::delete('/user/{user}', 'UserController@destroy')->middleware('hasAuthority:USER_DESTROY|USER_MODERATOR');
 
// Apply **AND** boolean logic by calling the hasAuthority middleware multiple times
// Allowed if $user->hasAnyAuthority(['USER_VIEW','USER_SHOW']) **AND** $user->hasAuthority(['ADMIN']);
Route::get('/user/{user}', 'UserController@show')->middleware('hasAuthority:USER_VIEW|USER_SHOW', 'hasAuthority:ADMIN');

//You can provide a second parameter to define the guard that should be used to retreive the authenticated user
//The web guard will be used by default
Route::put('/user/{user}', 'UserController@update')->middleware('hasAuthority:USER_UPDATE,api');
 
```

#### Controller Middleware <div id="controller-middleware"></div>

You can easily protect all actions in a Controller by applying the hasAuthorityController Middleware in the Controller's
constructor. When a User attempts to access a route for any of the Controller's actions they will be checked for a role
following the convention `CONTROLLERSUBJECT_METHOD`. If the user does not have the required authority for a route an 
`Symfony\Component\HttpKernel\Exception\HttpException` will be thrown with a status code of 401. 

Take the following Controller for example: 

```php
<?php

namespace App\Http\Controllers;

class PostController extends Controller
{
    function __construct() 
    {
        // Optionally provide a guard parameter to hasAuthorityController. 
        // The web guard will be used by default
        // ex: $this->middleware('hasAuthorityController:api');   
        $this->middleware('hasAuthorityController');    
    }
    public function create(){}
    public function store(){}
    public function show(){}
    public function edit(){}
    public function update(){}
    public function destroy(){}
}
```

Each route will be checked with an authority.

| ACTION  | AUTHORITY   |
|---------|-------------|
| create  | POST_CREATE |
| store   | POST_STORE  |
| show    | POST_SHOW   |
| edit    | POST_EDIT   |
| update  | POST_UPDATE |
| destroy | POST_DESTROY |

### API <div id="api"></div>

The HoldsAuthorities Trait adds the following methods to the User model

```php
<?php

// Returns true only if $authorityStr is in the User's authority set.
$user->hasAuthority($authorityStr);

// Returns true only if $authorityStr1 **OR** $authorityStr2 is in the User's authority set.
$user->hasAnyAuthority([$authorityStr1, $authorityStr2]);

// Returns true only if $authorityStr1 **AND** $authorityStr2 **AND** $authorityStr3 are in the User's authority set.
$user->hasAllAuthorities([$authorityStr1, $authorityStr2, $authorityStr3]);

// Returns a Collection of all the User's authorities
$user->authorities();

// Eloquent relation for User Roles.
$user->roles;
$user->roles();

// Eloquent relation for User RoleGroups
$user->roleGroups;
$user->roleGroups();
```

### Seeding RoleGroups <div id="seeding-role-groups"></div>

You may want to provide a default set of RoleGroups with specific authorities for your application. This is a good use
case for Laravel's seeding features. 

This package provides a RoleGroupsTableSeeder boilerplate and a RoleGroupSeeder Facade which can be used to clearly 
define the default authority sets for your application's RoleGroups. 

```php
<?php

use Illuminate\Database\Seeder;
use DamianTW\LaravelRoles\Facades\RoleGroupSeeder;
use App\RoleGroup;

class RoleGroupsTableSeeder extends Seeder
{
    
    public function run()
    {
        $admin = RoleGroup::firstOrCreate(['name' => 'Admin']);
        $user = RoleGroup::firstOrCreate(['name' => 'User']);
        
        RoleGroupSeeder::defineRoleGroupAuthorities(
          [
              $admin->id => [
                  'VIEW_USER',
                  'CREATE_USER',
                  'UPDATE_USER',
                  'DELETE_USER'
              ],
              // Admin RoleGroup will have authorities: VIEW_USER, CREATE_USER, UPDATE_USER, DELETE_USER
              
              $user->id => [
                  'ViEW_USER'
              ]
              // User RoleGroup will only have the VIEW_USER authority 
          ]  
        );
    }
}
```

When we run `php arisian db:seed --class=RoleGroupsTableSeeder` the RoleGroupSeeder Facade will automatically create
Roles for authorities that do not already exist and sync the RoleGroup authority set definitions as you defined them.

If your application does not allow for changing RoleGroup authority set definitions at runtime it can be useful to run
this command as part of the deployment procedure. 

#### Controller Based RoleGroup Seeding <div id="controller-based-role-group-seeding"></div>

You can also pass Controller classes as part of the RoleGroup authority definition. RoleGroupSeeder will create an
authority for each public, non magic method in the controller following the convention `CONTROLLERSUBJECT_METHOD`.

Take the following Controller for example:

```php
<?php

namespace App\Http\Controllers;

class PostController extends Controller
{
    function __construct() {}
    public function create(){}
    public function store(){}
    public function show(){}
    public function edit(){}
    public function update(){}
    public function destroy(){}
    private function privateHelperFunction(){}
}
```

when PostController is passed as part of the definition: 

```php
        RoleGroupSeeder::defineRoleGroupAuthorities(
          [
              $group->id => [
                  'NON_CONTROLLER_BASED_AUTHORITY',
                  App\Http\Controllers\PostController::class,
              ],
          ]  
        );
```
Authorities with the following roles will be created and associated with the group:

* NON_CONTROLLER_BASED_AUTHORITY
* POST_CREATE
* POST_STORE
* POST_SHOW
* POST_EDIT
* POST_UPDATE
* POST_DESTROY

### Blade Directives <div id="blade-directives"></div>

The following directives are available in Blade views for convenience and code readability. 

```

@hasAuthority('VIEW_USER')
    USER HAS AUTHORITY
@endHasAuthority

@hasAnyAuthority('VIEW_USER', 'CREATE_USER')
    USER HAS AUTHORITY VIEW_USER OR CREATE_USER
@endif
{{-- @endif has the same function as @endHasAuthority. You can use either. --}}
    
{{-- Feel free to include conditional control structures. --}}
@hasAllAuthorities('VIEW_USER, 'CREATE_USER')
     USER HAS BOTH VIEW_USER AND CREATE_USER AUTHORITIES
@else
     USER DOES NOT HAVE VIEW_USER AND CREATE_USER AUTHORITIES
@endHasAuthority     

```

##### Disclaimer

This package has only been tested with Laravel 5.3, though everything should work with Laravel 5.2.

###### Wish List

* ~~Cache User authority set~~
* ~~hasAuthority Blade directive~~
* ~~Protect all of a controllers actions automatically using a convention~~
* Tests