<?php

namespace Bkhim\Geolocation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function block(string $ip, string $reason, ?\DateTime $until = null): self
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
}