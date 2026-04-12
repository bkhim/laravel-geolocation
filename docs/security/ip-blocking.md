# IP Blocking

Block and manage malicious IPs with automatic and manual blocking.

---

## Overview

The package tracks IPs that have been flagged for suspicious activity and blocks them from accessing your application.

---

## Database

The blocking feature uses the `geolocation_ip_blocklist` table:

```php
// Created automatically via migration
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

```php
use DateTime;

IpBlocklist::block('1.2.3.4', 'Brute force', new DateTime('+7 days'));
```

### Automatic Blocking

Blocking happens automatically when the `geo.security` middleware is enabled and threat intelligence detects a threat:

```php
// In config/geolocation.php
'threat_intelligence' => [
    'enabled' => true,
    'auto_block' => true,
],
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

$blocked = IpBlocklist::where('ip', '1.2.3.4')->first();

if ($blocked) {
    $blocked->delete(); // or
    $blocked->blocked_until = now();
    $blocked->save();
}
```

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
1. Checks if IP is in blocklist
2. Checks threat intelligence
3. Auto-blocks if configured

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