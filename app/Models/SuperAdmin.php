<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdmin extends Model
{
    protected $fillable = ['name', 'email', 'password', 'api_token'];

    protected $hidden = ['password'];
}
