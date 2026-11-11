# CLI Commands

The package registers five Artisan commands for security management, maintenance and troubleshooting. They are available automatically after install (no manual registration).

---

## `geolocation:lookup`

Look up an IP using the configured driver.

```bash
# Configured default provider
php artisan geolocation:lookup --ip=8.8.8.8

# Current server IP (when --ip is omitted)
php artisan geolocation:lookup

# Bypass the cache for this lookup
php artisan geolocation:lookup --ip=8.8.8.8 --no-cache

# Clear the (tagged) cache first
php artisan geolocation:lookup --ip=8.8.8.8 --clear-cache

# Show the resolved cache configuration
php artisan geolocation:lookup --show-cache-info
```

---

## `geolocation:cache`

Manage the geolocation cache.

```bash
# Show cache configuration and driver capabilities
php artisan geolocation:cache info

# Clear a single IP for one provider (works without tags)
php artisan geolocation:cache clear --ip=8.8.8.8 --provider=ipapi

# Clear all geolocation entries (requires a taggable store: redis/memcached)
php artisan geolocation:cache clear

# Clear every provider's entries (requires tags)
php artisan geolocation:cache clear --provider=ipapi

# Warm one provider, or all configured providers
php artisan geolocation:cache warm-up
php artisan geolocation:cache warm-up --provider=ipinfo

# Inspect TTL, sliding support, driver recommendations
php artisan geolocation:cache optimize
```

> Bulk clears require cache tags (`cache.tags.enabled=true`) and a taggable
> store. Without tags you can still clear individual entries with `--ip`.

---

## `geolocation:audit`

Generate a security audit report from `user_login_locations`.

```bash
# Last 30 days (default)
php artisan geolocation:audit

# Custom period
php artisan geolocation:audit --days=7
```

Sample output:

```
Geolocation Security Audit – November 11, 2026
Period: Last 30 days

Login Locations:
├─ 1,247 total logins
├─ 23 unique countries
├─ 47 logins from new countries
├─ 8 VPN/proxy logins detected
└─ 2 Tor exit node logins

Recommendations:
├─ Enable MFA for users with VPN/proxy logins (8 users)
└─ Review 12 logins occurring between midnight and 5AM

Compliance:
├─ IP Masking: 94% of IPs anonymized
└─ Data Retention: 30 days (configured)
```

If the login-history table has not been migrated yet, the command says so and exits cleanly.

---

## `geolocation:prune`

Remove old login history and expired IP blocklist entries.

```bash
# Preview
php artisan geolocation:prune --dry-run

# Delete using the configured retention window
php artisan geolocation:prune

# Override the retention period for this run
php artisan geolocation:prune --days=90

# Keep expired blocklist rows (login history only)
php artisan geolocation:prune --keep-blocks
```

Retention is configured with:

```env
GEOLOCATION_LOGIN_RETENTION_DAYS=30
```

or `config/geolocation.php` → `user_trait.login_history_retention_days`.

---

## `geolocation:update-maxmind`

Download, extract and install a MaxMind database.

```bash
# Show what would happen
php artisan geolocation:update-maxmind --dry-run

# Download, extract and install (overwrites after confirmation)
php artisan geolocation:update-maxmind

# Overwrite without asking (for cron)
php artisan geolocation:update-maxmind --force

# Download a different edition
php artisan geolocation:update-maxmind --edition=GeoLite2-Country
```

Requirements:

```env
MAXMIND_LICENSE_KEY=your_license_key
MAXMIND_DATABASE_PATH=storage/app/geoip/GeoLite2-City.mmdb
```

The command extracts the archive with the system `tar` binary, locates the
`.mmdb` file and moves it into place atomically, then removes the archive.

---

## Scheduling

Laravel 11+ schedules commands in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('geolocation:audit --days=1')->dailyAt('06:00');
Schedule::command('geolocation:prune')->weekly();
Schedule::command('geolocation:update-maxmind --force')->weekly()->sundays()->at('02:00');
```

