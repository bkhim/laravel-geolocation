# Laravel IP Geolocation Package - Multi-Provider Location Detection

A modern, comprehensive IP geolocation and location detection package for Laravel applications with support for 5+ providers. Get accurate visitor location data including city, country, timezone, currency, ISP information, and security detection. Perfect for user personalization, analytics, fraud prevention, and geo-targeting in Laravel 10+ projects.

## Key Features & Benefits

- **5 Geolocation Providers**: IpInfo.io, MaxMind GeoIP2, IPStack.com, IPGeolocation.io, and ipapi.co for reliable IP address location lookup
- **Laravel 10+ Compatible**: Full support for Laravel 10.x through 12.x with modern PHP 8.1+ features
- **Free & Premium Options**: From completely free IP geolocation to enterprise-grade location services with advanced features
- **Rich Location Data**: City, region, country, GPS coordinates, timezone, postal codes, currency, and ISP/organization information
- **Advanced Caching System**: High-performance caching with configurable TTL and provider-specific cache keys for optimal speed
- **Production-Ready Error Handling**: Comprehensive exception management and IP validation for IPv4 and IPv6 addresses
- **Multi-language Support**: Country name translations and provider-specific language options for international applications
- **Security Detection**: Proxy, VPN, Tor, crawler, and mobile device detection for fraud prevention and analytics
- **Developer-Friendly**: Flexible configuration, Artisan commands, and extensive documentation for rapid integration

## Installation & Setup

Install the Laravel IP geolocation package using Composer:

```bash
composer require bkhim/laravel-geolocation
```

### Laravel Auto-Discovery
The package automatically registers via Laravel's package discovery. For manual registration in older Laravel versions, add to `config/app.php`:

```php
'providers' => [
    Bkhim\Geolocation\GeolocationServiceProvider::class,
],
```

### Configuration Setup
Publish the configuration file to customize your geolocation settings:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

## Supported Laravel Versions

- Laravel 10.x to 12.x
- PHP 8.1+

## Quick Start Guide - Choose Your IP Geolocation Provider

Perfect for user personalization, analytics, fraud detection, and geo-targeting applications:

### Geolocation Provider Options

#### 1. ipapi.co - Free IP Geolocation Service (30K requests/month)
No API key required - ideal for development and small applications:
```env
GEOLOCATION_DRIVER=ipapi
```

#### 2. IpInfo.io - Popular IP Location Service (50K requests/month free)
Reliable geolocation API with generous free tier:
```env
GEOLOCATION_DRIVER=ipinfo
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here
```

#### 3. IPStack - Professional Geolocation API (10K requests/month free)
Feature-rich IP location service with currency and timezone data:
```env
GEOLOCATION_DRIVER=ipstack
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here
```

#### 4. IPGeolocation.io - Advanced Location Detection (1K requests/month free)
Comprehensive geolocation with security detection and 12 language support:
```env
GEOLOCATION_DRIVER=ipgeolocation
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
```

#### 5. MaxMind GeoIP2 - Local Database Solution
Privacy-focused, fastest performance with offline IP location database:
```env
GEOLOCATION_DRIVER=maxmind
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
```

## Provider Comparison

| Provider | Free Tier | API Key Required | HTTPS | Special Features |
|----------|-----------|------------------|-------|------------------|
| **ipapi.co** | ✅ 30K/month | ❌ No | ✅ Yes | No API key needed, IPv4/IPv6 |
| **IpInfo** | ✅ 50K/month | ✅ Yes | ✅ Yes | Popular, reliable, generous free tier |
| **IPStack** | ✅ 10K/month | ✅ Yes | 💰 Paid only | Multiple tiers, comprehensive data |
| **IPGeolocation** | ✅ 1K/month | ✅ Yes | ✅ Yes | 12 languages, security features |
| **MaxMind** | ✅ Local DB | ❌ No | N/A | Privacy-focused, fastest, offline |

*Note: Rate limits and terms may change. Check provider websites for current limits.

### Recommendation by Use Case

- **Getting Started**: Use **ipapi.co** (no setup required)
- **Production Apps**: Use **IpInfo** (reliable with generous limits)
- **High Volume**: Use **MaxMind** (local database, no API limits)  
- **Advanced Features**: Use **IPGeolocation** (security data, translations)
- **Enterprise**: Use **IPStack** (comprehensive data, support)

### Practical Examples

#### Display User Location with Flag
```php
$details = Geolocation::lookup();

echo $details->getCountryFlag() . ' ' . $details->getFormattedAddress();
// Output: 🇺🇸 Mountain View, CA, United States

echo "Welcome visitor from " . $details->getShortAddress() . "!";
// Output: Welcome visitor from Mountain View, US!
```

#### Create Map Links
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

#### Security & Network Analysis
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

#### Display Country Information
```php
$details = Geolocation::lookup();

// Flag image
echo "<img src='" . $details->getCountryFlagUrl(64) . "' alt='" . $details->getCountry() . "' />";

// Currency information
echo "Currency: " . $details->getCurrencySymbol() . " " . $details->getCurrency();
// Output: Currency: $ US Dollar

// Timezone information  
echo "Local time offset: UTC" . ($details->getTimezoneOffset() >= 0 ? '+' : '') . $details->getTimezoneOffset();
// Output: Local time offset: UTC-8
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=geolocation-config
```

### Environment Variables

```env
# Default driver (ipinfo, maxmind, ipstack, ipgeolocation, ipapi)
GEOLOCATION_DRIVER=ipapi

# IpInfo Configuration
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_ipinfo_token

# MaxMind Configuration
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key

# IPStack Configuration
GEOLOCATION_IPSTACK_ACCESS_KEY=your_ipstack_key

# IPGeolocation Configuration
GEOLOCATION_IPGEOLOCATION_API_KEY=your_ipgeolocation_key
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
IPGEOLOCATION_INCLUDE_USERAGENT=false

# ipapi.co Configuration
# No configuration needed - completely free!

# Cache Settings
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400

# Request Settings
GEOLOCATION_TIMEOUT=5
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100
```

## Usage

### Basic IP Location Lookup Usage

Get comprehensive visitor location data for user personalization and analytics:

```php
use Bkhim\Geolocation\Geolocation;

// Detect visitor location automatically
$details = Geolocation::lookup();

// Lookup specific IP address location
$details = Geolocation::lookup('8.8.8.8');

// Access comprehensive location data
echo $details->getIp();             // 8.8.8.8
echo $details->getCity();           // Mountain View
echo $details->getRegion();         // California
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getLatitude();       // 37.386
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
echo $details->getTimezoneOffset(); // -8
echo $details->getCurrency();       // US Dollar
echo $details->getCurrencyCode();   // USD
echo $details->getCurrencySymbol(); // $
echo $details->getContinent();      // North America
echo $details->getContinentCode();  // NA
echo $details->getPostalCode();     // 94043
echo $details->getOrg();            // Google LLC
echo $details->getIsp();            // Google LLC
echo $details->getAsn();            // AS15169
echo $details->getAsnName();        // Google LLC
echo $details->getConnectionType(); // corporate
echo $details->getHostname();       // dns.google
echo $details->isMobile();          // false
echo $details->isProxy();           // false
echo $details->isCrawler();         // false
echo $details->isTor();             // false

// Utility methods for user display
echo $details->getFormattedAddress();   // Mountain View, CA, United States
echo $details->getShortAddress();       // Mountain View, US
echo $details->getFullAddress();        // Mountain View, CA 94043, US
echo $details->getGoogleMapsLink();     // https://maps.google.com/?q=37.386,-122.0838
echo $details->getCountryFlag();        // 🇺🇸
echo $details->getCountryFlagUrl();     // https://flagcdn.com/w320/us.png

// Get comprehensive location data array
$data = $details->toArray();
/*
[
    'city' => 'Mountain View',
    'region' => 'California',
    'country' => 'United States', 
    'countryCode' => 'US',
    'latitude' => 37.386,
    'longitude' => -122.0838,
    'timezone' => 'America/Los_Angeles',
    'timezoneOffset' => -8,
    'currency' => 'US Dollar',
    'currencyCode' => 'USD',
    'currencySymbol' => '$',
    'continent' => 'North America',
    'continentCode' => 'NA',
    'postalCode' => '94043',
    'org' => 'Google LLC',
    'isp' => 'Google LLC',
    'asn' => 'AS15169',
    'asnName' => 'Google LLC',
    'connectionType' => 'corporate',
    'isMobile' => false,
    'isProxy' => false,
    'isCrawler' => false,
    'isTor' => false,
    'hostname' => 'dns.google'
]
*/
```

### Specific Driver Usage

```php
// Use specific drivers
$ipapiDetails = Geolocation::driver('ipapi')->lookup('8.8.8.8');          // Free
$ipinfoDetails = Geolocation::driver('ipinfo')->lookup('8.8.8.8');        // Popular
$ipstackDetails = Geolocation::driver('ipstack')->lookup('8.8.8.8');      // Feature-rich
$ipgeoDetails = Geolocation::driver('ipgeolocation')->lookup('8.8.8.8');  // Advanced
$maxmindDetails = Geolocation::driver('maxmind')->lookup('8.8.8.8');      // Local database

// Switch default driver temporarily
config(['geolocation.drivers.default' => 'ipapi']);
$details = Geolocation::lookup('8.8.8.8');
```

## Use Cases & Applications

This Laravel IP geolocation package is perfect for:

### **User Personalization & Localization**
- **Currency Detection**: Automatically display prices in visitor's local currency
- **Language Localization**: Show content in user's regional language
- **Timezone Handling**: Schedule events and display times in user's timezone
- **Regional Content**: Customize content based on visitor's country/region

### **Security & Fraud Prevention**
- **Proxy Detection**: Identify VPN, proxy, and anonymous connections
- **Tor Detection**: Flag Tor exit nodes for enhanced security
- **Geoblocking**: Restrict access based on country or region
- **Risk Assessment**: Analyze connection patterns for fraud detection

### **Analytics & Business Intelligence**
- **Visitor Analytics**: Track user demographics and geographic distribution
- **Market Analysis**: Understand your audience's geographic spread
- **Performance Monitoring**: Analyze traffic patterns by location
- **A/B Testing**: Run location-based experiments and campaigns

### **E-commerce & Marketing**
- **Geo-targeting**: Show location-specific promotions and offers
- **Shipping Optimization**: Pre-fill shipping addresses and calculate costs
- **Tax Calculation**: Apply correct tax rates based on visitor location
- **Compliance**: Meet regional regulations (GDPR, data residency)

### **Mobile & Device Detection**
- **Mobile Optimization**: Detect mobile connections for responsive design
- **Bot Detection**: Identify crawlers and automated traffic
- **Device Targeting**: Customize experience based on connection type

## Available Geolocation Data Methods

The `GeolocationDetails` object provides the following methods:

```php
$details = Geolocation::lookup('8.8.8.8');

// Basic location information
$details->getIp();          // string|null - IP address
$details->getCity();        // string|null - City name
$details->getRegion();      // string|null - State/Province name
$details->getCountry();     // string|null - Country name (translated if available)
$details->getCountryCode(); // string|null - ISO country code (e.g., 'US', 'GB')

// Coordinates
$details->getLatitude();    // float|null - Latitude coordinate
$details->getLongitude();   // float|null - Longitude coordinate

// Time & Timezone
$details->getTimezone();        // string|null - Timezone identifier (e.g., 'America/New_York')
$details->getTimezoneOffset();  // int|null - Hours offset from UTC (e.g., -5, +2)

// Currency information
$details->getCurrency();       // string|null - Currency name (e.g., 'US Dollar')
$details->getCurrencyCode();   // string|null - Currency code (e.g., 'USD')
$details->getCurrencySymbol(); // string|null - Currency symbol (e.g., '$')

// Geographic regions
$details->getContinent();      // string|null - Continent name (e.g., 'North America')
$details->getContinentCode();  // string|null - Continent code (e.g., 'NA')

// Additional data
$details->getPostalCode();  // string|null - Postal/ZIP code
$details->getOrg();         // string|null - Organization/ISP name

// Network & ISP information
$details->getIsp();           // string|null - Internet Service Provider name
$details->getAsn();           // string|null - Autonomous System Number (e.g., 'AS15169')
$details->getAsnName();       // string|null - ASN organization name
$details->getConnectionType(); // string|null - Connection type (e.g., 'corporate', 'residential')
$details->getHostname();     // string|null - Hostname/reverse DNS

// Security & device detection
$details->isMobile();         // bool|null - Is mobile connection
$details->isProxy();          // bool|null - Is proxy/VPN
$details->isCrawler();        // bool|null - Is web crawler/bot
$details->isTor();            // bool|null - Is Tor exit node

// Utility methods for formatted data
$details->getFormattedAddress();   // string|null - "Mountain View, CA, United States"
$details->getShortAddress();       // string|null - "Mountain View, US"
$details->getFullAddress();        // string|null - "Mountain View, CA 94043, US"

// Map links
$details->getGoogleMapsLink();     // string|null - "https://maps.google.com/?q=37.386,-122.0838"
$details->getOpenStreetMapLink();  // string|null - "https://www.openstreetmap.org/?mlat=..."
$details->getAppleMapsLink();      // string|null - "maps://maps.apple.com/?ll=..."

// Country flags & emojis
$details->getCountryFlag();        // string|null - "🇺🇸"
$details->getCountryFlagEmoji();   // string|null - "🇺🇸"
$details->getCountryFlagUrl();     // string|null - "https://flagcdn.com/w320/us.png"
$details->getCountryFlagUrl(64);   // string|null - "https://flagcdn.com/w64/us.png" (custom width)

// Serialization
$details->toArray();        // array - All data as associative array
json_encode($details);      // string - JSON representation
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

## Advanced Configuration

### Custom Cache Store

```php
// config/geolocation.php
'cache' => [
    'enabled' => true,
    'ttl' => 86400,
    'store' => 'redis', // Use specific cache store
],
```

### Custom HTTP Client Options

```php
// config/geolocation.php
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

### IPGeolocation Advanced Configuration

```php
// config/geolocation.php
'ipgeolocation' => [
    'driver' => 'ipgeolocation',
    'api_key' => env('GEOLOCATION_IPGEOLOCATION_API_KEY'),
    
    // Multi-language support (paid plans only, except 'en')
    'language' => env('IPGEOLOCATION_LANGUAGE', 'en'), // en, de, ru, ja, fr, cn, es, cs, it, ko, fa, pt
    
    // Advanced features (require appropriate paid plan)
    'include_hostname' => env('IPGEOLOCATION_INCLUDE_HOSTNAME', false), // Standard+
    'include_security' => env('IPGEOLOCATION_INCLUDE_SECURITY', false), // Security+
    'include_useragent' => env('IPGEOLOCATION_INCLUDE_USERAGENT', false), // Paid plans
],
```

## Translation Support

The package includes translations for country names. Publish translation files:

```bash
php artisan vendor:publish --tag="geolocation-translations"
```

### Using Translations

```php
app()->setLocale('pt');
$details = Geolocation::lookup('8.8.8.8');
echo $details->getCountry(); // "Estados Unidos" instead of "United States"
```

## Troubleshooting

### Provider-Specific Issues

#### IPStack
- **HTTPS Error**: Free tier only supports HTTP. Upgrade to paid plan for HTTPS
- **Rate Limit**: Free tier limited to 10,000 requests/month

#### IPGeolocation  
- **Language Support**: Multi-language responses require paid plans
- **Security Features**: Advanced features (hostname, security) require appropriate plan tiers

#### IpInfo
- **Rate Limit**: Free tier limited to 50,000 requests/month
- **API Token**: Ensure your token is valid and active

#### ipapi.co
- **No Configuration**: This provider requires no setup - if it's not working, check your internet connection
- **Rate Limits**: May apply for extremely high volume usage

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

```bash
# Run tests
composer test

```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Changelog

- See [CHANGELOG.md](CHANGELOG.md) for details

## License

This package is open-source software licensed under the MIT License.

## Support

- [GitHub Issues](https://github.com/bkhim/laravel-geolocation/issues)
- [Documentation](https://briankimathi.com/packages/laravel-geolocation)
- [Packagist](https://packagist.org/packages/bkhim/laravel-geolocation)

---

**Note**: This package is actively maintained. For bug reports, feature requests, or contributions, please use the GitHub issue tracker.
