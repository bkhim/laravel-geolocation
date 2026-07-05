# Laravel Geolocation

**The complete global app toolkit for Laravel.**  
Detect location, personalize experiences, prevent fraud, and secure logins — all in one package.

[![Latest Version](https://img.shields.io/packagist/v/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![Total Downloads](https://img.shields.io/packagist/dt/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![Tests](https://github.com/bkhim/laravel-geolocation/workflows/Tests/badge.svg)](https://github.com/bkhim/laravel-geolocation/actions)
[![Stable](https://img.shields.io/badge/status-stable-brightgreen.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![License](https://img.shields.io/packagist/l/bkhim/laravel-geolocation.svg)](https://github.com/bkhim/laravel-geolocation/blob/main/LICENSE)

---

## One Package. Everything You Need for a Global App.

| Feature | What It Does |
|---------|--------------|
| **Location Detection** | City, region, country, coordinates from any IP — 6 providers |
| **Timezone Management** | Auto-detect and convert times to user's local timezone |
| **Currency Detection** | Show prices in user's local currency and format |
| **Fraud Prevention** | Proxy/VPN/Tor detection, risk scoring, threat intelligence |
| **Login Security** | Track logins, detect new countries/cities, trigger MFA |
| **Geo-Blocking** | Allow/deny countries and continents via middleware |
| **GDPR and Privacy** | IP anonymization, consent management, data pruning |

---

## Trusted By Developers

- 780+ installs and growing
- 149 tests passing
- Built for Laravel 10-13
- PHP 8.2, 8.3, 8.4 compatible
- MIT Licensed

[View on Packagist](https://packagist.org/packages/bkhim/laravel-geolocation) ·
[Report Issues](https://github.com/bkhim/laravel-geolocation/issues) ·
[Star on GitHub](https://github.com/bkhim/laravel-geolocation)

---

## One Line of Code

```php
$details = Geolocation::lookup();
echo $details->getCity();          // "Mountain View"
echo $details->getTimezone();      // "America/Los_Angeles"
echo $details->getCurrencyCode();  // "USD"
```

## Security Built In (Not Bolted On)

```php
if ($details->isProxy() || $details->isTor()) {
    return redirect()->route('mfa.challenge');
}

// Or get a full risk assessment
$risk = $user->getRiskScore($request->ip());
// ['score' => 45, 'is_high_risk' => false, 'triggers' => [...]]
```

---

## 6 Providers. One API.

| Provider | Free Tier | Fraud Score | Proxy Detection |
|----------|-----------|-------------|-----------------|
| ipapi.co | 30k/mo | No | Yes |
| IP2Location.io | 50k/mo | Yes | Yes |
| IpInfo | Unlimited* | No | No |
| MaxMind | Unlimited | No | No |
| IPStack | 100/mo | No | No |
| IPGeolocation | 1k/mo | Yes | Yes |

*IpInfo Lite: country only

[Compare all providers](docs/providers/index.md)

---

## Quick Install

```bash
composer require bkhim/laravel-geolocation
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider" --tag=geolocation-config

# For security features (login tracking, IP blocking):
php artisan vendor:publish --tag=geolocation-migrations
php artisan migrate
```

```php
// That's it.
$location = Geolocation::lookup('8.8.8.8');
echo $location->getCountry(); // "United States"
```

---

## Use Cases

| Use Case | How It Helps |
|----------|-------------|
| **Global SaaS** | Auto-detect timezone, currency, locale for each user |
| **Login Security** | MFA triggers on new locations, block proxies/VPNs |
| **E-commerce** | Local currency, geo-blocking, shipping zone detection |
| **Analytics** | Visitor country/city tracking without a third-party service |
| **Fraud Prevention** | Risk scoring, impossible travel detection, IP blocklisting |

---

## Documentation

- [Getting Started](docs/getting-started/installation.md)
- [Security Features](docs/security/mfa-integration.md)
- [Providers](docs/providers/index.md)
- [API Reference](docs/api-reference.md)
- [Addons](docs/addons/gdpr-consent.md)
- [Testing](docs/testing.md)
- [Contributing](docs/contributing.md)

---

Built for Laravel 10-13 | PHP 8.2+ | [MIT License](LICENSE)
