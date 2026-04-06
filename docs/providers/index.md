# Provider Comparison

This package supports 6 geolocation providers. Choose based on your needs.

## Comparison Matrix

| Provider | Free Tier | API Key | Fraud Score | Proxy Detection | Mobile Detection |
|----------|-----------|---------|-------------|------------------|------------------|
| **ipapi.co** | 30,000/month | ❌ No | ❌ Basic | ✅ | ❌ |
| **IP2Location.io** | 50,000/day | ✅ Yes | ✅ Advanced | ✅ | ✅ |
| **IpInfo** | Unlimited (Lite) | ✅ Yes | ❌ Basic | ❌ | ❌ |
| **MaxMind** | Unlimited (local) | ❌ No | ❌ None | ❌ | ❌ |
| **IPStack** | 100/month | ✅ Yes | ❌ Basic | ❌ | ❌ |
| **IPGeolocation** | 1,000/month | ✅ Yes | ✅ Paid | ✅ | ✅ |

## Recommendations

| Use Case | Recommended Provider |
|----------|---------------------|
| **Getting started, no API key** | ipapi.co |
| **Fraud prevention** | IP2Location.io |
| **High volume** | MaxMind (local DB) |
| **Free unlimited lookups** | IpInfo Lite (country only) |
| **Security features** | IPGeolocation, IP2Location.io |
| **Comprehensive data** | IPStack, IPGeolocation |

## Feature Matrix

| Feature | ipapi | IP2Location | IpInfo | MaxMind | IPStack | IPGeolocation |
|---------|-------|-------------|--------|---------|---------|---------------|
| City | ✅ | ✅ | ✅* | ✅ | ✅ | ✅ |
| Region | ✅ | ✅ | ✅* | ✅ | ✅ | ✅ |
| Country | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Coordinates | ✅ | ✅ | ✅* | ✅ | ✅ | ✅ |
| Timezone | ✅ | ✅ | ✅* | ✅ | ✅ | ✅ |
| Currency | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ |
| ISP/Org | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ASN | ✅ | ✅ | ✅* | ✅ | ✅ | ✅ |
| Proxy/VPN | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Tor Detection | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Mobile | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Crawler/Bot | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Hostname | ✅ | ✅ | ✅* | ✅ | ✅ | ✅* |
| Translation | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |

*Available on paid plans only

## Provider Details

- [ipapi.co](ipapi.md) - Free, no API key required
- [IP2Location.io](ip2location.md) - Best for fraud prevention
- [IpInfo](ipinfo.md) - Popular, unlimited Lite tier
- [MaxMind](maxmind.md) - Local database, fastest
- [IPStack](ipstack.md) - Comprehensive data
- [IPGeolocation](ipgeolocation.md) - Security-focused
