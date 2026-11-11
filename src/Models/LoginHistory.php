<?php

namespace Bkhim\Geolocation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LoginHistory.
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
        'continent_code',
        'city',
        'region',
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
     * The table is configurable so applications can relocate it.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $table = config('geolocation.user_trait.login_history_table');

        if (is_string($table) && $table !== '') {
            $this->setTable($table);
        }
    }

    /**
     * Get the user that owns the login history.
     *
     * Falls back to the application's default auth model when
     * `auth.providers.users.model` is not configured (custom guards).
     */
    public function user(): BelongsTo
    {
        $model = config('auth.providers.users.model')
            ?? config('geolocation.user_trait.user_model')
            ?? \Illuminate\Foundation\Auth\User::class;

        return $this->belongsTo($model);
    }
}
