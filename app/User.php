<?php

namespace Chompy;

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

    /**
     * Check to see if this user matches one of the given roles.
     *
     * @param  array|mixed $roles - role(s) to check
     * @return bool
     */
    public function hasRole($roles)
    {
      // Prepare an array of roles to check.
      // e.g. $user->hasRole('admin') => ['admin']
      //      $user->hasRole('admin, 'staff') => ['admin', 'staff']
      $roles = is_array($roles) ? $roles : func_get_args();

      return in_array($this->role, $roles);
    }
}
