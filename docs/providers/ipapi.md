# ipapi.co Provider

ipapi.co is a free geolocation service requiring no API key. Great for getting started quickly.

## Features

- ✅ No API key required
- ✅ Free tier: 30,000 requests/month
- ✅ HTTPS supported
- ✅ IPv4 and IPv6 support
- ✅ City, region, country, coordinates
- ✅ Timezone, currency, ISP information
- ✅ Hostname lookup

## Limitations

- ❌ No proxy/VPN detection
- ❌ No mobile device detection
- ❌ No Tor detection
- ❌ No fraud scoring

## Configuration

```env
GEOLOCATION_DRIVER=ipapi
```

No additional configuration needed. The free tier works out of the box.

## Usage

```php
$details = Geolocation::driver('ipapi')->lookup('8.8.8.8');

echo $details->getCity();        // Mountain View
echo $details->getCountry();     // United States
echo $details->getCountryCode(); // US
echo $details->getLatitude();    // 37.386
echo $details->getLongitude();   // -122.0838
```

## Rate Limits

- Free tier: 30,000 requests/month
- Paid plans available for higher limits

## Pricing

| Plan | Requests/month | Price |
|------|---------------|-------|
| Free | 30,000 | Free |
| Basic | 150,000 | $15/mo |
| Standard | 500,000 | $35/mo |
| Professional | 2,000,000 | $95/mo |

See https://ipapi.co/pricing/ for latest pricing.
