# GDPR Consent Management

Manage GDPR consent for location tracking in your Laravel application.

## Overview

The GDPR (General Data Protection Regulation) requires consent before collecting personal data from EU users. This addon helps manage consent for IP-based geolocation.

## Configuration

In `config/geolocation.php`:

```php
'addons' => [
    'gdpr' => [
        'enabled' => env('GEOLOCATION_GDPR_ENABLED', false),
        'require_consent_for' => ['GDPR'], // EU + UK + EEA
        'consent_cookie' => 'geo_consent',
        'consent_lifetime' => 365, // days
        'banner_view' => 'geolocation::gdpr.banner',
        
        // Built-in consent routes (optional)
        'routes' => [
            'enabled' => env('GEOLOCATION_GDPR_ROUTES_ENABLED', false),
            'give' => '/consent/accept',
            'withdraw' => '/consent/withdraw',
        ],
    ],
],
```

## Auto-Generated Routes (Recommended)

Enable built-in consent routes for out-of-the-box usage:

```php
// config/geolocation.php
'addons' => [
    'gdpr' => [
        'enabled' => true,
        'routes' => [
            'enabled' => true,
        ],
    ],
],
```

This creates:
- `POST /consent/accept` - Give consent
- `POST /consent/withdraw` - Withdraw consent

### Using in Blade

```blade
<form method="POST" action="{{ route('geolocation.consent.give') }}">
    @csrf
    <button type="submit">Accept</button>
</form>

<form method="POST" action="{{ route('geolocation.consent.withdraw') }}">
    @csrf
    <button type="submit">Withdraw</button>
</form>
```

## Manual Routes (Alternative)

If you prefer custom routes:

```php
// routes/web.php
Route::post('/consent/accept', [ConsentController::class, 'accept'])->name('consent.accept');
Route::post('/consent/withdraw', [ConsentController::class, 'withdraw'])->name('consent.withdraw');

// ConsentController.php
public function accept()
{
    LocationConsentManager::giveConsent();
    return back();
}

public function withdraw()
{
    LocationConsentManager::withdrawConsent();
    return back();
}
```

## Usage

```php
use Bkhim\Geolocation\Facades\LocationConsentManager;

// Check if consent is needed (with explicit IP)
if (LocationConsentManager::needsConsent($request->ip())) {
    return redirect('/consent-banner');
}

// Check if consent is needed (uses request IP automatically)
if (LocationConsentManager::needsConsent()) {
    return redirect('/consent-banner');
}

// Check if user has already given consent
if (LocationConsentManager::hasGivenConsent()) {
    // Proceed with geolocation
}

// Give consent (set cookie)
LocationConsentManager::giveConsent();

// Withdraw consent
LocationConsentManager::withdrawConsent();
```

## Regions

The addon supports these region definitions:

- `EU` - European Union countries
- `EEA` - European Economic Area (EU + Iceland, Liechtenstein, Norway)
- `GDPR` - Full GDPR coverage (EU + UK + EEA)

```php
// Require consent for GDPR region
'require_consent_for' => ['GDPR']
```

## Implementation Example

### Middleware

```php
// app/Http/Middleware/CheckGeoConsent.php
public function handle($request, $next)
{
    // Skip if GDPR disabled
    if (!config('geolocation.addons.gdpr.enabled')) {
        return $next($request);
    }
    
    // Skip if consent already given
    if (LocationConsentManager::hasGivenConsent()) {
        return $next($request);
    }
    
    // Check if consent is needed (EU user)
    if (LocationConsentManager::needsConsent($request->ip())) {
        // Return consent banner
        return response()->view('consent-banner');
    }
    
    return $next($request);
}
```

### Blade Component

```html
<!-- resources/views/consent-banner.blade.php -->
<div class="consent-banner">
    <p>We use your location to personalize your experience.</p>
    <form method="POST" action="/consent/give">
        @csrf
        <button type="submit">Accept</button>
    </form>
    <form method="POST" action="/consent/withdraw">
        @csrf
        <button type="submit">Decline</button>
    </form>
</div>
```
