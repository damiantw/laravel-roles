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

              ],

              $user->id => [

              ]
          ]
        );
    }
}