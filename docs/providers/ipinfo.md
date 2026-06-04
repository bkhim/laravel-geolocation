# IpInfo Provider

IpInfo is a popular geolocation service with a unique "unlimited" Lite tier.

## Features

- ✅ Free Lite tier: Unlimited requests (country only)
- ✅ Paid plans: Full geolocation data
- ✅ HTTPS supported
- ✅ City, region, coordinates, timezone on paid plans
- ✅ ISP and ASN information

## Limitations

- ❌ No proxy/VPN detection
- ❌ No mobile device detection
- ❌ No Tor detection
- ❌ No fraud scoring

## Configuration

```env
GEOLOCATION_DRIVER=ipinfo
GEOLOCATION_IPINFO_ACCESS_TOKEN=your_token_here
```

Get your token from https://ipinfo.io/account/token

## Usage

```php
$details = Geolocation::driver('ipinfo')->lookup('8.8.8.8');

echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US

// On paid plans:
echo $details->getCity();           // Mountain View
echo $details->getRegion();         // California
echo $details->getLatitude();       // 37.386
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
```

## Plan Comparison

| Plan | Data | Price |
|------|------|-------|
| Lite | Country only | Free |
| Core | City, region, coords, timezone, postal | $49/mo |
| Plus | Core + privacy detection | $74/mo |

See https://ipinfo.io/pricing for latest pricing.

## Notes

- The Lite plan only returns country data. Upgrade to Core or Plus for city-level geolocation.
- Plus plan adds privacy detection (proxy, VPN, hosting).
