# Laravel Geolocation Documentation

IP geolocation + fraud prevention for Laravel apps. Detect proxy/VPN/Tor, trigger MFA on suspicious logins, and personalize user experience.

## Overview

This package provides a unified API for IP geolocation with support for 6 providers, advanced security detection, and production-ready features.

## Documentation Sections

### Getting Started

- [Installation](getting-started/installation.md) - Install and configure the package
- [Configuration](getting-started/configuration.md) - Environment variables and provider settings
- [Quick Start](getting-started/quick-start.md) - Your first geolocation lookup

### Providers

- [Provider Comparison](providers/index.md) - Compare all 6 providers
- [ipapi.co](providers/ipapi.md) - Free tier, no API key required
- [IP2Location.io](providers/ip2location.md) - Advanced fraud detection
- [IpInfo](providers/ipinfo.md) - Popular choice with unlimited Lite tier
- [MaxMind](providers/maxmind.md) - Local database, no API calls
- [IPStack](providers/ipstack.md) - Comprehensive data
- [IPGeolocation](providers/ipgeolocation.md) - Security-focused

### Security

- [Risk Scoring](security/risk-scoring.md) - Calculate user risk scores
- [Anomaly Detection](security/anomaly-detection.md) - Detect suspicious activity
- [MFA Integration](security/mfa-integration.md) - Trigger 2FA on suspicious logins
- [Threat Intelligence](security/threat-intelligence.md) - Proxy/VPN/Tor detection

### Features

- [User Traits](features/user-traits.md) - Mobile, proxy, crawler detection
- [Middleware](features/middleware.md) - Geo-blocking and access control
- [Caching](features/caching.md) - Performance optimization
- [Fallback](features/fallback.md) - Provider redundancy
- [Events](features/events.md) - Hook into geolocation events

### Addons

- [GDPR Consent](addons/gdpr-consent.md) - EU privacy compliance
- [IP Anonymization](addons/ip-anonymization.md) - Privacy-preserving lookups
- [Rate Limiting](addons/rate-limiting.md) - Country-based rate limits

### Reference

- [API Reference](api-reference.md) - Complete method documentation
- [Upgrading](upgrading.md) - Migration guides
- [Testing](testing.md) - Running the test suite
- [Contributing](contributing.md) - How to contribute

## Quick Links

- [GitHub](https://github.com/bkhim/laravel-geolocation)
- [Packagist](https://packagist.org/packages/bkhim/laravel-geolocation)
- [Report Issues](https://github.com/bkhim/laravel-geolocation/issues)
