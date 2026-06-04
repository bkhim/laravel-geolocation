# Testing

This package includes comprehensive tests using Pest PHP.

## Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/pest tests/Unit/Providers/IpApiTest.php

# Run with coverage
vendor/bin/pest --coverage
```

## Test Structure

```
tests/
├── Feature/           # Integration tests
│   └── GeolocationTest.php
├── Unit/              # Unit tests
│   ├── Providers/     # Provider tests
│   │   ├── IpApiTest.php
│   │   ├── IpInfoTest.php
│   │   ├── Ip2LocationTest.php
│   │   ├── MaxMindTest.php
│   │   ├── IpStackTest.php
│   │   └── IpGeolocationTest.php
│   ├── GeolocationDetailsTest.php
│   ├── GeolocationManagerTest.php
│   └── Addons/        # Addon tests
│       ├── AnonymizationTest.php
│       └── GdprTest.php
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
