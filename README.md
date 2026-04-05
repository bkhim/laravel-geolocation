# Laravel Geolocation Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![License](https://img.shields.io/packagist/l/bkhim/laravel-geolocation.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/bkhim/laravel-geolocation.svg)](https://php.net)

IP geolocation for Laravel with **built-in fraud prevention and security features**. Detect proxy/VPN/Tor, trigger MFA on suspicious logins, track user login locations with risk scoring, and personalize user experience with timezone and currency detection. Perfect for authentication security, fraud prevention, geo-targeting, and user personalization.

> **Release**: v4.2.0 — Stable (2026-03-25). This release adds Laravel 13 compatibility. See [CHANGELOG.md](CHANGELOG.md) for details.

## Quick Installation

Install the package via Composer:

```bash
composer require bkhim/laravel-geolocation
```

The package uses Laravel's auto-discovery to register the service provider and facades automatically.

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

Set your provider API keys in `.env` and you're ready to use geolocation in your application!

## Table of Contents

- [Features](#features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)
- [Testing](#testing)
- [Contributing](#contributing)
- [License & Support](#license--support)

## Features

### Geolocation Providers
- **5 Providers**: IpInfo.io, MaxMind GeoIP2, IPStack.com, IPGeolocation.io, and ipapi.co for reliable IP geolocation
- **Free & Premium Options**: From completely free IP geolocation to enterprise-grade location services

### Security & Fraud Prevention
- **Proxy/VPN Detection**: Detect anonymizing services on login
- **Tor Exit Node Detection**: Identify Tor browser users
- **Crawler/Bot Detection**: Flag automated access
- **Configurable Risk Scoring**: Point-based system for threat assessment
- **MFA Triggers**: Automatically require 2FA on suspicious logins
- **Security Events**: Laravel events for Slack alerts, email notifications, webhooks
- **Login Location Tracking**: Record and monitor user login history by country/city

### User Personalization
- **Timezone Detection**: Automatically detect user timezone from IP
- **Currency Detection**: Show prices in user's local currency
- **Geographic Personalization**: Customize content by region

### Developer Experience
- **Rich Location Data**: City, region, country, GPS coordinates, timezone, postal codes, currency, ISP
- **Advanced Caching**: Configurable TTL, Redis/Memcached support, cache tags
- **Provider Fallback**: Automatic failover when primary provider fails
- **Laravel 10+ Compatible**: Full support for Laravel 10.x through 13.x with PHP 8.2+
- **Production-Ready**: Comprehensive error handling, IP validation, fallback mechanism
- **Modular Addons**: GDPR consent management, IP anonymization, geo-blocking middleware, rate limiting

## Use Cases

### Security & Authentication
- **Login Security**: Trigger MFA on suspicious logins (new country, VPN, Tor)
- **Fraud Prevention**: Detect proxy/VPN/Tor on checkout, login, or account changes
- **Account Protection**: Monitor login locations and alert on unusual activity
- **Risk-Based Access Control**: Require additional verification based on risk score

### User Personalization
- **Timezone Detection**: Display times in user's local timezone automatically
- **Currency Detection**: Show prices in user's local currency
- **Regional Content**: Customize content based on user's country/city

### Geo-Targeting & Compliance
- **Geo-Blocking**: Allow/deny access by country with middleware
- **GDPR Compliance**: IP anonymization and consent management
- **E-commerce**: Shipping zones, tax calculation, regional pricing

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, 12.x, or 13.x
- Composer
- For MaxMind provider: MaxMind GeoLite2 database (free) or GeoIP2 database (paid)

## Installation

Install the package via Composer:

```bash
composer require bkhim/laravel-geolocation
```

The package uses Laravel's auto-discovery to register the service provider and facades. For manual registration (optional):

**For Laravel 10 and earlier**, add to `config/app.php`:

```php
'providers' => [
    Bkhim\Geolocation\GeolocationServiceProvider::class,
],

'aliases' => [
    'Geolocation' => Bkhim\Geolocation\Geolocation::class,
    'IpAnonymizer' => Bkhim\Geolocation\Facades\IpAnonymizer::class,
    'LocationConsentManager' => Bkhim\Geolocation\Facades\LocationConsentManager::class,
],
```

**For Laravel 11+**, add the service provider to `bootstrap/providers.php`:

```php
return [
    // Other service providers...
    Bkhim\Geolocation\GeolocationServiceProvider::class,
];
```

And add aliases to `config/app.php`:

```php
'aliases' => [
    'Geolocation' => Bkhim\Geolocation\Geolocation::class,
    'IpAnonymizer' => Bkhim\Geolocation\Facades\IpAnonymizer::class,
    'LocationConsentManager' => Bkhim\Geolocation\Facades\LocationConsentManager::class,
],
```

### Publish Configuration

Publish the configuration file to customize your geolocation settings:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

This will create `config/geolocation.php` where you can configure providers, caching, and addons.

## Configuration

After publishing the configuration, set your environment variables in `.env`. See `.env.example` for all available options.

### Environment Variables

```env
# Default driver (ipapi, ipinfo, ipstack, ipgeolocation, ip2locationio, maxmind)
GEOLOCATION_DRIVER=ipapi

# Request & Cache Configuration
GEOLOCATION_TIMEOUT=5
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100

# IpInfo Configuration
# Get your token from: https://ipinfo.io/account/token
# Free (Lite) plan: Unlimited requests, country/continent data only
# Core plan ($49/mo): Full geolocation data (city, region, coordinates, timezone, postal)
# Plus plan ($74/mo): Additional privacy detection, accuracy radius, change tracking
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here

# IPStack Configuration
# Get your API key from: https://ipstack.com/dashboard
# Free tier: 100 requests/month with HTTPS support
# Paid plans: Higher limits and comprehensive data (Basic: $12.99/mo, Professional: $59.99/mo, Professional Plus: $99.99/mo)
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here


# IPGeolocation Configuration
# Get your API key from: https://ipgeolocation.io/dashboard
# Free tier: 1,000 requests/month (API credits)
# Paid plans offer higher limits and additional features (security, hostname, useragent, multi-language, company data)
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
IPGEOLOCATION_INCLUDE_USERAGENT=false

# IP2Location.io Configuration
# Get your API key from: https://ip2location.io/dashboard
# Free tier: 1,000 requests/day without API key, or 50,000 requests/month with a free API key
# Paid plans offer higher limits and additional features (proxy & security, continent, currency, geolocation add-on data)
GEOLOCATION_IP2LOCATIONIO_API_KEY=your_api_key_here
# Language support is only available on PAID PLANS - free tier only supports 'en'
GEOLOCATION_IP2LOCATIONIO_LANGUAGE=en

# MaxMind Configuration
# Download free GeoLite2 database from: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
# Or purchase GeoIP2 database for higher accuracy
# Requires local database file - no API rate limits, works offline
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key

# Addon Configuration
GEOLOCATION_MIDDLEWARE_ENABLED=false
GEOLOCATION_RATE_LIMITING_ENABLED=false
GEOLOCATION_ANONYMIZATION_ENABLED=false
GEOLOCATION_GDPR_ENABLED=false
```

### Provider Selection

Choose the driver that fits your needs:

| Provider | Free Tier | API Key Required | HTTPS | Special Features |
|----------|-----------|------------------|-------|------------------|
| **ipapi.co** | ✅ 30K/month* | ❌ No | ✅ Yes | No API key required, comprehensive data, paid plans available |
| **IpInfo** | ✅ Unlimited (Lite) | ✅ Yes | ✅ Yes | Lite plan (country only), paid plans for full location data |
| **IPStack** | ✅ 100/month | ✅ Yes | ✅ Yes | Comprehensive data, paid plans available |
| **IPGeolocation** | ✅ 1K/month | ✅ Yes | ✅ Yes | Security detection, company data, multi-language, paid plans available |
| **IP2Location.io** | ✅ 1K/day, 50k/month | ✅ Yes and ❌ No | ✅ Yes | Keyless API key available, proxy & security, continent, currency, geolocation add-on data, multi-language, paid plans available |
| **MaxMind** | ✅ Local DB | ❌ No | N/A | Privacy-focused, fastest, offline |



**Recommendation by Use Case**:
- **Getting Started**: Use **ipapi.co** (no API key, full geolocation data on free tier) or **IpInfo Lite** (unlimited requests, country-level only)
- **Production Apps**: Use **ipapi.co paid plans** (scalable, comprehensive data) or **IpInfo Core/Plus** (advanced features)
- **High Volume**: Use **MaxMind** (local database, no API limits) or **ipapi.co Enterprise** (high-volume API)
- **Advanced Features**: Use **IPGeolocation** (security data, translations), **IP2Location.io** (proxy & security, continent, currency, geolocation add-on data, translations) or **IpInfo Plus** (privacy detection)
- **Enterprise**: Use **IPStack** (comprehensive data), **IPGeolocation Enterprise** (security & compliance), or **ipapi.co Custom** (tailored solutions)

## Usage

### Basic Lookup

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
```

### Specific Driver Usage

```php
// Use specific drivers
$ipapiDetails = Geolocation::driver('ipapi')->lookup('8.8.8.8');          // Free
$ipinfoDetails = Geolocation::driver('ipinfo')->lookup('8.8.8.8');        // Popular
$ipstackDetails = Geolocation::driver('ipstack')->lookup('8.8.8.8');      // Feature-rich
$ipgeoDetails = Geolocation::driver('ipgeolocation')->lookup('8.8.8.8');  // Advanced
$iplDetails = Geolocation::driver('ip2locationio')->lookup('8.8.8.8');  // Advanced
$maxmindDetails = Geolocation::driver('maxmind')->lookup('8.8.8.8');      // Local database
```

### Display User Location with Flag

```php
$details = Geolocation::lookup();

echo $details->getCountryFlag() . ' ' . $details->getFormattedAddress();
// Output: 🇺🇸 Mountain View, CA, United States

echo "Welcome visitor from " . $details->getShortAddress() . "!";
// Output: Welcome visitor from Mountain View, US!
```

### Create Map Links

```php
$details = Geolocation::lookup('8.8.8.8');

// Google Maps
$googleUrl = $details->getGoogleMapsLink();
echo "<a href='{$googleUrl}' target='_blank'>View on Google Maps</a>";

// Apple Maps (for mobile Safari)
$appleUrl = $details->getAppleMapsLink();
echo "<a href='{$appleUrl}'>Open in Apple Maps</a>";

// OpenStreetMap
$osmUrl = $details->getOpenStreetMapLink();
echo "<a href='{$osmUrl}' target='_blank'>View on OpenStreetMap</a>";
```

### Security & Network Analysis

```php
$details = Geolocation::lookup($userIP);

if ($details->isProxy()) {
    echo "⚠️ Proxy/VPN detected from " . $details->getIsp();
}

if ($details->isTor()) {
    echo "🔒 Tor exit node detected";
}

if ($details->isCrawler()) {
    echo "🤖 Bot/crawler detected: " . $details->getHostname();
}

echo "ISP: " . $details->getIsp();
echo "ASN: " . $details->getAsn() . " (" . $details->getAsnName() . ")";
echo "Connection: " . $details->getConnectionType();
```

### Error Handling

```php
try {
    $details = Geolocation::lookup('8.8.8.8');
} catch (\Bkhim\Geolocation\GeolocationException $e) {
    // Handle errors (invalid IP, API failures, etc.)
    logger()->error('Geolocation failed: ' . $e->getMessage());
}
```

### Artisan Commands

The package includes several Artisan commands:

```bash
# Test geolocation lookup
php artisan geolocation:lookup [ip]

# Clear geolocation cache
php artisan geolocation:cache:clear
```

## API Reference

### GeolocationDetails Methods

The `GeolocationDetails` object returned by `Geolocation::lookup()` provides the following methods:

#### Basic Location Information
- `getIp()` - IP address
- `getCity()` - City name
- `getRegion()` - State/Province name
- `getCountry()` - Country name (translated if available)
- `getCountryCode()` - ISO country code (e.g., 'US', 'GB')

#### Coordinates
- `getLatitude()` - Latitude coordinate
- `getLongitude()` - Longitude coordinate

#### Time & Timezone
- `getTimezone()` - Timezone identifier (e.g., 'America/New_York')
- `getTimezoneOffset()` - Hours offset from UTC (e.g., -5, +2)

#### Currency Information
- `getCurrency()` - Currency name (e.g., 'US Dollar')
- `getCurrencyCode()` - Currency code (e.g., 'USD')
- `getCurrencySymbol()` - Currency symbol (e.g., '$')

#### Geographic Regions
- `getContinent()` - Continent name (e.g., 'North America')
- `getContinentCode()` - Continent code (e.g., 'NA')

#### Additional Data
- `getPostalCode()` - Postal/ZIP code
- `getOrg()` - Organization/ISP name

#### Network & ISP Information
- `getIsp()` - Internet Service Provider name
- `getAsn()` - Autonomous System Number (e.g., 'AS15169')
- `getAsnName()` - ASN organization name
- `getConnectionType()` - Connection type (e.g., 'corporate', 'residential')
- `getHostname()` - Hostname/reverse DNS

#### Security & Device Detection
- `isMobile()` - Is mobile connection
- `isProxy()` - Is proxy/VPN
- `isCrawler()` - Is web crawler/bot
- `isTor()` - Is Tor exit node

#### Utility Methods
- `getFormattedAddress()` - "Mountain View, CA, United States"
- `getShortAddress()` - "Mountain View, US"
- `getFullAddress()` - "Mountain View, CA 94043, US"
- `getGoogleMapsLink()` - Google Maps link with coordinates
- `getCountryFlag()` - Country flag emoji (🇺🇸)
- `getCountryFlagUrl()` - URL to flag image with customizable width
- `toArray()` - All data as associative array
- `jsonSerialize()` - JSON representation

## Advanced Features

### Caching

The package includes a high-performance caching system. Configure cache settings in `config/geolocation.php`:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 86400, // seconds
    'store' => 'redis', // optional, uses default cache store
],
```

### Fallback Configuration

Enable fallback to another provider if the primary fails:

```php
'fallback' => [
    'enabled' => true,
    'order' => ['ipinfo', 'maxmind'],
    'max_attempts' => 2,
],
```

### Logging

Log geolocation requests for debugging:

```php
'logging' => [
    'enabled' => true,
    'level_success' => 'info',
    'level_error' => 'error',
],
```

### Custom HTTP Client Options

You can customize HTTP client options per provider in the configuration:

```php
'ipinfo' => [
    'driver' => 'ipinfo',
    'access_token' => env('GEOLOCATION_IPINFO_ACCESS_TOKEN'),
    'client_options' => [
        'timeout' => 10,
        'connect_timeout' => 5,
        'headers' => [
            'User-Agent' => 'Your-App-Name/1.0',
        ],
    ],
],
```

### Translation Support

Country names can be translated. Publish translation files:

```bash
php artisan vendor:publish --tag="geolocation-translations"
```

Then set your application locale:

```php
app()->setLocale('pt');
$details = Geolocation::lookup('8.8.8.8');
echo $details->getCountry(); // "Estados Unidos" instead of "United States"
```

### IP Anonymization (GDPR/Privacy)

Anonymize IP addresses for privacy and GDPR compliance:

```php
use IpAnonymizer;

$anonIp = IpAnonymizer::anonymize($ipAddress);
```

Enable the anonymization addon in your `.env`:

```env
GEOLOCATION_ANONYMIZATION_ENABLED=true
```

### GDPR Consent Management

Manage GDPR consent for location tracking:

```php
use LocationConsentManager;

// Check if consent is needed for an IP
$needsConsent = LocationConsentManager::needsConsent($ipAddress);

// Check if user has given consent
$hasConsent = LocationConsentManager::hasGivenConsent();

// Give consent (set cookie)
LocationConsentManager::giveConsent();

// Withdraw consent (remove cookie)
LocationConsentManager::withdrawConsent();
```

Enable the GDPR addon in your `.env`:

```env
GEOLOCATION_GDPR_ENABLED=true
```

### Middleware & Rate Limiting

The package includes middleware for geo-based access control and rate limiting. Enable in configuration:

```env
GEOLOCATION_MIDDLEWARE_ENABLED=true
GEOLOCATION_RATE_LIMITING_ENABLED=true
```

### User Trait Integration

The package provides modular traits for integrating geolocation into your User model. These traits can be used individually or together to add geolocation capabilities to your application.

#### Installation

First, publish the migration for login history tracking:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --tag="geolocation-migrations"
php artisan migrate
```

Then add the traits to your User model:

```php
use Bkhim\Geolocation\Traits\HasGeolocation;
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;
use Bkhim\Geolocation\Traits\HasGeolocationPreferences;

class User extends Authenticatable
{
    use HasGeolocation;
    // use HasGeolocationSecurity; // Uncomment for security features
    // use HasGeolocationPreferences; // Uncomment for personalization
}
```

#### HasGeolocation Trait

The core trait for recording and retrieving user login locations:

```php
$user = User::find(1);

// Record a login with IP
$user->recordLoginLocation($request->ip());

// Get last login information
$lastLogin = $user->getLastLogin();
echo $lastLogin->country; // "United States"

// Check if login is from a new country
if ($user->isLoginFromNewCountry($request->ip())) {
    // Alert user about new country login
}

// Check if login is from a new city
if ($user->isLoginFromNewCity($request->ip())) {
    // Alert user about new city login
}

// Access login history
$history = $user->loginHistories()->orderBy('occurred_at', 'desc')->get();
```

#### HasGeolocationSecurity Trait

Security-focused trait for detecting suspicious logins:

```php
// Check if MFA is required based on location
if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.verify');
}

// Check if login is high risk (uses configurable scoring)
if ($user->isHighRiskLogin($request->ip())) {
    // Trigger additional verification
}

// Get detailed risk score with breakdown
$riskAnalysis = $user->getRiskScore($request->ip());
// Returns: [
//     'score' => 45,
//     'is_high_risk' => false,
//     'threshold' => 70,
//     'triggers' => ['new_country' => true, 'new_city' => true],
//     'trusted_country' => false,
// ]

// Get risk level of last login
$risk = $user->getLastLoginRiskLevel(); // Returns: 'low', 'high', or 'critical'

// Get count of suspicious logins
$suspiciousCount = $user->getSuspiciousLoginCount();
```

**Risk Scoring System**: The trait uses a configurable scoring system to determine high-risk logins:

```php
// In config/geolocation.php
'security' => [
    'high_risk_threshold' => 70, // Score threshold for high risk
    'trusted_countries' => ['US', 'CA', 'GB'], // Countries that bypass scoring
    'trusted_ips' => ['192.168.1.1'], // IPs that bypass scoring
    'rules' => [
        'proxy' => 40,
        'tor' => 80,
        'crawler' => 20,
        'new_country' => 30,
        'new_city' => 15,
    ],
    'custom_rules' => [
        // Add custom rule classes: 'App\Rules\HighRiskAsnScore'
    ],
],
```

**Events Fired**: The trait dispatches events for security monitoring:

- `LoginLocationRecorded` - When a login location is recorded
- `SuspiciousLocationDetected` - When MFA is triggered
- `HighRiskIpDetected` - When high-risk login is detected

```php
// Listen for security events
Event::listen(\Bkhim\Geolocation\Events\HighRiskIpDetected::class, function ($event) {
    Mail::to($event->user)->send(new SuspiciousLoginAlert($event->loginHistory));
});
```

#### HasGeolocationPreferences Trait

Personalization trait for timezone and currency detection:

```php
// Get user's detected timezone based on last login
$timezone = $user->getDetectedTimezone(); // "America/New_York"

// Get user's local currency
$currency = $user->getLocalCurrency(); // "USD"

// Apply timezone to user session
date_default_timezone_set($user->getDetectedTimezone());

// Format prices in user's currency
$formatter = new NumberFormatter($user->getLocalCurrency(), NumberFormatter::CURRENCY);
echo $formatter->formatCurrency(99.99, $user->getLocalCurrency());
```

#### Configuration

All traits are configurable via `config/geolocation.php`:

```php
'user_trait' => [
    'login_history_table' => 'user_login_locations',
    'cache_logins' => true,
    'login_cache_ttl' => 3600,
],

'security' => [
    'enable_mfa_trigger' => true,
    'risk_threshold' => 'high', // low, high, critical
    'high_risk_threshold' => 70,
    'trusted_countries' => [],
    'trusted_ips' => [],
    'rules' => [
        'proxy' => 40,
        'tor' => 80,
        'crawler' => 20,
        'new_country' => 30,
        'new_city' => 15,
    ],
],

'personalization' => [
    'enable_timezone_detection' => true,
    'enable_currency_detection' => true,
    'default_timezone' => 'UTC',
    'default_currency' => 'USD',
],
```

The package registers the following middleware aliases:

- `geo.allow` - Allow access only from specific countries/continents
- `geo.deny` - Deny access from specific countries/continents  
- `geo.ratelimit` - Rate limit requests based on country

#### Using Middleware in Routes

```php
// Allow only US and Canada
Route::get('/us-only', function () {
    return 'US and Canada only';
})->middleware('geo.allow:US,CA');

// Deny specific countries
Route::get('/no-china', function () {
    return 'Not available in China';
})->middleware('geo.deny:CN');

// Rate limit by country
Route::get('/api/data', function () {
    return ['data' => 'sensitive'];
})->middleware('geo.ratelimit:100,1'); // 100 requests per minute
```

#### Laravel 11+ Middleware Registration

For Laravel 11+, you may need to manually register middleware aliases in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Middleware;

// Inside the bootstrap/app.php file
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'geo.allow' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
        'geo.deny' => \Bkhim\Geolocation\Addons\Middleware\GeoMiddleware::class,
        'geo.ratelimit' => \Bkhim\Geolocation\Addons\Middleware\RateLimitByGeo::class,
    ]);
})
```

**Note for Laravel 11+**: When manually registering middleware aliases in `bootstrap/app.php`, set `GEOLOCATION_MIDDLEWARE_ENABLED=false` in your `.env` file to prevent duplicate registration from the package's service provider.

## Troubleshooting

### Provider-Specific Issues

#### IPStack
- **HTTPS Support**: All tiers (free and paid) support HTTPS connections
- **Rate Limit**: Free tier limited to 100 requests/month

#### IPGeolocation
- **Rate Limit**: Free tier limited to 1,000 requests/month (API credits). Paid plans start at 150K credits/month.
- **Feature Tiers**: Hostname, security detection, user agent, company data, abuse contact, and multi-language responses require paid plans.
- **API Credits**: Each API call consumes credits based on data returned. Monitor usage via IPGeolocation dashboard.
- **Configuration**: Ensure `IPGEOLOCATION_INCLUDE_HOSTNAME`, `IPGEOLOCATION_INCLUDE_SECURITY`, `IPGEOLOCATION_INCLUDE_USERAGENT` are set correctly for your plan.

#### IpInfo
- **Data Limitations**: Free (Lite) tier provides only country and continent data. Upgrade to Core/Plus for city-level geolocation, coordinates, timezone, and postal code.
- **API Token**: Ensure your token is valid and active
- **Plan Features**: Different plans offer different data fields. Check your plan capabilities at [ipinfo.io/pricing](https://ipinfo.io/pricing)

#### ipapi.co
- **No Configuration**: This provider requires no API key or setup
- **HTTPS Support**: All requests use secure HTTPS connections
- **Rate Limits**: Free tier includes 30,000 requests per month
- **IPv4/IPv6 Support**: Both IP address formats are supported

### MaxMind Database Issues

```bash
# Check file permissions
chmod 644 /path/to/GeoLite2-City.mmdb

# Use absolute path in .env
MAXMIND_DATABASE_PATH="/absolute/path/to/GeoLite2-City.mmdb"
```

### Common Errors

- **Invalid IP address**: Ensure IP validation passes `filter_var($ip, FILTER_VALIDATE_IP)`
- **API rate limits**: Implement caching or use MaxMind for heavy usage
- **Database not found**: Verify MaxMind database path and permissions
- **API key errors**: Double-check your environment variables and API key validity

## Testing

Run the test suite with:

```bash
composer test
```

The package includes comprehensive Pest tests covering all providers and features.

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License & Support

This package is open-source software licensed under the MIT License.

- **GitHub Issues**: [https://github.com/bkhim/laravel-geolocation/issues](https://github.com/bkhim/laravel-geolocation/issues)
- **Documentation**: [https://briankimathi.com/packages/laravel-geolocation](https://briankimathi.com/packages/laravel-geolocation)
- **Packagist**: [https://packagist.org/packages/bkhim/laravel-geolocation](https://packagist.org/packages/bkhim/laravel-geolocation)

---

**Note**: This package is actively maintained. For bug reports, feature requests, or contributions, please use the GitHub issue tracker.
