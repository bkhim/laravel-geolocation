# CLI Commands

The package provides CLI commands for security management, maintenance, and troubleshooting.

---

## Available Commands

### `geolocation:audit`

Generate a security audit report.

```bash
# Last 30 days (default)
php artisan geolocation:audit

# Custom period
php artisan geolocation:audit --days=7
```

**Output:**
```
🔒 Geolocation Security Audit – April 11, 2026
Period: Last 30 days

Login Locations:
├─ 1,247 total logins
├─ 23 unique countries
├─ 47 logins from new countries
├─ 8 VPN/proxy logins detected
└─ 2 Tor exit node logins

Recommendations:
├─ Enable MFA for users logging from high-risk countries
└─ Review 12 logins occurring between midnight and 5AM

Compliance:
├─ IP Masking: 94% of IPs anonymized
└─ Data Retention: 30 days (configured)
```

---

### `geolocation:update-maxmind`

Download/update MaxMind database.

```bash
# Dry run - show what would happen
php artisan geolocation:update-maxmind --dry-run

# Download actual database
php artisan geolocation:update-maxmind
```

**Requirements:**
```env
# In .env
GEOLOCATION_MAXMIND_LICENSE_KEY=your_license_key
```

After downloading, extract the archive and replace the `.mmdb` file in `storage/app/geoip/`.

---

### `geolocation:prune`

Remove old login history data for GDPR compliance.

```bash
# Preview what would be deleted
php artisan geolocation:prune --dry-run

# Delete old records
php artisan geolocation:prune
```

**Configuration:**
```env
# In .env - days to retain (default: 30)
GEOLOCATION_LOGIN_RETENTION_DAYS=30
```

Or in `config/geolocation.php`:
```php
'user_trait' => [
    'login_history_retention_days' => 30,
],
```

---

### `geolocation:lookup`

Lookup geolocation data for an IP address.

```bash
# Specific IP
php artisan geolocation:lookup --ip=8.8.8.8

# Current server IP
php artisan geolocation:lookup

# Skip cache
php artisan geolocation:lookup --ip=8.8.8.4 --no-cache

# Clear cache first
php artisan geolocation:lookup --ip=8.8.8.4 --clear-cache

# Show cache info
php artisan geolocation:lookup --show-cache-info
```

---

### `geolocation:clear-cache`

Clear geolocation cache.

```bash
# Clear all geolocation cache
php artisan geolocation:clear-cache

# Clear specific IP cache
php artisan geolocation:clear-cache --ip=8.8.8.8
```

---

## Usage Examples

### Daily Security Check

```bash
# Run audit as part of daily cron
0 6 * * * php artisan geolocation:audit --days=1 >> /var/log/geo-audit.log
```

### Weekly Maintenance

```bash
# Weekly MaxMind update
0 2 * * 0 php artisan geolocation:update-maxmind

# Weekly data prune
0 3 * * 0 php artisan geolocation:prune
```

---

## Scheduling

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('geolocation:audit --days=1')->dailyAt('6am');
    $schedule->command('geolocation:prune')->weekly();
    $schedule->command('geolocation:update-maxmind')->weekly()->sundays()->at('2am');
}
```