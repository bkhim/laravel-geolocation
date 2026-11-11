<?php

namespace Bkhim\Geolocation\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class IpBlocklist extends Model
{
    protected $table = 'geolocation_ip_blocklist';

    protected $fillable = [
        'ip',
        'reason',
        'blocked_until',
        'attempts',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];

    public static function block(string $ip, string $reason, ?DateTimeInterface $until = null): self
    {
        $record = static::where('ip', $ip)->first();

        if ($record) {
            $record->update([
                'reason' => $reason,
                'blocked_until' => $until ?? now()->addHours(24),
                'attempts' => $record->attempts + 1,
            ]);

            return $record;
        }

        return static::create([
            'ip' => $ip,
            'reason' => $reason,
            'blocked_until' => $until ?? now()->addHours(24),
            'attempts' => 1,
        ]);
    }

    public static function isBlocked(string $ip): bool
    {
        return static::where('ip', $ip)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    /**
     * Remove expired block entries. Returns the number of rows deleted.
     */
    public static function pruneExpired(): int
    {
        return static::where('blocked_until', '<=', now())->delete();
    }

    /**
     * Lift a block immediately, regardless of its expiry.
     */
    public static function unblock(string $ip): bool
    {
        return (bool) static::where('ip', $ip)->delete();
    }
}
