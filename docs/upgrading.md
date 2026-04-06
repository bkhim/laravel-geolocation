# Upgrading Guide

Migration guides for upgrading between versions.

## Upgrading to v4.3

### Changes

- Added IP2Location.io as the 6th provider
- Minimum PHP requirement is now 8.2
- Added `IP2LOCATION_ADDONS` environment variable
- Added support for Laravel 13

### Configuration Changes

If using IP2Location.io, add to your `.env`:

```env
GEOLOCATION_DRIVER=ip2location
GEOLOCATION_IP2LOCATION_API_KEY=your_api_key_here
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
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --force
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
php artisan cache:clear
php artisan geolocation:cache:clear
```
