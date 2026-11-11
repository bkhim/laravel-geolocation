# IP2Location.io Provider

IP2Location.io provides advanced fraud detection with proxy, VPN, Tor, and mobile detection.

## Features

- Proxy / VPN detection
- Tor exit node detection
- Mobile carrier detection (MCC/MNC)
- Crawler detection
- Full geolocation data (city, region, coordinates, postal code)
- Currency and continent data
- Multi-language place names (paid plans)

## Configuration

```env
GEOLOCATION_DRIVER=ip2locationio
GEOLOCATION_IP2LOCATIONIO_API_KEY=your_api_key_here
GEOLOCATION_IP2LOCATIONIO_LANGUAGE=en
```

Get a key from https://www.ip2location.io/. An API key is optional: without a
key the free tier allows 1,000 lookups/day. With a free key the allowance rises
to 50,000 lookups/month.

Language note: only `en` is available without a paid plan. For other languages
use one of:

`ar, cs, da, de, en, es, et, fi, fr, ga, gl, it, ja, ko, ms, nl, pt, ru, sv, tr, vi, zh-cn, zh-tw`

If a translation error (10004) is returned, the provider automatically retries
without the `lang` parameter.

## Usage

```php
$details = Geolocation::driver('ip2locationio')->lookup('8.8.8.8');

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

echo $details->getConnectionType(); // dialup, broadband, corporate, datacenter, satellite
```

## Data availability

| Field | Notes |
|-------|-------|
| Country / region / city | available |
| Coordinates, postal code | available |
| Timezone + GMT offset | available |
| Currency | available |
| ASN / AS name / ISP | available |
| Proxy | available |
| Tor / crawler | paid plans |
| Mobile (MCC/MNC) | available |

Fields the current plan does not expose come back as `null` rather than erroring.

## Rate limits

- Without a key: 1,000 requests/day
- Free key: 50,000 requests/month
- Paid plans: higher monthly allowances

See https://www.ip2location.io/pricing for current pricing.
