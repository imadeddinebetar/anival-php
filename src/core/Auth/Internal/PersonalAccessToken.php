<?php

namespace Core\Auth\Internal;

use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'json',
    ];

    public function getDates()
    {
        return [];
    }

    public function user()
    {
        $userModel = config('auth.user_model', \App\Models\User::class);
        return $this->belongsTo($userModel);
    }
}
