<?php

namespace App\Models;

use Core\Database\Internal\Model;

class User extends Model
{
    use \Core\Database\Internal\HasFactory;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'password',
        'team',
        'role',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function tokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Core\Auth\Internal\PersonalAccessToken::class);
    }
}
