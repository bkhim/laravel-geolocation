# MFA Integration

Trigger multi-factor authentication (MFA/2FA) based on geolocation and security signals. Use the built-in `HasGeolocationSecurity` trait for automatic MFA triggers.

## Using the Built-in Trait

Add the trait to your User model:

```php
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;

class User extends Model
{
    use HasGeolocationSecurity;
}
```

## Built-in MFA Methods

### `requiresMfaDueToLocation($ip = null)`

Automatically determine if MFA is needed. Uses the current request IP if no IP is provided:

```php
$user = User::find(1);

// With explicit IP
if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.challenge');
}

// Uses request()->ip() automatically
if ($user->requiresMfaDueToLocation()) {
    return redirect()->route('mfa.challenge');
}
```

This method checks:
- Proxy/VPN detection
- Tor exit nodes
- New country login
- Risk level thresholds (configured in config)

### `isHighRiskLogin($ip = null)`

Check if a login is high risk. Uses the current request IP if no IP is provided:

```php
// Explicit IP
if ($user->isHighRiskLogin($request->ip())) {
    Log::warning('High risk login detected', [
        'user_id' => $user->id,
        'ip' => $request->ip(),
    ]);
}

// Uses request()->ip() automatically
if ($user->isHighRiskLogin()) {
    Log::warning('High risk login detected');
}
```

## Manual MFA Logic

If you need custom MFA logic without the trait:

```php
use Bkhim\Geolocation\Geolocation;

public function shouldRequireMfa(string $ip, int $failedAttempts = 0): bool
{
    // Always require MFA after too many failed attempts
    if ($failedAttempts >= 3) {
        return true;
    }
    
    // Check geolocation
    $details = Geolocation::lookup($ip);
    
    // Require MFA for suspicious indicators
    return $details->isProxy() 
        || $details->isTor() 
        || $details->isCrawler();
}
```

## Advanced MFA Logic

```php
class MfaDecider
{
    public function requiresMfa(Request $request, int $failedAttempts = 0): bool
    {
        $details = Geolocation::lookup($request->ip());
        
        // Check risk score
        $riskScore = $this->calculateRiskScore($details);
        
        // Thresholds
        return $failedAttempts >= 3
            || $riskScore >= 30
            || $this->isNewDevice($request, $details)
            || $this->isUnusualLocation($request->ip(), Auth::id());
    }
    
    protected function calculateRiskScore(GeolocationDetails $details): int
    {
        $score = 0;
        
        if ($details->isTor()) $score += 50;
        if ($details->isProxy()) $score += 30;
        if ($details->isCrawler()) $score += 40;
        if ($details->getConnectionType() === 'datacenter') $score += 20;
        
        return $score;
    }
    
    protected function isNewDevice(Request $request, GeolocationDetails $details): bool
    {
        // Check if this is first login from this country
        $countryCount = LoginHistory::where('user_id', Auth::id())
            ->where('country_code', $details->getCountryCode())
            ->count();
        
        return $countryCount === 0;
    }
    
    protected function isUnusualLocation(string $ip, int $userId): bool
    {
        // Check if login location changed significantly
        $lastLogin = LoginHistory::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();
            
        if (!$lastLogin) return false;
        
        $current = Geolocation::lookup($ip);
        
        // Different country than last login
        return $current->getCountryCode() !== $lastLogin->country_code;
    }
}
```

## Simplified Usage (Recommended)

Using the `HasGeolocationSecurity` trait, you can use the simplified methods:

```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    $user = User::where('email', $request->email)->first();
    
    // Check if MFA is required - uses request IP automatically
    if ($user && $user->requiresMfaDueToLocation()) {
        // Store pending login
        Cache::put('pending_login:' . $request->ip(), [
            'user_id' => $user->id,
        ], 5);
        
        return redirect()->route('2fa.challenge');
    }
    
    // Normal authentication...
}
```

## MFA Challenge Flow

```php
public function challenge(Request $request)
{
    $pending = Cache::get('pending_login:' . $request->ip());
    
    if (!$pending) {
        return redirect()->route('login');
    }
    
    // Validate 2FA code
    if (!Hash::check($request->code, Auth::user()->two_factor_code)) {
        return back()->withErrors(['code' => 'Invalid code']);
    }
    
    // Log user in
    Auth::loginUsingId($pending['user_id']);
    
    Cache::forget('pending_login:' . $request->ip());
    
    return redirect()->intended('/');
}
```

## How Risk Scoring Works

The package uses a configurable risk scoring system to determine MFA requirements.

### Risk Score Calculation

The total risk score is calculated by adding points for each triggered condition:

| Condition | Default Score | Config Key |
|-----------|---------------|-----------|
| Proxy/VPN | 40 | `geolocation.security.rules.proxy` |
| Tor exit node | 80 | `geolocation.security.rules.tor` |
| Crawler/Bot | 20 | `geolocation.security.rules.crawler` |
| New country | 30 | `geolocation.security.rules.new_country` |
| New city | 15 | `geolocation.security.rules.new_city` |

### Risk Levels

| Level | Score Range | Trigger |
|-------|-------------|---------|
| `low` | 0-2 | No MFA |
| `high` | 3-69 | Requires MFA if threshold is `high` |
| `critical` | 70+ | Always requires MFA |

### Decision Flow

```
requiresMfaDueToLocation($ip)
    │
    ├─► Is MFA enabled? (geolocation.security.enable_mfa_trigger)
    │       └─► No → return false
    │
    └─► Calculate risk score:
            │
            ├─► Is IP from trusted country? → score = 0
            │
            ├─► isProxy()? → +40 points
            ├─► isTor()? → +80 points
            ├─► isCrawler()? → +20 points
            ├─► isNewCountry()? → +30 points
            ├─► isNewCity()? → +15 points
            │
            └─► Custom rules → +custom points
    │
    └─► Check against threshold:
            │   (geolocation.security.risk_threshold = 'high')
            │
            └─► risk >= threshold? → return true
```

### Example Risk Calculations

```php
$user = User::find(1);

// Login from US (trusted) - no MFA needed
$details = Geolocation::lookup('8.8.8.8'); // Google DNS in US
// Score: 0 (trusted country) → No MFA

// Login using Tor from new country
$details = Geolocation::lookup('1.2.3.4'); // Tor node, NOT in trusted countries
// Score: 80 (Tor) + 30 (new country) = 110 → Always MFA
```

## Configuration

All security settings are in `config/geolocation.php`:

```php
'security' => [
    // Enable/disable MFA triggers
    'enable_mfa_trigger' => env('GEOLOCATION_SECURITY_MFA_ENABLED', true),
    
    // Enable/disable IP blocking
    'enable_blocking' => env('GEOLOCATION_SECURITY_BLOCKING_ENABLED', true),
    
    // Risk level threshold: 'low', 'high' (default), or 'critical'
    'risk_threshold' => env('GEOLOCATION_SECURITY_RISK_THRESHOLD', 'high'),
    
    // Score required to be considered high risk
    'high_risk_threshold' => env('GEOLOCATION_SECURITY_HIGH_RISK_THRESHOLD', 70),
    
    // Risk scoring rules (add points for each trigger)
    'rules' => [
        'proxy' => env('GEOLOCATION_SECURITY_RULE_PROXY', 40),
        'tor' => env('GEOLOCATION_SECURITY_RULE_TOR', 80),
        'crawler' => env('GEOLOCATION_SECURITY_RULE_CRAWLER', 20),
        'new_country' => env('GEOLOCATION_SECURITY_RULE_NEW_COUNTRY', 30),
        'new_city' => env('GEOLOCATION_SECURITY_RULE_NEW_CITY', 15),
    ],
    
    // Countries that bypass security checks (score = 0)
    'trusted_countries' => ['US', 'CA', 'GB'],
    
    // IPs that bypass security checks
    'trusted_ips' => [],
    
    // Custom risk rules (class names)
    'custom_rules' => [],
],
```

### Environment Variables

```env
# Enable MFA triggers
GEOLOCATION_SECURITY_MFA_ENABLED=true

# Set threshold (low, high, critical)
GEOLOCATION_SECURITY_RISK_THRESHOLD=high

# Configure risk scores
GEOLOCATION_SECURITY_RULE_PROXY=40
GEOLOCATION_SECURITY_RULE_TOR=80
GEOLOCATION_SECURITY_RULE_CRAWLER=20
GEOLOCATION_SECURITY_RULE_NEW_COUNTRY=30
GEOLOCATION_SECURITY_RULE_NEW_CITY=15

# Trusted countries (comma-separated)
GEOLOCATION_SECURITY_TRUSTED_COUNTRIES=US,CA,GB
```

## Getting Risk Details

You can get detailed risk information for debugging:

```php
$user = User::find(1);

$score = $user->getRiskScore($request->ip());
/*
Returns:
[
    'score' => 110,
    'is_high_risk' => true,
    'threshold' => 70,
    'triggers' => [
        'tor' => true,
        'new_country' => true
    ],
    'trusted_country' => false
]
*/

// Get risk level
$level = $user->getLastLoginRiskLevel($request->ip());
// Returns: 'low', 'high', or 'critical'
```

## Custom Risk Rules

Add custom risk scoring rules:

```php
// Create a custom rule class
namespace App\Security\Rules;

class CustomRiskRule
{
    public function score($user, $details): int
    {
        $score = 0;
        
        // Example: Check for multiple failed logins
        $failedLogins = $user->failed_logins()
            ->where('created_at', '>', now()->subHours(24))
            ->count();
            
        if ($failedLogins >= 5) {
            $score += 25;
        }
        
        return $score;
    }
}
```

Then register in config:

```php
'security' => [
    'custom_rules' => [
        App\Security\Rules\CustomRiskRule::class,
    ],
],
```
