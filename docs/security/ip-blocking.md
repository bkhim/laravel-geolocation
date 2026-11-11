# IP Blocking

Block and manage malicious IPs with automatic and manual blocking.

---

## Overview

The package tracks IPs that have been flagged for suspicious activity and blocks them from accessing your application.

---

## Database

The blocking feature uses the `geolocation_ip_blocklist` table. Publish the migration and then run it:

```bash
php artisan vendor:publish --tag=geolocation-migrations
php artisan migrate
```

The schema:

```php
Schema::create('geolocation_ip_blocklist', function (Blueprint $table) {
    $table->id();
    $table->string('ip', 45)->unique();
    $table->string('reason')->nullable();
    $table->timestamp('blocked_until');
    $table->integer('attempts')->default(1);
    $table->timestamps();
});
```

---

## Blocking IPs

### Manual Blocking

```php
use Bkhim\Geolocation\Models\IpBlocklist;

// Block for 24 hours (default)
IpBlocklist::block('1.2.3.4', 'Manual block - spam');
```

### Block for Custom Duration

`block()` accepts any `DateTimeInterface` (Carbon, immutable dates, etc.):

```php
IpBlocklist::block('1.2.3.4', 'Brute force', now()->addDays(7));
IpBlocklist::block('1.2.3.5', 'Spam', new \DateTimeImmutable('+7 days'));
```

### Automatic Blocking

Blocking happens automatically when the `geo.security` middleware is enabled and threat intelligence detects a threat:

```env
GEOLOCATION_THREAT_INTELLIGENCE_ENABLED=true
GEOLOCATION_THREAT_AUTO_BLOCK=true
ABUSEIPDB_API_KEY=your_key
```

---

## Checking Blocks

```php
use Bkhim\Geolocation\Models\IpBlocklist;

if (IpBlocklist::isBlocked($request->ip())) {
    abort(403, 'Your IP has been blocked');
}
```

---

## Unblocking

```php
use Bkhim\Geolocation\Models\IpBlocklist;

IpBlocklist::unblock('1.2.3.4');   // delete the row immediately
IpBlocklist::pruneExpired();       // delete rows whose blocked_until has passed
```

`geolocation:prune` also removes expired entries automatically.

---

## Middleware

Add to your routes:

```php
Route::middleware('geo.security')->group(function () {
    Route::post('/login', ...);
    Route::post('/checkout', ...);
});
```

This middleware:
1. Rejects IPs present in `geolocation_ip_blocklist` (403)
2. Audits threat-intelligence detections
3. Auto-blocks when `threat_intelligence.auto_block=true`

Disable the blocklist check with `security.enable_blocking=false`.

---

## Viewing Blocked IPs

```sql
SELECT * FROM geolocation_ip_blocklist 
WHERE blocked_until > NOW() 
ORDER BY blocked_until DESC;
```

---

## Best Practices

1. **Never block permanent** - Always use temporary blocks with expiration
2. **Log reason** - Always specify why an IP was blocked
3. **Monitor false positives** - Watch for legitimate users being blocked
4. **Use threat intelligence** - Let AbuseIPDB help identify threats automatically