# Risk Scoring

Calculate a risk score for user IPs based on multiple factors.

## Concept

A risk score helps identify potentially malicious users before they can cause harm. Combine multiple signals:

- Proxy/VPN/Tor detection
- Geographic anomalies
- ISP reputation
- Connection type
-ASN reputation

## Simple Risk Score

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
