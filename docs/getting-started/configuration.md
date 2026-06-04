# Configuration

After publishing the configuration, set your environment variables in `.env`.

## Environment Variables

### Default Driver

```env
GEOLOCATION_DRIVER=ipapi
```

Available drivers: `ipapi`, `ipinfo`, `ipstack`, `ipgeolocation`, `maxmind`, `ip2location`

### Request & Cache Configuration

```env
GEOLOCATION_TIMEOUT=5
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100
```

### Provider API Keys

```env
# IpInfo - Get token from https://ipinfo.io/account/token
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here

# IPStack - Get API key from https://ipstack.com/dashboard
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here

# IPGeolocation - Get API key from https://ipgeolocation.io/dashboard
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
IPGEOLOCATION_LANGUAGE=en

# MaxMind - Download database from https://dev.maxmind.com/geoip/
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key
```

### Addon Configuration

```env
GEOLOCATION_MIDDLEWARE_ENABLED=false
GEOLOCATION_RATE_LIMITING_ENABLED=false
GEOLOCATION_ANONYMIZATION_ENABLED=false
GEOLOCATION_GDPR_ENABLED=false
```

## Configuration File

The published `config/geolocation.php` contains all configuration options:

```php
return [
    'driver' => env('GEOLOCATION_DRIVER', 'ipapi'),
    
    'timeout' => env('GEOLOCATION_TIMEOUT', 5),
    
    'cache' => [
        'enabled' => env('GEOLOCATION_CACHE_ENABLED', true),
        'ttl' => env('GEOLOCATION_CACHE_TTL', 86400),
        'store' => null, // Uses default cache store
    ],
    
    'fallback' => [
        'enabled' => false,
        'order' => ['ipinfo', 'maxmind'],
        'max_attempts' => 2,
    ],
    
    'logging' => [
        'enabled' => false,
        'level_success' => 'info',
        'level_error' => 'error',
    ],
    
    // Provider configurations...
    
    'addons' => [
        'middleware' => [
            'enabled' => env('GEOLOCATION_MIDDLEWARE_ENABLED', false),
            'response_type' => 'abort',
            'status_code' => 403,
            'redirect_to' => '/',
        ],
        
        'rate_limiting' => [
            'enabled' => env('GEOLOCATION_RATE_LIMITING_ENABLED', false),
            'limits' => [],
            'message' => 'Too Many Attempts.',
        ],
        
        'anonymization' => [
            'enabled' => env('GEOLOCATION_ANONYMIZATION_ENABLED', false),
        ],
        
        'gdpr' => [
            'enabled' => env('GEOLOCATION_GDPR_ENABLED', false),
            'require_consent_for' => ['GDPR'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ],
];
```

## Provider Selection

See [Provider Comparison](../providers/index.md) to choose the right provider for your needs.
