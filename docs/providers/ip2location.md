# IP2Location.io Provider

IP2Location.io provides advanced fraud detection with proxy, VPN, Tor, and mobile detection.

## Features

- ✅ Advanced fraud detection
- ✅ Proxy/VPN detection
- ✅ Tor exit node detection
- ✅ Mobile device detection
- ✅ Crawler/bot detection
- ✅ Free tier: 50,000 requests/day
- ✅ Comprehensive geolocation data
- ✅ Multi-language support

## Configuration

```env
GEOLOCATION_DRIVER=ip2location
GEOLOCATION_IP2LOCATION_API_KEY=your_api_key_here
IP2LOCATION_ADDONS=domain,apikey,timezone,isp,usertype,protection,credit
```

Get your API key from https://ipapi.co/product/ip2location/

## Usage

```php
$details = Geolocation::driver('ip2location')->lookup('8.8.8.8');

// Security detection
if ($details->isProxy()) {
    // Proxy or VPN detected
}

if ($details->isTor()) {
    // Tor exit node detected
}

if ($details->isMobile()) {
    // Mobile connection
}

if ($details->isCrawler()) {
    // Bot or crawler detected
}

echo $details->getConnectionType(); // broadband, corporate, datacenter, etc.
```

## Addons

The `IP2LOCATION_ADDONS` environment variable enables additional data:

- `domain` - Domain name
- `apikey` - API key information
- `timezone` - Timezone with GMT offset
- `isp` - ISP name
- `usertype` - User type (residential, business, etc.)
- `protection` - Proxy/VPN/Tor detection
- `credit` - Remaining credits

## Rate Limits

- Free tier: 50,000 requests/day
- Paid plans available for higher limits

## Pricing

| Plan | Requests/day | Price |
|------|-------------|-------|
| Free | 50,000 | Free |
| Developer | 150,000 | $29/mo |
| Business | 500,000 | $79/mo |
| Enterprise | Unlimited | $199/mo |

See https://ip2location.io/pricing for latest pricing.
