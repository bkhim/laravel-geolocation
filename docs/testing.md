# Testing

This package includes comprehensive tests using Pest PHP.

## Running Tests

The suite currently contains **187 tests / 365 assertions**.

```bash
# Run all tests
composer test          # = vendor/bin/pest

# Run a specific file
vendor/bin/pest tests/Unit/Providers/IpApiTest.php

# Run with coverage (requires xdebug/pcov)
vendor/bin/pest --coverage
```

## Test Structure

```
tests/
├── Feature/
│   └── GeolocationServiceProviderTest.php
├── Unit/
│   ├── Addons/
│   │   ├── AnonymizationTest.php
│   │   ├── GdprTest.php
│   │   └── Middleware/
│   │       ├── GeoMiddlewareTest.php
│   │       └── GeoRateLimitMiddlewareTest.php
│   ├── Config/
│   │   └── GeolocationConfigTest.php
│   ├── Console/GeolocationCommandTest.php
│   ├── Models/IpBlocklistTest.php
│   ├── Providers/ (6 provider suites)
│   ├── Services/
│   │   ├── AnomalyDetectorTest.php
│   │   ├── AuditLoggerTest.php
│   │   └── ThreatIntelligenceServiceTest.php
│   ├── Traits/
│   │   ├── CachesGeolocationDataTest.php
│   │   └── HasGeolocationTest.php
│   ├── GeolocationDetailsTest.php
│   └── GeolocationManagerTest.php
└── TestCase.php
```

## Writing Tests

```php
test('geolocation lookup returns correct data', function () {
    $details = Geolocation::lookup('8.8.8.8');
    
    expect($details->getCountryCode())->toBe('US');
    expect($details->getCity())->toBe('Mountain View');
});

test('can use specific driver', function () {
    $details = Geolocation::driver('maxmind')->lookup('8.8.8.8');
    
    expect($details)->toBeInstanceOf(GeolocationDetails::class);
});

test('handles invalid IP', function () {
    expect(fn () => Geolocation::lookup('invalid-ip'))
        ->toThrow(GeolocationException::class);
});
```

## Mocking Providers

```php
test('uses fallback when primary fails', function () {
    config(['geolocation.fallback.enabled' => true]);
    config(['geolocation.fallback.order' => ['ipinfo', 'maxmind']]);
    
    $details = Geolocation::lookup('8.8.8.8');
    
    expect($details->getCountryCode())->toBe('US');
});
```

## Testing Events

```php
test('dispatches HighRiskIpDetected event', function () {
    Event::fake();
    
    // Trigger detection
    $details = Geolocation::lookup('8.8.8.8');
    // ... trigger high-risk condition
    
    Event::assertDispatched(HighRiskIpDetected::class);
});
