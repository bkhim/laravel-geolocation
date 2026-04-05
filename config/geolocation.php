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
        'default' => env('GEOLOCATION_DRIVER', 'ipapi'),
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
        | IpInfo offers multiple plans: Lite (free, unlimited requests, country/continent only),
        | Core ($49/mo, full geolocation data), Plus ($74/mo, privacy detection).
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
        | Free tier includes 100 requests per month with HTTPS support.
        | Paid plans offer higher limits and comprehensive data (Basic: $12.99/mo, Professional: $59.99/mo, Professional Plus: $99.99/mo).
        |
        | Required: access_key (get from https://ipstack.com/dashboard)
        |
        */
        'ipstack' => [
            'driver' => 'ipstack',

            // Your IPStack API access key
            'access_key' => env('GEOLOCATION_IPSTACK_ACCESS_KEY', null),


        ],

        /*
        |----------------------------------------------------------------------
        | IPGeolocation Provider Configuration
        |----------------------------------------------------------------------
        |
        | IPGeolocation provides comprehensive IP geolocation data including
        | timezone, ISP, security, company, and abuse contact information through a RESTful API.
        | You can get a free API key from https://ipgeolocation.io/
        |
        | API Plans:
        | - Free: 1,000 requests/month (API credits) - location essentials, country metadata, ASN basics, currency, time zone
        | - Starter ($19/mo): 150K credits - hostname, connection type, routing, basic security
        | - Core ($29/mo): 250K credits - company data, ASN details, multi-language support
        | - Plus ($49/mo): 500K credits - security detection, abuse contact, user agent data
        | - Pro ($79/mo): 1M credits - bulk lookup, higher accuracy, priority support
        | - Business ($129/mo): 2M credits - extra API keys, dedicated APIs (ASN, Abuse, Security)
        | - Premium ($249/mo): 5M credits - enterprise-grade features, custom integrations
        | - Enterprise: Custom pricing and volume
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
        | ipapi.co provides a comprehensive IP geolocation API with no API key
        | required for the free tier. It supports both IPv4 and IPv6 addresses
        | and provides extensive location data including city, region, country,
        | postal code, latitude/longitude, timezone, currency, languages, ASN,
        | and organization information.
        |
        | Features:
        | - No API key required for free tier (30,000 requests/month)
        | - Multiple paid plans available ($12-$159+/month)
        | - IPv4 & IPv6 support
        | - HTTPS/SSL encryption for all requests
        | - Comprehensive location data even on free tier
        | - ASN and organization information
        | - Transparent pricing with annual discounts
        |
        */
        'ipapi' => [
            'driver' => 'ipapi',

            // No configuration needed - free tier provides 30K requests/month
            // Paid plans available for higher volumes and production use
        ],


        /*
        |----------------------------------------------------------------------
        | ip2location.io Provider Configuration
        |----------------------------------------------------------------------
        |
        | ip2location.io provides a comprehensive IP geolocation API including
        | location & geography, network & connectivity, proxy & security,
        | currency & language, and so on. It supportsboth IPv4 and IPv6
        | addresses lookup. It can be used without an API key, up to 1,000
        | queries daily, or sign up for a free API key to get up to 50,000
        | queries monthly.
        |
        */
        'ip2locationio' => [
            'driver' => 'ip2locationio',

            // Your IP2Location.io API key
            // If not defined, will query without key
            'api_key' => env('GEOLOCATION_IP2LOCATIONIO_API_KEY', null),

            // Response language for continent, country, region and city name
            // NOTE: Translation is only available with PAID PLANS
            // Free tier only supports 'en' (English) - other languages will cause API errors
            // Set to null or 'en' for free tier, or upgrade to paid plan for translations
            // Supported languages: ar, cs, da, de, en, es, et, fi, fr, ga, it, ja, ko, ms, nl, pt, ru, sv, tr, vi, zh-cn, zh-tw
            'language' => env('GEOLOCATION_IP2LOCATIONIO_LANGUAGE', 'en'),
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
    | Cache Best Practices:
    | - Use Redis or Memcached for production environments
    | - Consider using cache tags for easier cache invalidation
    | - Set appropriate TTL based on your data freshness requirements
    | - Monitor cache hit rates and adjust TTL accordingly
    |
    | Note: The cache key includes the IP address and provider name to
    |       avoid conflicts between different providers.
    |
    */
    'cache' => [
        // Enable or disable caching of geolocation results
        'enabled' => env('GEOLOCATION_CACHE_ENABLED', true),

        // Time-to-live for cached results (in seconds)
        // Recommended: 3600 (1 hour) to 86400 (24 hours) for most use cases
        // IP geolocation data doesn't change frequently
        'ttl' => env('GEOLOCATION_CACHE_TTL', 86400),

        // Optional: Specify a custom cache store (redis, memcached, database, etc.)
        // Null uses the default cache store from config/cache.php
        // For production, consider using Redis or Memcached for better performance
        'store' => env('GEOLOCATION_CACHE_STORE', null),

        // Optional: Cache key prefix to avoid conflicts with other cached data
        // This will be prepended to all cache keys (e.g., 'myapp:geolocation:ipinfo:...')
        'prefix' => env('GEOLOCATION_CACHE_PREFIX', 'geolocation'),

        // Optional: Enable cache tags for easier bulk cache invalidation
        // Note: Only supported by Redis and Memcached cache drivers
        // When enabled, you can flush all geolocation cache with: Cache::tags(['geolocation'])->flush()
        'tags' => [
            'enabled' => env('GEOLOCATION_CACHE_TAGS_ENABLED', false),
            'names' => ['geolocation', 'ip-lookup'],
        ],
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

    /*
    |--------------------------------------------------------------------------
    | User Trait Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the geolocation traits that can be applied to the User model.
    |
    */
    'user_trait' => [
        'enabled' => env('GEOLOCATION_USER_TRAIT_ENABLED', true),
        'login_history_table' => 'user_login_locations',
        'login_history_model' => \Bkhim\Geolocation\Models\LoginHistory::class,
        'cache_ttl' => env('GEOLOCATION_USER_TRAIT_CACHE_TTL', 86400), // seconds
        'anonymization_mode' => env('GEOLOCATION_ANONYMIZATION_MODE', 'partial'), // none|partial|full
        'store_ip' => env('GEOLOCATION_STORE_IP', true), // true = masked, false = don't store
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for security-related geolocation features.
    |
    */
    'security' => [
        'enable_mfa_trigger' => env('GEOLOCATION_SECURITY_MFA_ENABLED', true),
        'risk_threshold' => env('GEOLOCATION_SECURITY_RISK_THRESHOLD', 'high'),

        // Risk scoring rules for isHighRiskLogin method
        'high_risk_threshold' => env('GEOLOCATION_SECURITY_HIGH_RISK_THRESHOLD', 70),
        'rules' => [
            'proxy' => env('GEOLOCATION_SECURITY_RULE_PROXY', 40),
            'tor' => env('GEOLOCATION_SECURITY_RULE_TOR', 80),
            'crawler' => env('GEOLOCATION_SECURITY_RULE_CRAWLER', 20),
            'new_country' => env('GEOLOCATION_SECURITY_RULE_NEW_COUNTRY', 30),
            'new_city' => env('GEOLOCATION_SECURITY_RULE_NEW_CITY', 15),
        ],

        // Trusted locations that bypass security checks
        'trusted_countries' => [],
        'trusted_ips' => [],

        // Custom risk rule classes (for extensibility)
        'custom_rules' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Personalization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for personalization-related geolocation features.
    |
    */
    'personalization' => [
        'enable_currency' => env('GEOLOCATION_PERSONALIZATION_CURRENCY_ENABLED', true),
        'enable_timezone' => env('GEOLOCATION_PERSONALIZATION_TIMEZONE_ENABLED', true),
    ],
];
