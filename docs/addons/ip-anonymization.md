# IP Anonymization

Privacy-preserving IP address handling for GDPR and CCPA compliance.

## Overview

IP anonymization masks part of the IP address to make it non-personal data while still allowing for geolocation and analytics.

## IPv4 Anonymization

```php
use Bkhim\Geolocation\Facades\IpAnonymizer;

// Anonymize IPv4 address
$anonymized = IpAnonymizer::anonymize('192.168.1.100');
// Result: 192.168.1.0

// Check if IP is already anonymized
$isAnon = IpAnonymizer::isAnonymized('192.168.1.100');
// Result: false
```

## IPv6 Anonymization

```php
// Anonymize IPv6 address
$anonymized = IpAnonymizer::anonymize('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
// Result: 2001:0db8:85a3::8a2e:0370:7334 (last 64 bits masked)
```

## Configuration

In `config/geolocation.php`:

```php
'addons' => [
    'anonymization' => [
        'enabled' => env('GEOLOCATION_ANONYMIZATION_ENABLED', false),
    ],
],
```

## Usage with Geolocation

```php
// Enable in .env
GEOLOCATION_ANONYMIZATION_ENABLED=true

// The package will automatically anonymize IPs before lookup
// This is useful for analytics where you don't need exact location
```

## Implementation Example

### Using in Middleware

```php
// app/Http/Middleware/AnonymizeIpForAnalytics.php
public function handle($request, Closure $next)
{
    if (config('geolocation.addons.anonymization.enabled')) {
        $anonymized = IpAnonymizer::anonymize($request->ip());
        $request->merge(['ip' => $anonymized]);
    }
    
    return $next($request);
}
```

### Using in Logging

```php
// In your logging configuration
'processors' => [
    function ($record) {
        if (isset($record['context']['ip'])) {
            $record['context']['ip'] = IpAnonymizer::anonymize($record['context']['ip']);
        }
        return $record;
    },
],
```

## Anonymization Methods

| Method | Description | Example |
|--------|-------------|---------|
| `anonymize()` | Masks last octet (IPv4) or last 64 bits (IPv6) | `192.168.1.100` → `192.168.1.0` |
| `isAnonymized()` | Checks if IP is already anonymized | Returns `true`/`false` |

## Legal Compliance

- **GDPR**: Anonymized IPs are not personal data
- **CCPA**: Anonymized data is exempt from certain requirements
- **Best Practice**: Always anonymize IPs for analytics purposes
