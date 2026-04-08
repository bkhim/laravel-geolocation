# Risk Scoring

Calculate a risk score for user IPs based on multiple factors. The package includes built-in support via the `HasGeolocationSecurity` trait.

## Using the Built-in Trait

Add the trait to your User model:

```php
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;

class User extends Model
{
    use HasGeolocationSecurity;
}
```

## Built-in Methods

### `getRiskScore($ip)`

Get a complete risk score breakdown:

```php
$user = User::find(1);
$risk = $user->getRiskScore('8.8.8.8');

/*
Returns:
[
    'score' => 30,
    'is_high_risk' => false,
    'threshold' => 70,
    'triggers' => ['proxy' => true],
    'trusted_country' => false
]
*/
```

### `isHighRiskLogin($ip)`

Quick check if a login is high risk:

```php
if ($user->isHighRiskLogin($request->ip())) {
    // Flag this login for review
}
```

### `getLastLoginRiskLevel($ip)`

Get risk level as a string:

```php
$level = $user->getLastLoginRiskLevel('8.8.8.8');
// Returns: 'low', 'high', or 'critical'
```

### `requiresMfaDueToLocation($ip)`

Check if MFA should be required:

```php
if ($user->requiresMfaDueToLocation($request->ip())) {
    return redirect()->route('mfa.challenge');
}
```

## Configuration

Configure risk scoring in `config/geolocation.php`:

```php
'security' => [
    'enable_mfa_trigger' => true,
    'risk_threshold' => 'high', // low, high, critical
    
    'high_risk_threshold' => 70, // Score threshold for high risk
    
    'rules' => [
        'proxy' => 40,        // Points for proxy/VPN
        'tor' => 80,          // Points for Tor
        'crawler' => 20,      // Points for crawler/bot
        'new_country' => 30,  // Points for new country
        'new_city' => 15,     // Points for new city
    ],
    
    // Trusted countries bypass security checks
    'trusted_countries' => ['US', 'CA', 'GB'],
],
```

## Manual Risk Score Calculation

If you need custom scoring without the trait:

```php
$details = Geolocation::lookup($request->ip());

$riskScore = 0;

if ($details->isProxy()) $riskScore += 30;
if ($details->isTor()) $riskScore += 50;
if ($details->isCrawler()) $riskScore += 20;

// Evaluate risk
if ($riskScore >= 30) {
    // Require additional verification
}
```

## Advanced Risk Scoring

```php
function calculateRiskScore(GeolocationDetails $details): int 
{
    $score = 0;
    
    // Security indicators (highest weight)
    if ($details->isTor()) $score += 50;
    if ($details->isProxy()) $score += 30;
    if ($details->isCrawler()) $score += 20;
    if ($details->isMobile()) $score -= 10; // Legitimate mobile is safer
    
    // High-risk connection types
    $connectionType = $details->getConnectionType();
    if ($connectionType === 'dialup') $score += 10;
    if ($connectionType === 'satellite') $score += 15;
    
    // Data quality check (missing data can indicate issues)
    if (!$details->getCity()) $score += 5;
    if (!$details->getIsp()) $score += 5;
    
    return min($score, 100); // Cap at 100
}

// Usage
$details = Geolocation::lookup($request->ip());
$score = calculateRiskScore($details);

$actions = match(true) {
    $score >= 50 => ['block', 'Require MFA'],
    $score >= 30 => ['flag', 'Require email verification'],
    $score >= 10 => ['log', 'Log for review'],
    default => ['allow', 'Normal access'],
};
```

## Risk Levels

| Score | Risk Level | Action |
|-------|------------|--------|
| 0-10 | Low | Allow |
| 11-30 | Medium | Log & Monitor |
| 31-50 | High | Require Verification |
| 51+ | Critical | Block or MFA |

## Integration with Auth

```php
// In your LoginController
public function authenticate(Request $request)
{
    $details = Geolocation::lookup($request->ip());
    $riskScore = calculateRiskScore($details);
    
    if ($riskScore >= 30) {
        // Require 2FA
        return redirect()->route('2fa.challenge')->with('risk_score', $riskScore);
    }
    
    // Normal login flow...
}
```
