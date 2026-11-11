# IP Anonymization

Privacy-preserving IP address handling for GDPR and CCPA compliance.

## Overview

IP anonymization masks part of an IP address so it is no longer personal data,
while still allowing coarse geolocation and analytics. The addon has a master
switch plus an optional country scope:

| `enabled` | `gdpr_countries` | Behaviour |
|-----------|------------------|-----------|
| `false` | any | **never** masks (master switch) |
| `true` | `[]` (default) | masks **every** IP |
| `true` | `['*']` | masks **every** IP |
| `true` | `['DE', 'FR', …]` | masks only those countries |

`preserve_local` (default `true`) always leaves private/reserved addresses
untouched.

## Usage

```php
use Bkhim\Geolocation\Facades\IpAnonymizer;

// Applies the configured policy (enabled + country scope)
$masked = IpAnonymizer::anonymize('8.8.8.1');       // "8.8.8.0"

// Force masking regardless of policy (e.g. storage decisions)
$masked = IpAnonymizer::mask('8.8.8.1');            // "8.8.8.0"

// Inspect
IpAnonymizer::isEnabled();   // bool
IpAnonymizer::scope();       // "none" | "all" | "gdpr"
```

## Configuration

```php
'addons' => [
    'anonymization' => [
        'enabled' => env('GEOLOCATION_ANONYMIZATION_ENABLED', false),

        // Master-scope list. Empty or ['*'] = anonymize everything.
        // Provide ISO codes to restrict masking to GDPR countries.
        'gdpr_countries' => [],   // env: GEOLOCATION_ANONYMIZATION_COUNTRIES=DE,FR

        'ipv4_mask' => '255.255.255.0',                          // env: ..._IPV4_MASK
        'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000', // env: ..._IPV6_MASK
        'preserve_local' => true,                                 // env: ..._PRESERVE_LOCAL
    ],
],
```

Every value above can be set from `.env`:

```env
GEOLOCATION_ANONYMIZATION_ENABLED=true
GEOLOCATION_ANONYMIZATION_IPV4_MASK=255.255.255.0
GEOLOCATION_ANONYMIZATION_IPV6_MASK=ffff:ffff:ffff:ffff:0000:0000:0000:0000
GEOLOCATION_ANONYMIZATION_PRESERVE_LOCAL=true
GEOLOCATION_ANONYMIZATION_COUNTRIES=
```

## IPv4 / IPv6

```php
IpAnonymizer::mask('192.168.1.100');                    // 192.168.1.0   (/24)
IpAnonymizer::mask('2001:db8:85a3::8a2e:370:7334');     // 2001:db8:85a3::  (/64)
```

Change `ipv4_mask` / `ipv6_mask` to control how much is preserved. The masks
are also honoured when the `HasGeolocation` trait stores a masked login IP.

## Login history

IP storage for `user_login_locations` is controlled separately by
`user_trait.anonymization_mode` (`none`, `partial`, `full`) and uses the same
masks, so stored addresses and addon output are always consistent.
