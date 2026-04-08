# API Reference

Complete reference for all available methods.

## Geolocation Facade

### `lookup($ip = null)`

Look up location for an IP address or current visitor.

```php
$details = Geolocation::lookup();
$details = Geolocation::lookup('8.8.8.8');
```

### `driver($driver)`

Use a specific provider.

```php
$details = Geolocation::driver('ipapi')->lookup('8.8.8.8');
$details = Geolocation::driver('maxmind')->lookup('8.8.8.8');
```

### `clearCache(?string $ip = null, ?string $provider = null)`

Clear geolocation cache.

```php
// Clear all geolocation cache
Geolocation::clearCache();

// Clear specific IP cache
Geolocation::clearCache('8.8.8.8');

// Clear specific provider cache
Geolocation::clearCache(null, 'ipapi');

// Clear specific IP for specific provider
Geolocation::clearCache('8.8.8.8', 'ipapi');
```

### `getCacheKey(string $ip, ?string $provider = null)`

Get cache key for an IP.

```php
$key = Geolocation::getCacheKey('8.8.8.8');
// Returns: "geolocation:ipapi:a1b2c3d4e5f6..."

$key = Geolocation::getCacheKey('8.8.8.8', 'maxmind');
// Returns: "geolocation:maxmind:a1b2c3d4e5f6..."
```

### Console Commands

Cache management via artisan commands:

```bash
# Clear all geolocation cache
php artisan geolocation:cache clear

# Clear specific provider cache
php artisan geolocation:cache clear --provider=ipapi

# Clear specific IP cache
php artisan geolocation:cache clear --provider=ipapi --ip=8.8.8.8

# Show cache info
php artisan geolocation:cache info

# Warm up cache
php artisan geolocation:cache warm-up

# Optimize cache
php artisan geolocation:cache optimize
```

---

## GeolocationDetails Methods

### Basic Location

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getIp()` | `?string` | IP address |
| `getCity()` | `?string` | City name |
| `getRegion()` | `?string` | State/Province |
| `getCountry()` | `?string` | Country name |
| `getCountryCode()` | `?string` | ISO country code (e.g., 'US') |

### Coordinates

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getLatitude()` | `?float` | Latitude |
| `getLongitude()` | `?float` | Longitude |

### Time & Timezone

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getTimezone()` | `?string` | Timezone (e.g., 'America/New_York') |
| `getTimezoneOffset()` | `?int` | UTC offset in hours |

### Currency

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getCurrency()` | `?string` | Currency name (e.g., 'US Dollar') |
| `getCurrencyCode()` | `?string` | Currency code (e.g., 'USD') |
| `getCurrencySymbol()` | `?string` | Currency symbol (e.g., '$') |

### Geographic

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getContinent()` | `?string` | Continent name |
| `getContinentCode()` | `?string` | Continent code (e.g., 'NA') |
| `getPostalCode()` | `?string` | Postal/ZIP code |

### Network

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getIsp()` | `?string` | ISP name |
| `getOrg()` | `?string` | Organization |
| `getAsn()` | `?string` | ASN (e.g., 'AS15169') |
| `getAsnName()` | `?string` | ASN organization name |
| `getConnectionType()` | `?string` | Connection type |
| `getHostname()` | `?string` | Reverse DNS |

### Security

| Method | Return Type | Description |
|--------|-------------|-------------|
| `isMobile()` | `?bool` | Is mobile connection |
| `isProxy()` | `?bool` | Is proxy/VPN |
| `isCrawler()` | `?bool` | Is bot/crawler |
| `isTor()` | `?bool` | Is Tor exit node |

### Utility

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getFormattedAddress()` | `?string` | "City, State, Country" |
| `getShortAddress()` | `?string` | "City, CountryCode" |
| `getFullAddress()` | `?string` | "City, State PostalCode, CountryCode" |
| `getGoogleMapsLink()` | `?string` | Google Maps URL |
| `getOpenStreetMapLink()` | `?string` | OpenStreetMap URL |
| `getAppleMapsLink()` | `?string` | Apple Maps URL |
| `getCountryFlag()` | `?string` | Flag emoji (🇺🇸) |
| `getCountryFlagUrl(int $width)` | `?string` | Flag CDN URL |
| `toArray()` | `array` | All data as array |
| `isValid()` | `bool` | Has valid location data |

### Time

| Method | Return Type | Description |
|--------|-------------|-------------|
| `hasTimezone()` | `bool` | Has timezone data |
| `getCurrentTime()` | `?\DateTime` | Current time in location |
| `convertToLocalTime(\DateTimeInterface)` | `?\DateTime` | Convert time to local |

---

## Facades

### IpAnonymizer

```php
use Bkhim\Geolocation\Facades\IpAnonymizer;

$anonymized = IpAnonymizer::anonymize('192.168.1.100');
$isAnon = IpAnonymizer::isAnonymized('192.168.1.100');
```

### LocationConsentManager

```php
use Bkhim\Geolocation\Facades\LocationConsentManager;

$needsConsent = LocationConsentManager::needsConsent($ip);
$hasConsent = LocationConsentManager::hasGivenConsent();
LocationConsentManager::giveConsent();
LocationConsentManager::withdrawConsent();
```

---

## Exceptions

### GeolocationException

```php
use Bkhim\Geolocation\GeolocationException;

try {
    $details = Geolocation::lookup('invalid-ip');
} catch (GeolocationException $e) {
    echo $e->getMessage();
}
```
