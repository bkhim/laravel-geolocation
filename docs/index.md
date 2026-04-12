# Laravel Geolocation Documentation

**IP geolocation + fraud prevention for Laravel.** Detect proxy/VPN/Tor, trigger MFA on suspicious logins, and personalize user experience.

---

## Quick Start (30 Seconds)

```bash
composer require bkhim/laravel-geolocation
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
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
| **6 Providers** | One API - switch providers without code changes |
| **Security First** | Proxy/VPN/Tor detection, MFA triggers, threat intelligence |
| **Compliance Built-in** | GDPR consent, IP anonymization, audit logging |
| **CLI Tools** | Security audits, MaxMind updates, data pruning |

---

## Documentation Sections

### 🚀 Getting Started

1. [Installation](getting-started/installation.md) - Install the package
2. [Configuration](getting-started/configuration.md) - Environment variables
3. [Quick Start](getting-started/quick-start.md) - Your first lookup

### 🛡️ Security (Recommended)

- [Security Overview](security/overview.md) - Complete security guide
- [Anomaly Detection](security/anomaly-detection.md) - Detect impossible travel, new locations
- [MFA Integration](security/mfa-integration.md) - Trigger 2FA on suspicious logins
- [Risk Scoring](security/risk-scoring.md) - Calculate user risk scores
- [Threat Intelligence](security/threat-intelligence.md) - AbuseIPDB integration
- [IP Blocking](security/ip-blocking.md) - Block repeat offenders

### 🌎 Providers

- [Provider Comparison](providers/index.md) - Compare all 6 providers
- [ipapi.co](providers/ipapi.md) - Free tier, no API key
- [IP2Location.io](providers/ip2location.md) - Fraud detection
- [IpInfo](providers/ipinfo.md) - Popular choice
- [MaxMind](providers/maxmind.md) - Local database
- [IPStack](providers/ipstack.md) - Comprehensive data
- [IPGeolocation](sources/ipgeolocation.md) - Security-focused

### ⚡ Features

- [CLI Commands](features/commands.md) - Audit, MaxMind updates, pruning
- [Middleware](features/middleware.md) - Geo-blocking, rate limiting
- [Caching](features/caching.md) - Performance optimization
- [Fallback](features/fallback.md) - Provider redundancy
- [Events](features/events.md) - Hook into geolocation events
- [User Traits](features/user-traits.md) - Model traits for User

### ⚖️ Compliance

- [GDPR Consent](addons/gdpr-consent.md) - EU privacy compliance
- [IP Anonymization](addons/ip-anonymization.md) - Privacy-preserving
- [Rate Limiting](addons/rate-limiting.md) - Country-based limits

### 📚 Reference

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

- PHP 8.2+
- Laravel 10.x - 13.x

---

## Links

- [GitHub](https://github.com/bkhim/laravel-geolocation)
- [Packagist](https://packagist.org/packages/bkhim/laravel-geolocation)
- [Report Issues](https://github.com/bkhim/laravel-geolocation/issues)