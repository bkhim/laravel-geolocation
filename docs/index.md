# Laravel Geolocation Documentation

**The complete global app toolkit for Laravel.** Detect location, personalize experiences, prevent fraud, and secure logins — all in one package.

---

## Quick Start (30 Seconds)

```bash
composer require bkhim/laravel-geolocation
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --tag=geolocation-config

# For security features (login tracking, IP blocking):
php artisan vendor:publish --tag=geolocation-migrations
php artisan migrate
```

```php
// One line of code
$details = Geolocation::lookup();
echo $details->getCity(); // "Mountain View"
echo $details->isProxy(); // false
```

---

## Why This Package?

| Feature | Description |
|---------|-------------|
| **6 Providers** | One API — switch providers without code changes |
| **Location & Timezone** | City, region, coordinates, timezone, currency from any IP |
| **Fraud Prevention** | Proxy/VPN/Tor detection, risk scoring, threat intelligence |
| **Login Security** | Track logins, detect new countries/cities, trigger MFA |
| **Geo-Blocking** | Allow/deny countries and continents via middleware |
| **Compliance Built-in** | GDPR consent, IP anonymization (IPv4 + IPv6), audit logging |
| **Caching** | TTL, configurable prefix, tags, sliding expiration on Laravel 13+ |
| **CLI Tools** | Lookup, cache management, security audit, pruning, MaxMind updater |

---

## Documentation Sections

### Getting Started

1. [Installation](getting-started/installation.md) - Install the package
2. [Configuration](getting-started/configuration.md) - Environment variables
3. [Quick Start](getting-started/quick-start.md) - Your first lookup

### Security (Recommended)

- [Security Overview](security/overview.md) - Complete security guide
- [Anomaly Detection](security/anomaly-detection.md) - Detect impossible travel, new locations
- [MFA Integration](security/mfa-integration.md) - Trigger 2FA on suspicious logins
- [Risk Scoring](security/risk-scoring.md) - Calculate user risk scores
- [Threat Intelligence](security/threat-intelligence.md) - AbuseIPDB integration
- [IP Blocking](security/ip-blocking.md) - Block repeat offenders

### Providers

- [Provider Comparison](providers/index.md) - Compare all 6 providers
- [ipapi.co](providers/ipapi.md) - Free tier, no API key
- [IP2Location.io](providers/ip2location.md) - Fraud detection
- [IpInfo](providers/ipinfo.md) - Popular choice
- [MaxMind](providers/maxmind.md) - Local database
- [IPStack](providers/ipstack.md) - Comprehensive data
- [IPGeolocation](providers/ipgeolocation.md) - Security-focused

### Features

- [CLI Commands](features/commands.md) - Lookup, cache, audit, prune, MaxMind updates
- [Middleware](features/middleware.md) - Geo-blocking, per-country rate limiting, threat blocking
- [Caching](features/caching.md) - TTL, sliding expiration, tags, prefix
- [Fallback](features/fallback.md) - Provider redundancy
- [Events](features/events.md) - Automatic security events + your listeners
- [User Traits](features/user-traits.md) - Login history, risk scoring, personalization

### Compliance

- [GDPR Consent](addons/gdpr-consent.md) - EU privacy compliance
- [IP Anonymization](addons/ip-anonymization.md) - Privacy-preserving
- [Rate Limiting](addons/rate-limiting.md) - Country-based limits

### Reference

- [API Reference](api-reference.md) - Complete method documentation
- [Contributing](contributing.md) - How to contribute
- [Testing](testing.md) - Running tests
- [Upgrading](upgrading.md) - Migration guides

---

## CLI Commands

```bash
# Security audit - see exactly what to fix
php artisan geolocation:audit

# Update MaxMind database
php artisan geolocation:update-maxmind

# Prune old data for GDPR
php artisan geolocation:prune

# Lookup an IP
php artisan geolocation:lookup --ip=1.2.3.4
```

---

## Security Quick Start

```php
// In your login controller
$details = Geolocation::lookup($request->ip());

// Block proxies/VPNs
if ($details->isProxy() || $details->isTor()) {
    return redirect()->route('mfa.challenge');
}

// Or use the middleware
Route::middleware('geo.security')->group(function () {
    // Protected routes
});
```

---

## Requirements

- PHP 8.2 – 8.5
- Laravel 12.x - 13.x
- 187 tests / 365 assertions

---

## Links

- [GitHub](https://github.com/bkhim/laravel-geolocation)
- [Packagist](https://packagist.org/packages/bkhim/laravel-geolocation)
- [Report Issues](https://github.com/bkhim/laravel-geolocation/issues)
