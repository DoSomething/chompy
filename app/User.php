<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DoSomething\Gateway\Contracts\NorthstarUserContract;
use DoSomething\Gateway\Laravel\HasNorthstarToken;

class User extends Authenticatable implements NorthstarUserContract
{
    use Notifiable, HasNorthstarToken;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
