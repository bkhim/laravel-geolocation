# Features to Implement / Known Issues

This document tracks features that were documented but don't exist yet.

## Issues Found in Documentation

### docs/features/caching.md - INCORRECT

```markdown
❌ INCORRECT (what was documented):
```php
// Manual Cache Operations
Geolocation::clearCache();
Geolocation::clearCache('8.8.8.8');
$key = Geolocation::getCacheKey('8.8.8.8');
```

✅ CORRECT (what actually exists):
```bash
# Console commands (work correctly)
php artisan geolocation:cache clear
php artisan geolocation:cache clear --provider=ipapi
php artisan geolocation:cache clear --provider=ipapi --ip=8.8.8.8
php artisan geolocation:cache info
php artisan geolocation:cache warm-up
php artisan geolocation:cache optimize
```

**Action:** Add these facade methods or update docs.

---

## Missing Features (Documented but Not Implemented)

### 1. Geolocation Facade Methods

**Claimed in docs but NOT implemented:**
- `Geolocation::clearCache($ip)` - Clear cache via facade
- `Geolocation::clearCache()` - Clear all cache via facade  
- `Geolocation::getCacheKey($ip)` - Get cache key via facade

**Actually exists:**
- `php artisan geolocation:cache clear` - Console command (works)
- `php artisan geolocation:cache info` - Console command (works)
- `php artisan geolocation:cache warm-up` - Console command (works)
- `php artisan geolocation:cache optimize` - Console command (works)

**Action:** Add these methods to `Geolocation` facade:
```php
// In src/Geolocation.php or add to GeolocationManager
public static function clearCache(?string $ip = null): void
public static function getCacheKey(string $ip): string
```

---

### 2. Events

**Events that DO exist:**
- `HighRiskIpDetected` - ✅ Implemented
- `SuspiciousLocationDetected` - ✅ Implemented  
- `GeoBlockedRequest` - ✅ Implemented
- `LoginLocationRecorded` - ✅ Implemented

**Action:** No changes needed.

---

### 3. Traits Methods

**Traits that DO exist:**
- `HasGeolocation` - ✅ Implemented
- `HasGeolocationSecurity` - ✅ Implemented
- `HasGeolocationPreferences` - ✅ Implemented
- `CachesGeolocationData` - ✅ Implemented
- `CalculatesTimezoneOffset` - ✅ Implemented

**Action:** No changes needed.

---

## Suggested New Features

### 1. Risk Scoring Service

Create a dedicated service for calculating risk scores:

```php
// Proposed: src/Services/RiskScorer.php
namespace Bkhim\Geolocation\Services;

class RiskScorer
{
    public function score(GeolocationDetails $details): int;
    public function isHighRisk(GeolocationDetails $details): bool;
    public function getRiskLevel(GeolocationDetails $details): string; // low, medium, high, critical
}
```

### 2. MFA Decision Service

Create a service for MFA decisions based on location:

```php
// Proposed: src/Services/MfaDecider.php
namespace Bkhim\Geolocation\Services;

class MfaDecider
{
    public function shouldRequireMfa(Request $request, int $failedAttempts = 0): bool;
    public function getReason(Request $request): string;
}
```

### 3. Anomaly Detection Service

```php
// Proposed: src/Services/AnomalyDetector.php
namespace Bkhim\Geolocation\Services;

class AnomalyDetector
{
    public function isAnomalous(string $ip, int $userId): bool;
    public function checkImpossibleTravel(GeolocationDetails $current, Collection $history): bool;
    public function checkNewCountry(string $ip, int $userId): bool;
}
```

### 4. Login History Model

Currently exists but needs enhancement:
- Add `impossibleTravel()` method
- Add location comparison methods
- Add query scopes for finding anomalies

### 5. Artisan Command Improvements

Current commands work but could add:
- `geolocation:lookup` - Already exists ✅
- `geolocation:cache:clear` - Rename from `geolocation:cache clear`
- `geolocation:update-db` - For MaxMind database updates

---

## Feature Priority

| Priority | Feature | Effort |
|----------|---------|--------|
| High | Add `Geolocation::clearCache()` to facade | Low |
| High | Add `Geolocation::getCacheKey()` to facade | Low |
| Medium | RiskScorer service | Medium |
| Medium | MfaDecider service | Medium |
| Low | AnomalyDetector service | Medium |
| Low | LoginHistory enhancements | Low |

---

## Implementation Plan

1. First, fix the facade methods (clearCache, getCacheKey)
2. Then create RiskScorer service
3. Then create MfaDecider service
4. Update docs to reflect actual capabilities
