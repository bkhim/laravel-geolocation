<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Request Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | This setting controls the maximum time (in seconds) that the package will
    | wait for a response from geolocation APIs before timing out.
    |
    | Increase this value if you experience timeouts with certain providers.
    |
    */
    'timeout' => env('GEOLOCATION_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Retry Mechanism Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the retry behavior for failed API requests. This helps handle
    | temporary network issues or API rate limits.
    |
    | - attempts: Maximum number of retry attempts (0 to disable retries)
    | - delay: Delay between retries in milliseconds
    |
    */
    'retry' => [
        'attempts' => env('GEOLOCATION_RETRY_ATTEMPTS', 2),
        'delay' => env('GEOLOCATION_RETRY_DELAY', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Geolocation Driver
    |--------------------------------------------------------------------------
    |
    | This option specifies the default geolocation driver that will be used
    | when no specific driver is requested. You can change this to any of
    | the supported drivers listed in the 'providers' section below.
    |
    | Supported drivers: 'ipinfo', 'maxmind', 'ipstack', 'ipgeolocation', 'ipapi'
    |
    */
    'drivers' => [
        'default' => env('GEOLOCATION_DRIVER', 'ipinfo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geolocation Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each geolocation provider.
    | You can use multiple providers and switch between them dynamically.
    |
    | Each provider must specify a 'driver' that matches one of the available
    | driver implementations in the package.
    |
    */

    'providers' => [

        /*
        |----------------------------------------------------------------------
        | IpInfo Provider Configuration
        |----------------------------------------------------------------------
        |
        | IpInfo is a free IP geolocation API with generous rate limits.
        | You can get an API token from https://ipinfo.io/
        |
        | Required: access_token (get from https://ipinfo.io/account/token)
        |
        */
        'ipinfo' => [
            'driver' => 'ipinfo',

            // Your IpInfo API access token
            'access_token' => env('GEOLOCATION_IPINFO_ACCESS_TOKEN', null),

            // Additional Guzzle HTTP client options
            // See: https://docs.guzzlephp.org/en/stable/request-options.html
            'client_options' => [
                'connect_timeout' => 5,
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Laravel-Geolocation-Package/1.0',
                ],
            ],
            'include_timezone' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | MaxMind Provider Configuration
        |----------------------------------------------------------------------
        |
        | MaxMind provides local database files for IP geolocation.
        | You need to download the database file from MaxMind and update
        | the path below.
        |
        | Download: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
        |
        */
        'maxmind' => [
            'driver' => 'maxmind',

            // Use absolute path for better reliability
            'database_path' => env(
                'MAXMIND_DATABASE_PATH',
                storage_path('app/geoip/GeoLite2-City.mmdb')
            ),

            'license_key' => env('MAXMIND_LICENSE_KEY'),
            'include_timezone' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | IPStack Provider Configuration
        |----------------------------------------------------------------------
        |
        | IPStack provides IP geolocation data through a RESTful API.
        | You can get a free API key from https://ipstack.com/
        | Free tier includes 10,000 requests per month.
        |
        | Required: access_key (get from https://ipstack.com/dashboard)
        |
        */
        'ipstack' => [
            'driver' => 'ipstack',

            // Your IPStack API access key
            'access_key' => env('GEOLOCATION_IPSTACK_ACCESS_KEY', null),

            // Additional options
            'secure' => env('IPSTACK_SECURE', true),
        ],

        /*
        |----------------------------------------------------------------------
        | IPGeolocation Provider Configuration
        |----------------------------------------------------------------------
        |
        | IPGeolocation provides comprehensive IP geolocation data including
        | timezone, ISP, and security information through a RESTful API.
        | You can get a free API key from https://ipgeolocation.io/
        |
        | API Plans:
        | - Free: City-level geolocation, country details, currency (1,000 req/month)
        | - Standard: Everything in Free + hostname, ASN, ISP info (50,000 req/month)
        | - Security: Everything in Standard + security checks, threat score
        | - Advance: Everything in Security + accuracy radius, deeper network data
        |
        | Required: api_key (get from https://ipgeolocation.io/dashboard)
        |
        */
        'ipgeolocation' => [
            'driver' => 'ipgeolocation',

            // Your IPGeolocation API key
            'api_key' => env('GEOLOCATION_IPGEOLOCATION_API_KEY', null),

            // Response language (paid plans only, except 'en')
            // Supported: en, de, ru, ja, fr, cn, es, cs, it, ko, fa, pt
            'language' => env('IPGEOLOCATION_LANGUAGE', 'en'),

            // Additional fields (requires appropriate paid plan)
            'include_hostname' => env('IPGEOLOCATION_INCLUDE_HOSTNAME', false), // Standard+
            'include_security' => env('IPGEOLOCATION_INCLUDE_SECURITY', false), // Security+
            'include_useragent' => env('IPGEOLOCATION_INCLUDE_USERAGENT', false), // Paid plans
        ],

        /*
        |----------------------------------------------------------------------
        | ipapi.co Provider Configuration
        |----------------------------------------------------------------------
        |
        | ipapi.co provides a simple, free IP geolocation API with no API key
        | required. It supports both IPv4 and IPv6 addresses and provides
        | comprehensive location data including timezone and ISP information.
        |
        | Features:
        | - No API key required (30,000 requests/month free)
        | - IPv4 & IPv6 support
        | - Multiple output formats (JSON, XML, CSV, YAML)
        | - Comprehensive location data
        | - ASN and organization information
        | - HTTPS support
        |
        */
        'ipapi' => [
            'driver' => 'ipapi',

            // No configuration needed - free tier provides 30K requests/month
            // without requiring an API key
        ],

        /*
        |----------------------------------------------------------------------
        | Future Provider Example
        |----------------------------------------------------------------------
        |
        | You can add additional providers in the future by following the same
        | pattern and implementing the corresponding driver class.
        |
        | 'newprovider' => [
        |     'driver' => 'newprovider',
        |     'api_key' => env('NEW_PROVIDER_API_KEY'),
        |     'options' => [...],
        | ],
        |
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Geolocation results can be cached to improve performance and reduce
    | API rate limit usage. This is especially useful for frequently
    | requested IP addresses.
    |
    | Note: The cache key includes the IP address and provider name to
    |       avoid conflicts between different providers.
    |
    */
    'cache' => [
        // Enable or disable caching of geolocation results
        'enabled' => env('GEOLOCATION_CACHE_ENABLED', true),

        // Time-to-live for cached results (in seconds)
        // Default: 86400 seconds (24 hours)
        'ttl' => env('GEOLOCATION_CACHE_TTL', 86400),

        // Optional: Specify a custom cache store
        'store' => env('GEOLOCATION_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for geolocation requests. This can help
    | with debugging and monitoring API usage.
    |
    */
    'logging' => [
        // Enable logging of geolocation requests
        'enabled' => env('GEOLOCATION_LOGGING_ENABLED', false),

        // Log level for successful requests
        'level_success' => env('GEOLOCATION_LOG_LEVEL_SUCCESS', 'info'),

        // Log level for failed requests
        'level_error' => env('GEOLOCATION_LOG_LEVEL_ERROR', 'error'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | Configure fallback behavior when the primary provider fails.
    | If enabled, the package will automatically try the next available
    | provider when the current one fails.
    |
    */
    'fallback' => [
        'enabled' => env('GEOLOCATION_FALLBACK_ENABLED', false),

        // Order of providers to try if primary fails
        'order' => ['ipinfo', 'maxmind'],

        // Maximum number of fallback attempts
        'max_attempts' => env('GEOLOCATION_FALLBACK_ATTEMPTS', 2),
    ],

    'addons' => [
        'middleware' => [
            'enabled' => false,
            'cache_time' => 3600, // 1 hour
            'response_type' => 'abort', // abort, redirect, json
            'redirect_to' => '/restricted',
            'status_code' => 403,
        ],

        'rate_limiting' => [
            'enabled' => false,
            'limits' => [
                // 'country_code' => ['requests_per_minute' => X]
                'US' => ['requests_per_minute' => 100],
                'CN' => ['requests_per_minute' => 50],
                '*' => ['requests_per_minute' => 30], // Default
            ],
            'storage' => 'redis', // redis, database, file
        ],

        'anonymization' => [
            'enabled' => false,
            'ipv4_mask' => '255.255.255.0', // Last octet
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => true,
            'gdpr_countries' => ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB','IS','LI','NO'],
        ],

        'gdpr' => [
            'enabled' => false,
            'require_consent_for' => ['EU', 'EEA', 'GDPR'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365, // days
            'banner_view' => 'geolocation::gdpr.banner',
        ],
    ],

];
