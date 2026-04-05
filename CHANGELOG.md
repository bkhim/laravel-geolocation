# Changelog

## [v4.3.0] - 2026-04-05

### New Provider: IP2Location.io

Added **IP2Location.io** as the 6th geolocation provider with comprehensive feature support and flexible pricing options.

#### Key Features
- **Free Tier Available**: 1,000 requests/day without API key, or 50,000 requests/month with free API key
- **Comprehensive Data**: Full geolocation data including coordinates, timezone, postal codes
- **Security Features**: Basic proxy detection (advanced security features available in paid plans)
- **Network Intelligence**: ASN information, ISP details, connection type detection
- **Mobile Detection**: Advanced mobile carrier detection using MCC/MNC codes
- **Multi-language Support**: 22 supported languages for location names (paid plans only)

#### Configuration
```env
# IP2Location.io Configuration
GEOLOCATION_IP2LOCATIONIO_API_KEY=your_api_key_here
GEOLOCATION_IP2LOCATIONIO_LANGUAGE=en
```

#### API Response Fields Supported
- **Location**: IP, country (name + code), region, city, coordinates, postal code
- **Timezone**: UTC offset with proper DST handling
- **Network**: ASN (with AS prefix), AS name, ISP information
- **Connection**: Connection type mapping (dialup, broadband, corporate, datacenter, satellite)
- **Security**: Basic proxy detection (is_proxy field)
- **Mobile**: Intelligent mobile detection based on MCC/MNC/carrier data

#### Implementation Quality
- **Production Ready**: Comprehensive error handling with specific HTTP status code responses
- **Robust Testing**: 12 test cases with 31 assertions including real API integration tests  
- **Field Consistency**: ASN format standardized with 'AS' prefix to match other providers
- **Cache Integration**: Full Laravel cache support with configurable TTL
- **Documentation**: Complete environment variable documentation and usage examples

#### Usage Example
```php
// Using IP2Location.io provider
config(['geolocation.driver' => 'ip2locationio']);

$location = app('geolocation')->lookup('8.8.8.8');
echo $location->getCountry(); // "United States of America"
echo $location->getAsn();     // "AS15169" (with standardized AS prefix)
echo $location->isProxy();    // false
```

#### Pricing Tiers
- **Free**: 1,000 requests/day (no signup) or 50,000/month (with free account)
- **Paid Plans**: Enhanced features including advanced security, fraud scoring, detailed proxy analysis

### Bug Fixes
- **IP2Location.io Translation Handling**: Fixed error 10004 ("Translation is not available with your plan") by intelligently skipping language parameter for free tier accounts and implementing automatic retry without translation when error occurs

### Code Quality Improvements
- **Standardized Naming**: All provider classes now follow strict PascalCase convention
- **Environment Variables**: Consistent `GEOLOCATION_` prefix across all provider configurations  
- **Error Handling**: Enhanced error handling patterns with specific HTTP status codes
- **Test Coverage**: Added real API integration testing capabilities

---

## [v4.2.0] - 2026-03-25

### Compatibility Updates

- **Laravel 13 Support**: Added full compatibility with Laravel 13.x
- **PHP Version**: Updated minimum PHP requirement to 8.2
- **Testbench Update**: Updated orchestra/testbench to support Laravel 13 in dev dependencies

---

## [v4.1.0] - 2026-03-20

### Security & Fraud Prevention Features

This release transforms the package into a **geo-intelligence and security platform** with built-in fraud prevention capabilities.

#### User Trait Integration
Added modular traits for integrating geolocation into User models:

- **HasGeolocation Trait**: Core functionality for recording and retrieving user login locations
  - `recordLoginLocation($ip)` - Records a login with IP and geolocation data
  - `getLastLogin()` - Returns the user's last login record
  - `getLastLoginCountry()` - Returns the country of the last login
  - `isLoginFromNewCountry($ip)` - Checks if login is from a new country
  - `isLoginFromNewCity($ip)` - Checks if login is from a new city
  - `loginHistories()` - Eloquent relationship to login history

- **HasGeolocationSecurity Trait**: Security-focused features for fraud prevention
  - `requiresMfaDueToLocation($ip)` - Determines if MFA is required
  - `isHighRiskLogin($ip)` - Configurable risk scoring system (threshold: 70)
  - `getRiskScore($ip)` - Returns detailed risk score with breakdown (score, triggers, threshold)
  - `getSuspiciousLoginCount()` - Returns count of suspicious logins
  - `getLastLoginRiskLevel()` - Returns 'low', 'high', or 'critical'
  - Automatic event dispatching for security monitoring

- **HasGeolocationPreferences Trait**: Personalization features
  - `getDetectedTimezone()` - Detects user's timezone from login history
  - `getLocalCurrency()` - Returns user's local currency code

- **LoginHistory Model**: Eloquent model for storing login records with fields:
  - IP address, country, city, timezone, currency
  - Security flags (is_proxy, is_tor, is_crawler, is_mobile)
  - Risk score and risk level

#### Events System
New event classes for security monitoring:
- `LoginLocationRecorded` - Fired when a login location is recorded
- `SuspiciousLocationDetected` - Fired when suspicious activity is detected
- `HighRiskIpDetected` - Fired when high-risk login is detected
- `GeoBlockedRequest` - Fired when a geo-blocked request is blocked

#### Database Migration
Added migration for `user_login_locations` table with comprehensive fields for security tracking.

#### Configuration Enhancements
New configuration sections:
- `user_trait` - Login history table name and caching options
- `security` - MFA trigger, risk thresholds, scoring rules, trusted countries/IPs
- `personalization` - Timezone and currency detection settings

### API Improvements
- Added `getDetails()` method alias to GeolocationManager for trait compatibility

### Usage Example

```php
// Add traits to User model
class User extends Authenticatable
{
    use HasGeolocation, HasGeolocationSecurity, HasGeolocationPreferences;
}

// Record login and check security
$user->recordLoginLocation($request->ip());

if ($user->isHighRiskLogin($request->ip())) {
    // Trigger additional verification
}

// Personalization
date_default_timezone_set($user->getDetectedTimezone());
```

---

## [v4.0.7] - 2026-03-07

### Bug Fixes
- Fixed IpInfo provider validation - now accepts both `country` and `country_code` fields from API
- Fixed MaxMind provider `country` field mapping - now returns ISO code instead of country name
- Fixed MaxMind provider security fields (`isMobile`, `isProxy`) using correct property names
- Fixed cache race condition in IpApi provider using atomic `cache->remember()`
- Fixed type hint in GeolocationManager - now uses `Illuminate\Contracts\Cache\Repository` interface

### Code Quality
- Standardized internal country field mapping across all providers (country field now contains ISO code)
- Updated IPStack documentation with correct free tier limits (100 requests/month)
- Updated IPGeolocation documentation with current pricing and feature information
- Removed deprecated `IPSTACK_SECURE` configuration (HTTPS now supported on all tiers)
- Improved country code transformation consistency in GeolocationDetails

### Documentation
- Updated README with accurate provider pricing, features, and limitations
- Added Laravel 11+ service provider registration instructions
- Fixed IPStack free tier documentation (100 requests/month, not 10,000)
- Updated IPGeolocation pricing and feature matrix

---


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
- **IPStack**: Free (100 req/month), Basic ($12.99/mo), Professional ($59.99/mo), Professional Plus ($99.99/mo) tiers with HTTPS support on paid plans
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
