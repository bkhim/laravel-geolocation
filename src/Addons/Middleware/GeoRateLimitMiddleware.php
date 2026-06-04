<?php

namespace Bkhim\Geolocation\Addons\Middleware;

use Bkhim\Geolocation\Geolocation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class GeoRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('geolocation.addons.rate_limiting.enabled', false)) {
            return $next($request);
        }

        $countryCode = $this->getCountryCode($request);
        $key = "ratelimit:{$countryCode}:" . ($request->user()?->id ?? $request->ip());
        
        $limit = $this->getLimitForCountry($countryCode);
        
        if (!RateLimiter::tooManyAttempts($key, $limit)) {
            RateLimiter::hit($key, 60);
            return $next($request);
        }

        $seconds = RateLimiter::availableIn($key);
        
        return response()->json([
            'error' => 'Too Many Attempts',
            'retry_after' => $seconds,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $seconds,
        ]);
    }

    protected function getCountryCode(Request $request): string
    {
        $details = Geolocation::lookup($request->ip());
        return $details->getCountryCode() ?? 'XX';
    }

    protected function getLimitForCountry(string $countryCode): int
    {
        $limits = config('geolocation.addons.rate_limiting.limits', []);
        
        return $limits[$countryCode] ?? $limits['*'] ?? 60;
    }
}