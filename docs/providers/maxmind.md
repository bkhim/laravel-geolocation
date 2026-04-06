# MaxMind Provider

MaxMind provides local database-based geolocation. No API calls needed - fastest option.

## Features

- ✅ Local database - no API calls, no rate limits
- ✅ Works offline
- ✅ Fastest provider (local lookup)
- ✅ Unlimited lookups
- ✅ No API key required (for free GeoLite2)
- ✅ Comprehensive geolocation data

## Limitations

- ❌ No proxy/VPN detection
- ❌ No mobile device detection
- ❌ No Tor detection
- ❌ No fraud scoring
- ❌ Database requires manual updates

## Configuration

```env
GEOLOCATION_DRIVER=maxmind
MAXMIND_DATABASE_PATH=/path/to/GeoLite2-City.mmdb
MAXMIND_LICENSE_KEY=your_license_key
```

### Getting the Database

1. Create a free account at https://www.maxmind.com/
2. Download GeoLite2 City database
3. Extract to your preferred location
4. Set `MAXMIND_DATABASE_PATH` in `.env`

For higher accuracy, purchase the commercial GeoIP2 database.

## Usage

```php
$details = Geolocation::driver('maxmind')->lookup('8.8.8.8');

echo $details->getCity();           // Mountain View
echo $details->getRegion();         // California
echo $details->getCountry();        // United States
echo $details->getCountryCode();    // US
echo $details->getLatitude();       // 37.3861
echo $details->getLongitude();      // -122.0838
echo $details->getTimezone();       // America/Los_Angeles
echo $details->getPostalCode();     // 94043
echo $details->getIsp();            // Google LLC
echo $details->getAsn();            // AS15169
```

## Performance Tips

- Use `ext-maxminddb` PHP extension for best performance
- Keep database file on fast storage (SSD)
- Update database monthly for accuracy

## Database Updates

MaxMind databases should be updated regularly for accuracy. You can automate with cron:

```bash
# Update GeoLite2 database monthly
0 0 1 * * /usr/bin/php /path/to/your/project/artisan geolocation:update-db
```

See https://dev.maxmind.com/geoip/geolite2-free-geolocation-data for more info.
