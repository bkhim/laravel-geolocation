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

### `requiresMfaDueToLocation($ip)`

Automatically determine if MFA is needed:

```php
$user = User::find(1);

if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.challenge');
}
```

This method checks:
- Proxy/VPN detection
- Tor exit nodes
- New country login
- Risk level thresholds (configured in config)

### `isHighRiskLogin($ip)`

Check if a login is high risk:

```php
if ($user->isHighRiskLogin($request->ip())) {
    Log::warning('High risk login detected', [
        'user_id' => $user->id,
        'ip' => $request->ip(),
    ]);
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

## Usage in Controller

```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    $attempts = Cache::get('login_attempts:' . $request->ip(), 0);
    
    $decider = new MfaDecider();
    
    if ($decider->requiresMfa($request, $attempts)) {
        // Store pending login
        Cache::put('pending_login:' . $request->ip(), [
            'email' => $request->email,
            'password' => $request->password,
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

## Configuration

```env
# In .env - adjust thresholds
GEOLOCATION_MFA_PROXY_WEIGHT=30
GEOLOCATION_MFA_TOR_WEIGHT=50
GEOLOCATION_MFA_FAILED_ATTEMPTS=3
GEOLOCATION_MFA_NEW_COUNTRY=true
```
