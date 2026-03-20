<?php

namespace Bkhim\Geolocation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LoginHistory.
 *
 * @package Bkhim\Geolocation\Models
 */
class LoginHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_login_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip',
        'ip_hash',
        'country_code',
        'city',
        'timezone',
        'currency_code',
        'is_proxy',
        'is_tor',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_proxy' => 'boolean',
        'is_tor' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the user that owns the login history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}