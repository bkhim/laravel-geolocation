# Upgrading Guide

Migration guides for upgrading between versions.

## Upgrading to v4.7

### Behaviour changes

- **Addon flags now read `.env`.** `GEOLOCATION_MIDDLEWARE_ENABLED`,
  `GEOLOCATION_RATE_LIMITING_ENABLED`, `GEOLOCATION_ANONYMIZATION_ENABLED`,
  `GEOLOCATION_GDPR_ENABLED` and `GEOLOCATION_GDPR_ROUTES_ENABLED` were
  previously ignored because the config hard-coded `false`. If you relied on
  editing only the config file, nothing changes; if you set these env values,
  they now take effect.
- **IP anonymization semantics:** `enabled` is the master switch.
  `enabled=true` + empty `gdpr_countries` masks every IP; provide ISO codes to
  scope it. The packaged default list changed from EU-only to empty, so new
  installs mask everything when enabled.
- **Login IP storage** now uses the same configured `ipv4_mask` / `ipv6_mask`.
  Default IPv6 masking keeps a /64 instead of a /48.

### New configuration

```env
GEOLOCATION_ANONYMIZATION_IPV4_MASK=255.255.255.0
GEOLOCATION_ANONYMIZATION_IPV6_MASK=ffff:ffff:ffff:ffff:0000:0000:0000:0000
GEOLOCATION_ANONYMIZATION_PRESERVE_LOCAL=true
GEOLOCATION_ANONYMIZATION_COUNTRIES=
GEOLOCATION_SECURITY_EVENTS_ENABLED=true
GEOLOCATION_THREAT_CACHE_TTL=3600
GEOLOCATION_THREAT_FAILURE_TTL=300
GEOLOCATION_GDPR_ROUTE_GIVE=/consent/accept
GEOLOCATION_GDPR_ROUTE_WITHDRAW=/consent/withdraw
```

### Upgrade steps

```bash
composer update bkhim/laravel-geolocation
php artisan vendor:publish --tag=geolocation-config --force
php artisan vendor:publish --tag=geolocation-migrations   # adds blocklist + continent/region
php artisan migrate
php artisan config:clear
```

### Command changes

- `geolocation:update-maxmind` now extracts and installs the database
  (`--edition`, `--force`).
- `geolocation:prune` also removes expired blocklist entries (`--days`,
  `--keep-blocks`).
- `geolocation:cache` honours the configured store; bulk clears still require
  cache tags.

## Upgrading to v4.3

### Changes

- Added IP2Location.io as the 6th provider (`ip2locationio` driver)
- Minimum PHP requirement is now 8.2
- Added `IP2LOCATION_ADDONS` environment variable
- Added support for Laravel 13

### Configuration Changes

If using IP2Location.io, add to your `.env`:

```env
GEOLOCATION_DRIVER=ip2locationio
GEOLOCATION_IP2LOCATIONIO_API_KEY=your_api_key_here
IP2LOCATION_ADDONS=domain,apikey,timezone,isp,usertype,protection,credit
```

### Method Changes

- `getConnectionType()` now returns standardized values: `dialup`, `broadband`, `corporate`, `datacenter`, `satellite`
- ASN format now includes `AS` prefix (e.g., `AS15169` instead of `15169`)

---

## Upgrading to v4.0

### Breaking Changes

- Namespace changed from `AdrianoRosa\Geolocation` to `Bkhim\Geolocation`
- Facade class renamed from `Geolocation` to `Bkhim\Geolocation\Geolocation`
- Service provider renamed

### Configuration Changes

Update your `config/geolocation.php`:

- Driver names remain the same: `ipapi`, `ipinfo`, `ipstack`, `ipgeolocation`, `maxmind`
- Environment variables changed to use `GEOLOCATION_` prefix

### Code Changes

Before:
```php
use AdrianoRosa\Geolocation\Facades\Geolocation;
$details = Geolocation::lookup();
```

After:
```php
use Bkhim\Geolocation\Geolocation;
$details = Geolocation::lookup();
```

### Facades

Update facade imports:

```php
// Before
use AdrianoRosa\Geolocation\Facades\IpAnonymizer;
use AdrianoRosa\Geolocation\Facades\LocationConsentManager;

// After
use Bkhim\Geolocation\Facades\IpAnonymizer;
use Bkhim\Geolocation\Facades\LocationConsentManager;
```

---

## Upgrading from v3.x

### Configuration

The v4.0 config structure is different. Publish a fresh config:

```bash
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --tag=geolocation-config --force
php artisan migrate
```

### Provider Keys

All provider-specific environment variables now use `GEOLOCATION_` prefix:

```env
# Before
IPINFO_TOKEN=xxx
IPSTACK_KEY=xxx

# After
GEOLOCATION_IPINFO_ACCESS_TOKEN=xxx
GEOLOCATION_IPSTACK_ACCESS_KEY=xxx
```

---

## Troubleshooting

### Class not found errors

Ensure autoloader is updated:

```bash
composer dump-autoload
```

### Cache issues

Clear cache after upgrade:

```bash
php artisan config:clear
php artisan cache:clear
php artisan geolocation:cache clear
```
