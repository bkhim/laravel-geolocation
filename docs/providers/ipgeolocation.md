# IPGeolocation Provider

IPGeolocation provides security-focused geolocation with proxy/VPN detection on paid plans.

## Features

- ✅ Security detection (proxy, VPN, Tor on paid plans)
- ✅ Mobile device detection
- ✅ Comprehensive geolocation data
- ✅ Multi-language support
- ✅ Company/ISP data on paid plans

## Configuration

```env
GEOLOCATION_DRIVER=ipgeolocation
GEOLOCATION_IPGEOLOCATION_API_KEY=your_api_key_here
IPGEOLOCATION_LANGUAGE=en
IPGEOLOCATION_INCLUDE_HOSTNAME=false
IPGEOLOCATION_INCLUDE_SECURITY=false
IPGEOLOCATION_INCLUDE_USERAGENT=false
```

Get your API key from https://ipgeolocation.io/dashboard

## Usage

```php
$details = Geolocation::driver('ipgeolocation')->lookup('8.8.8.8');

echo $details->getCity();           // Mountain View
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getTimezone();       // America/Los_Angeles

// On paid plans with security enabled:
if ($details->isProxy()) {
    // Proxy detected
}

if ($details->isMobile()) {
    // Mobile connection
}
```

## Configuration Options

| Variable | Description | Default |
|----------|-------------|---------|
| `IPGEOLOCATION_LANGUAGE` | Response language (en, es, fr, etc.) | en |
| `IPGEOLOCATION_INCLUDE_HOSTNAME` | Include hostname (paid) | false |
| `IPGEOLOCATION_INCLUDE_SECURITY` | Include security data (paid) | false |
| `IPGEOLOCATION_INCLUDE_USERAGENT` | Include user agent data (paid) | false |

## Rate Limits

- Free tier: 1,000 requests/month
- Paid plans: 150,000+ requests/month

## Pricing

| Plan | Requests/month | Price |
|------|---------------|-------|
| Free | 1,000 | Free |
| Developer | 150,000 | $29/mo |
| Business | 500,000 | $79/mo |
| Enterprise | Unlimited | Custom |

See https://ipgeolocation.io/pricing for latest pricing.

## Notes

- Security detection (proxy, VPN, Tor) requires `IPGEOLOCATION_INCLUDE_SECURITY=true`
- Hostname lookup requires `IPGEOLOCATION_INCLUDE_HOSTNAME=true`
- Company data available on Business+ plans
- Each API call consumes credits based on data returned
