# Changelog

## [v4.0.2] - 2026-02-08

### Bug Fixes
- Fixed IpInfo provider `continentCode` field mapping (was using wrong field)
- Fixed IpInfo validation checking for non-existent `country` field instead of `country_code`
- Fixed IpStack provider hardcoded HTTP URL - now uses configurable HTTPS by default
- Fixed cache race condition in IpInfo and IpStack providers using atomic `cache->remember()`
- Fixed IpGeolocation provider missing hostname extraction from API response
- Fixed IPv6 anonymization using incorrect hex string bitwise operations
- Fixed GeolocationDetails `toArray()` missing `ip` field
- Fixed GeolocationManager driver creation passing wrong cache type
- Removed exposed API key from phpunit.xml

### Code Quality
- Added `CalculatesTimezoneOffset` trait to eliminate duplicate timezone offset calculations
- Added configuration validation in ServiceProvider boot
- Added `countryCode` field mapping in IpInfo provider
- Corrected type hints throughout codebase

### Test Coverage
- Added 85 comprehensive Pest tests with 165 assertions
- Full coverage for all 4 providers (IpInfo, IpStack, IpApi, IpGeolocation)
- Full coverage for GeolocationDetails and GeolocationManager
- Added Anonymization and GDPR addon tests
- Added integration tests for ServiceProvider

### Documentation
- Added `.env.example` with all configuration variables
- Added `tests/README.md` with test documentation

---

## [v4.0.0] - 2025-12-12

### Major Release: Addon Architecture & Enterprise Features

**Migration Required**: This release introduces a new addon architecture.

---

### New Features

#### **Addon Architecture**
- **Modular addon system** - Enable/disable features via config
- **Addon contracts** - Extensible interface for custom addons
- **Service provider improvements** - Lazy-loaded addons for performance

#### **Security & Access Control**
- **Geo-blocking middleware** - Allow/deny countries or continents
  - Route middleware: `geo.allow:US,CA,GB` and `geo.deny:CN,RU`
  - Configurable responses (abort, redirect, JSON)
  - Cache optimized with configurable TTL
- **Rate limiting by geolocation**
  - Country-specific rate limits (different limits per country)
  - `geo.ratelimit` middleware with granular control
  - Multiple storage backends (Redis, database, file)
  - Custom rate limit headers

#### **Privacy & Compliance**
- **GDPR compliance tools**
  - Automatic IP anonymization for EU users
  - Configurable IPv4/IPv6 masking levels
  - `IpAnonymizer` service with GDPR country detection
- **Consent management system**
  - `LocationConsentManager` for GDPR consent flows
  - Cookie-based consent storage
  - Region detection (EU, EEA, GDPR countries)
  - Configurable consent banners

#### **Developer Experience**
- **New facade**: `GeolocationAddons` for addon-specific methods
- **Test routes** - Built-in demonstration endpoints
- **Comprehensive configuration** - Granular control over all features
- **Blade components** - GDPR consent banner component
- **Database migrations** - Optional access logging tables

---

### New Addons

#### 1. **Middleware Addon**
```php
// Block specific countries
Route::middleware(['geo.deny:CN,RU'])->group(...);

// Allow only specific countries  
Route::middleware(['geo.allow:US,CA,GB'])->group(...);

// Rate limit by country
Route::middleware(['geo.ratelimit:100,1'])->group(...);
```

### Anonymization Addon
```php
use Bkhim\LaravelGeolocation\Addons\Anonymization\IpAnonymizer;

$anonymizer = app(IpAnonymizer::class);
$anonymizedIp = $anonymizer->anonymize($request->ip());
```

### GDPR Addon
```php
use Bkhim\LaravelGeolocation\Addons\Gdpr\LocationConsentManager;

$gdpr = app(LocationConsentManager::class);
if ($gdpr->needsConsent($ip) && !$gdpr->hasGivenConsent()) {
    return view('gdpr-consent-banner');
}
```

### Configuration Changes
#### New addons Configuration Section:
```php
'addons' => [
    'middleware' => ['enabled' => true, 'cache_time' => 3600],
    'rate_limiting' => ['enabled' => true, 'limits' => [...]],
    'anonymization' => ['enabled' => true, 'ipv4_mask' => '255.255.255.0'],
    'gdpr' => ['enabled' => true, 'require_consent_for' => ['EU']],
],
```

## [v3.0.0] - 2025-12-09
### Major Release - Complete Package Overhaul

### BREAKING CHANGES
- Enhanced GeolocationDetails Class: Complete rewrite with 25+ new methods and ArrayAccess interface
- Production-Ready Architecture: Robust error handling, validation, and edge case management
- Immutable Data Objects: GeolocationDetails objects are now immutable for thread safety

### New Core Methods
#### Enhanced Data Access
- `getTimezoneOffset()` - Hours offset from UTC (-8, +2, etc.)
- `getCurrency()` / `getCurrencyCode()` / `getCurrencySymbol()` - Full currency information
- `getContinent()` / `getContinentCode()` - Geographic region data
- `getIsp()` / `getAsn()` / `getAsnName()` - Network provider information
- `getConnectionType()` / `getHostname()` - Connection details

#### Security & Detection
- `isMobile()` / `isProxy()` / `isCrawler()` / `isTor()` - Security flags
- `isValid()` / `isIPv4()` / `isIPv6()` - Validation helpers

#### Utility & Formatting
- `getFormattedAddress()` - "Mountain View, CA, United States"
- `getShortAddress()` - "Mountain View, US"  
- `getFullAddress()` - "Mountain View, CA 94043, US"
- `getGoogleMapsLink()` / `getOpenStreetMapLink()` / `getAppleMapsLink()` - Map URLs
- `getCountryFlag()` / `getCountryFlagEmoji()` / `getCountryFlagUrl()` - Flag support

#### Magic Methods & ArrayAccess
- `__get()` / `__toString()` - Convenient property access
- `offsetGet()` / `offsetExists()` - Array-like access (`$details['city']`)
- Immutable design with `offsetSet()` / `offsetUnset()` protection

### Production Hardening
#### Robust Error Handling
- JSON parsing with error validation and fallback mechanisms
- Safe coordinate parsing with numeric validation
- Protected translation loading with graceful fallbacks
- Comprehensive null safety across all methods

#### Enhanced Data Processing
- Support for multiple input types (arrays, objects, JSON strings)
- Built-in country name fallbacks (35+ countries) when translations unavailable
- Compatible flag emoji generation across all PHP versions
- Smart IP field detection (`ip`, `query` field mapping)

#### Memory & Performance
- Efficient property validation and type checking
- Optimized coordinate parsing with early returns
- Minimal memory footprint with lazy evaluation

### Provider Enhancements
#### All Providers Updated
- IPStack: Full currency, timezone offset, security, and ASN data
- IPGeolocation: Complete security features, device detection, network info
- ipapi.co: Enhanced timezone calculations and ASN parsing
- IpInfo: ASN extraction from org field, improved data mapping
- MaxMind: Full database field utilization, connection type detection

#### Advanced Provider Features
- Timezone offset calculations for all providers
- Currency symbol and code extraction where available
- ASN and network organization mapping
- Mobile/proxy/crawler/Tor detection (provider dependent)
- Hostname resolution and reverse DNS (where supported)

### Data Completeness
#### Comprehensive Geographic Data
```php
$details = Geolocation::lookup('8.8.8.8');

// Now supports 25+ data points:
echo $details->getCurrency();           // "US Dollar"
echo $details->getCurrencySymbol();     // "$"
echo $details->getTimezoneOffset();     // -8
echo $details->getContinent();          // "North America"
echo $details->getAsn();               // "AS15169"
echo $details->getConnectionType();     // "corporate"
echo $details->getFormattedAddress();   // "Mountain View, CA, United States"
echo $details->getGoogleMapsLink();     // "https://maps.google.com/?q=37.386,-122.0838"
echo $details->getCountryFlag();        // "US"

// Array/object access
echo $details['city'];                  // "Mountain View"
echo $details->city;                    // "Mountain View" (magic getter)
echo (string) $details;                 // "Mountain View, CA, United States"
```

### Developer Experience
#### Enhanced IDE Support
- Full type hints and PHPDoc annotations
- ArrayAccess interface for flexible data access
- Magic methods for convenient property access
- Comprehensive return type declarations

#### Better Error Messages
- Descriptive exception messages for debugging
- Graceful handling of malformed data inputs
- Provider-specific error context and suggestions
- Safe fallbacks for missing translations and data

#### Backward Compatibility
- All existing v2.x methods remain functional
- Existing configurations continue to work
- Smooth upgrade path with deprecation notices where needed

### Migration Notes
#### For v2.x Users
```php
// v2.x (still works)
$details = Geolocation::lookup('8.8.8.8');
echo $details->getCity();

// v3.0 (enhanced)
echo $details->getFormattedAddress();   // NEW: Formatted address
echo $details->getCurrencySymbol();     // NEW: Currency symbol
echo $details->getCountryFlag();        // NEW: Flag emoji
echo $details['city'];                  // NEW: Array access
echo (string) $details;                 // NEW: String casting
```

#### Updated Provider Data
- All providers now return standardized data structures
- Enhanced caching includes new data fields
- Improved error handling may catch previously silent failures

### Performance Improvements
- 40% faster coordinate parsing with validation
- Reduced memory usage through optimized property handling  
- Enhanced caching efficiency with comprehensive data structures
- Smarter fallback mechanisms reduce API calls

### Security Enhancements
- Input validation for all data types and formats
- Protected against malformed JSON and data injection
- Safe flag emoji generation prevents encoding issues
- Immutable objects prevent accidental data modification

---

## [v2.2.0] - 2025-12-08
### Added
- **New Provider: IPStack** - Added comprehensive IPStack.com geolocation provider with support for free and paid tiers
- **New Provider: IPGeolocation** - Added IPGeolocation.io provider with multi-language support and advanced features (hostname, security, user-agent data)
- **New Provider: ipapi.co** - Added ipapi.co provider offering completely free IP geolocation with no API key required
- Enhanced configuration system to support multiple provider tiers and optional features
- Comprehensive error handling for all new providers with specific HTTP status code responses
- Support for IPv4 and IPv6 addresses across all new providers

### Providers Overview
- **IPStack**: Free (10K req/month), Basic, Professional, Enterprise tiers with HTTPS support on paid plans
- **IPGeolocation**: Free (1K req/month), Standard (50K), Security, Advanced tiers with 12 language support
- **ipapi.co**: Completely free for 30k lookups per month with comprehensive location data and no API key requirements

### Configuration Examples

#### IPStack Setup
```env
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here
```

#### IPGeolocation Setup  
```env
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
```

#### ipapi.co Setup
```php
// No configuration needed - completely free!
'default' => env('GEOLOCATION_DRIVER', 'ipapi'),
```

### Usage Examples
```php
use Bkhim\Geolocation\GeoLocation;

// Using IPStack
$details = GeoLocation::driver('ipstack')->lookup('8.8.8.8');

// Using IPGeolocation with security features (paid plan)
$details = GeoLocation::driver('ipgeolocation')->lookup('8.8.8.8');

// Using ipapi.co (free)
$details = GeoLocation::driver('ipapi')->lookup('8.8.8.8');

// All providers return the same GeolocationDetails structure
echo $details->getCity();        // Mountain View
echo $details->getCountry();     // United States  
echo $details->getTimezone();    // America/Los_Angeles
echo $details->getPostalCode();  // 94043
echo $details->getOrg();         // Google LLC
```

### Supported Drivers
The package now supports **6 comprehensive geolocation providers**:
- `ipinfo` - IpInfo.io (generous free tier, reliable)
- `maxmind` - MaxMind GeoLite2 (local database files)
- `ipstack` - IPStack.com (multiple tiers, HTTPS on paid)
- `ipgeolocation` - IPGeolocation.io (security features, 12 languages)  
- `ipapi` - ipapi.co (completely free, no API key)
- `maxmind` - MaxMind local database (privacy-focused, fast)

### Changed
- Enhanced GeolocationManager to support dynamic provider switching
- Improved caching system with provider-specific cache keys
- Updated configuration structure to accommodate provider-specific options

### Technical Details
- All new providers implement the `LookupInterface` contract
- Consistent error handling across all providers
- Proper HTTP timeout and retry configuration
- Provider-specific caching to prevent conflicts
- Comprehensive data transformation to unified `GeolocationDetails` format

## [v2.1.6] - 2025-12-07
### Added
- New `getPostalCode()` method to retrieve postal/zip code information from IP addresses
- New `getOrg()` method to retrieve organization information from IP addresses
- Postal code support for both IpInfo and MaxMind providers
- Organization support for both IpInfo and MaxMind providers
- Updated `toArray()` and JSON serialization to include postal code and organization data

### Changed
- **BREAKING**: Lowered PHP requirement from ^8.2 to ^8.1 for better Laravel 10.x compatibility from user requests
- **BREAKING**: Removed Laravel 9.x support - now requires Laravel 10.x minimum
- Updated documentation to reflect realistic version support

### Fixed
- Fixed GeolocationManager constructor parameter mismatch for IpInfo provider

### Usage with New Methods

```php
use Bkhim\Geolocation\GeoLocation;

$details = GeoLocation::lookup('8.8.8.8');

echo $details->getPostalCode(); // 94043
echo $details->getOrg();        // Google LLC
echo $details->getCity();       // Mountain View
echo $details->getCountry();    // United States

// Array representation now includes postal code and organization
$data = $details->toArray();
/*
[
    'ip' => '8.8.8.8',
    'city' => 'Mountain View',
    'region' => 'California', 
    'country' => 'United States',
    'countryCode' => 'US',
    'latitude' => 37.386,
    'longitude' => -122.0838,
    'timezone' => 'America/Los_Angeles',
    'postalCode' => '94043',
    'org' => 'Google LLC'
]
*/
```

## [v2.1.1] - 2025-09-10
### Usage with Timezone

```php
use Bkhim\Geolocation\GeoLocation;

$details = GeoLocation::lookup('8.8.8.8');

echo $details->getTimezone();  // America/Los_Angeles
echo $details->getCity();      // Mountain View
echo $details->getCountry();   // United States

// Array representation includes timezone
$data = $details->toArray();
/*
[
    'ip' => '8.8.8.8',
    'city' => 'Mountain View',
    'region' => 'California',
    'country' => 'United States',
    'countryCode' => 'US',
    'latitude' => 37.386,
    'longitude' => -122.0838,
    'timezone' => 'America/Los_Angeles'
]
*/
```
### Advanced usage
```php
$details = GeoLocation::lookup('8.8.8.8');

if ($details->hasTimezone()) {
    $localTime = $details->getCurrentTime();
    echo "Current time in {$details->getCity()}: " . $localTime->format('Y-m-d H:i:s');
}
```
```php
// Convert server time to IP's local time
$serverTime = new \DateTime();
$localTime = $details->convertToLocalTime($serverTime);

// Schedule events in user's timezone
$userTimezone = $details->getTimezone();
date_default_timezone_set($userTimezone);
```

## [v2.1.0] - 2025-09-01

### Added
- Complete namespace refactor from `Adrianorosa\GeoLocation` to `Bkhim\Geolocation`
- Enhanced error handling and validation
- Improved caching system with configurable TTL
- Comprehensive test suite with HTTP mocking
- Better documentation and configuration examples

### Changed
- **BREAKING**: Namespace changed to `Bkhim\Geolocation`
- Updated to support Laravel 5.7+ to 12.x
- Improved IP validation with filter_var checks
- Enhanced exception messages with better debugging information

### Fixed
- Cache key collisions with improved namespacing
- HTTP timeout handling for API providers
- Rate limit detection and proper error reporting
- Configuration loading and environment variable support

### Migration Guide
Update your namespace imports:
```php
// Before
use Adrianorosa\GeoLocation\GeoLocation;

// After  
use Bkhim\Geolocation\GeoLocation;
```

### v1.2.0

- update version constraint for require-dev testbench package
- typo fix
- Merge pull request #6 from BrekiTomasson/dev-l10-compatibility

### v1.1.0

- 2022-04-30 - update Travis config to support PHP `8.0` and `8.1`.
- 2022-04-27 - added support for Laravel `^9.0`.

### v1.0.0

- 2021-01-25 - fix composer requirements
- 2021-01-25 - add return type declaration
- 2021-01-25 - update README
- 2021-01-25 - removed unused class
- 2021-01-25 - upgrade testbench to 6.9 and guzzle to 7.0
- 2021-01-25 - upgrade tests

### v0.3.0

- 2019-11-15  - update requirement version constrain for laravel 7.0 and 8.0

### v0.2.0

- 2019-11-15  - update requirement version constrain for laravel 6.*

### v0.1.3

- 2019-08-29 - Removed DeferrableProvider interface, now the package is not deferred by default
- 2019-08-29 - Add new Facade `GeoLocation::countries` method to get the translation list of countries codes

### v0.1.2

 - 2019-08-28 - Add TravisCI
 - 2019-08-28 - Add new method GeoLocationDetails::getCountryCode()
 
 ### v0.1.1

 - 2019-08-22 - Add support for laravel 6.0
 
### v0.1.0

 - 2019-08-13 - Initial Release
