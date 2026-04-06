# Quick Start

Get up and running with Laravel Geolocation in minutes.

## Basic Lookup

```php
use Bkhim\Geolocation\Geolocation;

// Detect visitor location automatically
$details = Geolocation::lookup();

// Lookup specific IP address
$details = Geolocation::lookup('8.8.8.8');

// Access location data
echo $details->getCity();           // Mountain View
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getLatitude();       // 37.386
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
echo $details->getCurrencyCode();   // USD
echo $details->getCountryFlag();    // 🇺🇸
```

## Use Specific Provider

```php
// Use ipapi (default, free tier)
$details = Geolocation::driver('ipapi')->lookup('8.8.8.8');

// Use IP2Location.io (advanced fraud detection)
$details = Geolocation::driver('ip2location')->lookup('8.8.8.8');

// Use MaxMind (local database)
$details = Geolocation::driver('maxmind')->lookup('8.8.8.8');
```

## Security Detection

```php
$details = Geolocation::lookup($request->ip());

// Check for suspicious indicators
if ($details->isProxy()) {
    // User is using a proxy/VPN
}

if ($details->isTor()) {
    // User is on Tor network
}

if ($details->isCrawler()) {
    // Bot/crawler detected
}

if ($details->isMobile()) {
    // Mobile connection detected
}
```

## Address Formatting

```php
$details = Geolocation::lookup('8.8.8.8');

// Different address formats
echo $details->getFormattedAddress(); // "Mountain View, CA, United States"
echo $details->getShortAddress();     // "Mountain View, US"
echo $details->getFullAddress();      // "Mountain View, CA 94043, US"

// Map links
echo $details->getGoogleMapsLink();   // https://maps.google.com/?q=37.386,-122.0838
echo $details->getOpenStreetMapLink(); // https://www.openstreetmap.org/...
echo $details->getAppleMapsLink();    // maps://maps.apple.com/...
```

## Error Handling

```php
use Bkhim\Geolocation\GeolocationException;

try {
    $details = Geolocation::lookup('invalid-ip');
} catch (GeolocationException $e) {
    logger()->error('Geolocation failed: ' . $e->getMessage());
}
```

## Artisan Commands

```bash
# Test geolocation lookup
php artisan geolocation:lookup 8.8.8.8

# Clear geolocation cache
php artisan geolocation:cache:clear
```

## Next Steps

- [Provider Comparison](../providers/index.md) - Choose your provider
- [Security Features](../security/mfa-integration.md) - MFA triggers and fraud prevention
- [API Reference](../api-reference.md) - All available methods
