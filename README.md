# Laravel Geolocation

**IP geolocation + fraud prevention for Laravel.**  
Detect proxy/VPN/Tor, trigger MFA on suspicious logins, personalize user experience.

[![Latest Version](https://img.shields.io/packagist/v/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![Total Downloads](https://img.shields.io/packagist/dt/bkhim/laravel-geolocation.svg)](https://packagist.org/packages/bkhim/laravel-geolocation)
[![Tests](https://github.com/bkhim/laravel-geolocation/workflows/Tests/badge.svg)](https://github.com/bkhim/laravel-geolocation/actions)
[![License](https://img.shields.io/packagist/l/bkhim/laravel-geolocation.svg)](https://github.com/bkhim/laravel-geolocation/blob/main/LICENSE)

---

## ✨ One Line of Code

```php
$details = Geolocation::lookup();
echo $details->getCity(); // "Mountain View"
```

🛡️ Security First

```php
if ($details->isProxy() || $details->isTor()) {
    return redirect()->route('mfa');
}
```

🌍 6 Providers. One API.

| Provider | Free Tier | Fraud Score | Proxy Detection |
|----------|-----------|-------------|-----------------|
| ipapi.co | 30k/mo | ❌ | ✅ |
| IP2Location.io | 50k/mo | ✅ | ✅ |
| IpInfo | Unlimited* | ❌ | ❌ |
| MaxMind | Unlimited | ❌ | ❌ |
| IPStack | 100/mo | ❌ | ❌ |
| IPGeolocation | 1k/mo | ✅ | ✅ |

*IpInfo Lite: country only

[→ Compare all providers](docs/providers/index.md)

---

## ⚡ Quick Install

```bash
composer require bkhim/laravel-geolocation
php artisan vendor:publish --provider="Bkhim\Geolocation\GeolocationServiceProvider"
```

## Documentation

- [🚀 Getting Started](docs/getting-started/installation.md)
- [🛡️ Security Features](docs/security/mfa-integration.md)
- [🌍 Providers](docs/providers/index.md)
- [📖 API Reference](docs/api-reference.md)
- [🔧 Addons](docs/addons/gdpr-consent.md)
- [🧪 Testing](docs/testing.md)
- [🤝 Contributing](docs/contributing.md)

## Use Cases

- 🔐 **Login Security** – MFA triggers on suspicious locations
- 💰 **E-commerce** – Local currency, geo-blocking
- 📊 **Analytics** – Visitor location tracking
- 🛡️ **Fraud Prevention** – Proxy/VPN/Tor detection

---

Built for Laravel 10–13 | PHP 8.2+ | [MIT License](LICENSE)
