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
// Clear one IP for one provider (works without cache tags)
Geolocation::clearCache('8.8.8.8', 'ipapi');

// Clear one IP for the default provider
Geolocation::clearCache('8.8.8.8');

// Clear every geolocation entry (requires cache tags + taggable store)
Geolocation::clearCache();
```

> Bulk clearing returns `false` when `cache.tags.enabled` is off, because the
> package will not flush an untagged shared cache.

### `getCacheKey(string $ip, ?string $provider = null)`

Get cache key for an IP.

```php
$key = Geolocation::getCacheKey('8.8.8.8');
// Returns: "{prefix}:ipapi:{md5}"

$key = Geolocation::getCacheKey('8.8.8.8', 'maxmind');
// Returns: "{prefix}:maxmind:{md5}"

// The prefix is configurable via GEOLOCATION_CACHE_PREFIX.
```

### `countries($locale = null)`

Return the bundled ISO country-code → name map.

```php
$countries = Geolocation::countries();      // ['US' => 'United States', ...]
$countries = Geolocation::countries('pt_BR');
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
| `getCountryFlagEmoji()` | `?string` | Alias of `getCountryFlag()` |
| `getCountryFlagUrl(int $width)` | `?string` | Flag CDN URL |
| `hasCoordinates()` | `bool` | Both lat/long present (0 is valid) |
| `toArray()` | `array` | All data as array |
| `isValid()` | `bool` | IP + country + coordinates present |

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

// Applies the configured policy (enabled + country scope)
$anonymized = IpAnonymizer::anonymize('8.8.8.1');   // "8.8.8.0"

// Force masking regardless of policy (used by storage decisions)
$masked = IpAnonymizer::mask('8.8.8.1');            // "8.8.8.0"

// Inspect
IpAnonymizer::isEnabled();          // bool
IpAnonymizer::scope();              // "none" | "all" | "gdpr"
IpAnonymizer::shouldAnonymize($ip); // bool
```

### ThreatIntelligence

```php
use Bkhim\Geolocation\Facades\ThreatIntelligence;

ThreatIntelligence::checkIp('1.2.3.4');   // ?array (cached)
ThreatIntelligence::isThreat('1.2.3.4');  // bool
ThreatIntelligence::forget('1.2.3.4');    // clear the cached reputation
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
