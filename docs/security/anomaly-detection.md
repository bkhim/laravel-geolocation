# Anomaly Detection

Detect unusual location patterns that may indicate fraudulent activity.

## Location History Check

Compare current location with historical login locations:

```php
class LocationAnomalyDetector
{
    public function isAnomalous(string $ip, int $userId): bool
    {
        // Get current location
        $current = Geolocation::lookup($ip);
        
        // Get recent login history
        $recentLogins = LoginHistory::where('user_id', $userId)
            ->where('created_at', '>', now()->subDays(30))
            ->get();
        
        if ($recentLogins->isEmpty()) {
            return false; // First login, can't detect anomaly
        }
        
        // Check for unusual country
        $countries = $recentLogins->pluck('country_code')->unique();
        if ($countries->count() > 3) {
            return true; // Too many different countries
        }
        
        // Check for impossible travel
        foreach ($recentLogins as $login) {
            if ($this->impossibleTravel($login, $current)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function impossibleTravel(LoginHistory $past, GeolocationDetails $current): bool
    {
        // Simple distance check - if > 1000km in < 1 hour, flag it
        $distance = $this->calculateDistance(
            $past->latitude, $past->longitude,
            $current->getLatitude(), $current->getLongitude()
        );
        
        $hours = $past->created_at->diffInHours($current->getCurrentTime() ?? now());
        
        return $distance > 1000 && $hours < 1;
    }
    
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Haversine formula
        $R = 6371; // Earth's radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
```

## Usage in Login Flow

```php
public function handleLogin(Request $request)
{
    $details = Geolocation::lookup($request->ip());
    
    $detector = new LocationAnomalyDetector();
    if ($detector->isAnomalous($request->ip(), Auth::id())) {
        // Log for investigation
        event(new SuspiciousLocationDetected(
            Auth::user(), 
            $details
        ));
        
        // Require additional verification
        return redirect()->route('verify.identity');
    }
}
```

## Detection Patterns

| Pattern | Description | Action |
|---------|-------------|--------|
| Impossible Travel | Login from distant locations in short time | Flag for review |
| New Country | First login from new country | Require verification |
| Multiple Countries | Logins from 3+ countries in 30 days | Flag for review |
| High-Risk Location | Login from known high-risk country | Require MFA |
