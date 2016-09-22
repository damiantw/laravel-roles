<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleGroup extends Model
{

    public function users()
    {
        return $this->belongsToMany('App\User')->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role')->withTimestamps();
    }
}
