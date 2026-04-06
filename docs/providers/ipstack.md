# IPStack Provider

IPStack provides comprehensive geolocation data with a simple API.

## Features

- ✅ Comprehensive data (city, region, country, coords, timezone, currency)
- ✅ HTTPS on all tiers
- ✅ Multi-language support
- ✅ IPv4 and IPv6 support
- ✅ Timezone data with UTC offset

## Limitations

- ❌ No proxy/VPN detection
- ❌ No mobile device detection
- ❌ No Tor detection

## Configuration

```env
GEOLOCATION_DRIVER=ipstack
GEOLOCATION_IPSTACK_ACCESS_KEY=your_api_key_here
```

Get your API key from https://ipstack.com/dashboard

## Usage

```php
$details = Geolocation::driver('ipstack')->lookup('8.8.8.8');

echo $details->getCity();           // Mountain View
echo $details->getRegion();         // California
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getLatitude();       // 37.386
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
echo $details->getCurrencyCode();   // USD
echo $details->getCurrency();       // US Dollar
echo $details->getCurrencySymbol(); // $
```

## Rate Limits

- Free tier: 100 requests/month
- Paid plans available for higher limits

## Pricing

| Plan | Requests/month | Price |
|------|---------------|-------|
| Free | 100 | Free |
| Basic | 10,000 | $12.99/mo |
| Professional | 50,000 | $39.99/mo |
| Professional Plus | 100,000 | $99.99/mo |

See https://ipstack.com/plans/ for latest pricing.
