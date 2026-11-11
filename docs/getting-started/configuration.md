# Configuration

After publishing the config file, most settings can be driven from `.env`.

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --tag=geolocation-config
```

## Core

```env
GEOLOCATION_DRIVER=ipapi
GEOLOCATION_TIMEOUT=5
GEOLOCATION_RETRY_ATTEMPTS=2
GEOLOCATION_RETRY_DELAY=100
```

Available drivers: `ipapi`, `ip2locationio`, `ipinfo`, `ipstack`, `ipgeolocation`, `maxmind`.

The config structure is:

```php
'drivers' => [
    'default' => env('GEOLOCATION_DRIVER', 'ipapi'),
],
```

## Cache

```env
GEOLOCATION_CACHE_ENABLED=true
GEOLOCATION_CACHE_TTL=86400
GEOLOCATION_CACHE_SLIDING=true          # Sliding TTL (Laravel 13+; fixed TTL on 12)
GEOLOCATION_CACHE_STORE=                # redis, memcached, database, file (empty = default)
GEOLOCATION_CACHE_PREFIX=geolocation
GEOLOCATION_CACHE_TAGS_ENABLED=false    # Requires redis/memcached
```

## Providers

```env
# IpInfo — https://ipinfo.io/account/token
GEOLOCATION_IPINFO_ACCESS_TOKEN=

# IPStack — https://ipstack.com/dashboard
GEOLOCATION_IPSTACK_ACCESS_KEY=

# IPGeolocation — https://ipgeolocation.io/dashboard
GEOLOCATION_IPGEOLOCATION_API_KEY=
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
IPGEOLOCATION_INCLUDE_USERAGENT=false

# IP2Location.io — https://www.ip2location.io/
GEOLOCATION_IP2LOCATIONIO_API_KEY=
GEOLOCATION_IP2LOCATIONIO_LANGUAGE=en

# MaxMind — local database
MAXMIND_DATABASE_PATH=storage/app/geoip/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=
```

## Logging & fallback

```env
GEOLOCATION_LOGGING_ENABLED=false
GEOLOCATION_LOG_LEVEL_SUCCESS=info
GEOLOCATION_LOG_LEVEL_ERROR=error
GEOLOCATION_LOG_AUDIT_CHANNEL=stack

GEOLOCATION_FALLBACK_ENABLED=false
GEOLOCATION_FALLBACK_ATTEMPTS=2
```

The fallback order is configured in `config/geolocation.php` (`fallback.order`).

## Addons

All addon toggles are read from the environment. If a flag is `false`, the
feature is completely off.

```env
# Middleware
GEOLOCATION_MIDDLEWARE_ENABLED=false
GEOLOCATION_MIDDLEWARE_RESPONSE_TYPE=abort      # abort | json | redirect
GEOLOCATION_MIDDLEWARE_REDIRECT_TO=/restricted
GEOLOCATION_MIDDLEWARE_STATUS_CODE=403

# Rate limiting
GEOLOCATION_RATE_LIMITING_ENABLED=false

# Anonymization
GEOLOCATION_ANONYMIZATION_ENABLED=false
GEOLOCATION_ANONYMIZATION_IPV4_MASK=255.255.255.0
GEOLOCATION_ANONYMIZATION_IPV6_MASK=ffff:ffff:ffff:ffff:0000:0000:0000:0000
GEOLOCATION_ANONYMIZATION_PRESERVE_LOCAL=true
# Scope: empty or * = mask everything; DE,FR = only those countries
GEOLOCATION_ANONYMIZATION_COUNTRIES=

# GDPR consent
GEOLOCATION_GDPR_ENABLED=false
GEOLOCATION_GDPR_REGIONS=EU,EEA,GDPR
GEOLOCATION_GDPR_CONSENT_COOKIE=geo_consent
GEOLOCATION_GDPR_CONSENT_LIFETIME=365
GEOLOCATION_GDPR_BANNER_VIEW=geolocation::gdpr.banner
GEOLOCATION_GDPR_ROUTES_ENABLED=false
GEOLOCATION_GDPR_ROUTE_GIVE=/consent/accept
GEOLOCATION_GDPR_ROUTE_WITHDRAW=/consent/withdraw
```

Rate limits accept either an integer or an array:

```php
'limits' => [
    'US' => 100,
    'CN' => ['requests_per_minute' => 10],
    '*' => 60,
],
```

## User traits

```env
GEOLOCATION_USER_TRAIT_ENABLED=true
GEOLOCATION_STORE_IP=true
GEOLOCATION_ANONYMIZATION_MODE=partial   # none | partial | full
GEOLOCATION_USER_TRAIT_CACHE_TTL=86400
GEOLOCATION_LOGIN_RETENTION_DAYS=30
# GEOLOCATION_USER_MODEL=App\Models\User
```

The table/model are configurable in `config/geolocation.php` under `user_trait`.

## Security

```env
GEOLOCATION_SECURITY_MFA_ENABLED=true
GEOLOCATION_SECURITY_BLOCKING_ENABLED=true
GEOLOCATION_SECURITY_EVENTS_ENABLED=true
GEOLOCATION_SECURITY_RISK_THRESHOLD=high            # low | high | critical
GEOLOCATION_SECURITY_HIGH_RISK_THRESHOLD=70
GEOLOCATION_SECURITY_RULE_PROXY=40
GEOLOCATION_SECURITY_RULE_TOR=80
GEOLOCATION_SECURITY_RULE_CRAWLER=20
GEOLOCATION_SECURITY_RULE_NEW_COUNTRY=30
GEOLOCATION_SECURITY_RULE_NEW_CITY=15
```

`trusted_countries`, `trusted_ips` and `custom_rules` live in the config file.

## Personalization

```env
GEOLOCATION_PERSONALIZATION_ENABLE_TIMEZONE=true
GEOLOCATION_PERSONALIZATION_ENABLE_CURRENCY=true
```

## Threat intelligence

```env
GEOLOCATION_THREAT_INTELLIGENCE_ENABLED=false
ABUSEIPDB_API_KEY=
GEOLOCATION_THREAT_MIN_SCORE=50
GEOLOCATION_THREAT_CACHE_TTL=3600       # successful lookups
GEOLOCATION_THREAT_FAILURE_TTL=300      # negative caching for failures
GEOLOCATION_THREAT_AUTO_BLOCK=false
```

> After editing `.env`, run `php artisan config:clear` locally. In production,
> re-run `php artisan config:cache` so the baked config picks up new values.

See [Provider Comparison](../providers/index.md) to choose a provider.
