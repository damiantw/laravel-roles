<?php

return [
    'role_class' => \App\Role::class,
    'role_group_class' => \App\RoleGroup::class,
    'base_controller_class' => \App\Http\Controllers\Controller::class,
    'user_class' => \App\User::class,
    'role_user_table' => 'role_user',
    'role_role_group_table' => 'role_role_group',
    //Cache not yet implemented
    'cache_authorities' => true,
    'cache_driver' => 'default',
    'cache_time_seconds' => 60
];