<?php

namespace Bkhim\Geolocation\Addons\Middleware;

use Bkhim\Geolocation\Geolocation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle requests using per-country limits.
 *
 * Limits may be declared in either of these shapes in
 * config('geolocation.addons.rate_limiting.limits'):
 *
 *   'US' => 100
 *   'US' => ['requests_per_minute' => 100]
 *
 * The wildcard key '*' provides the default for countries without an entry.
 */
class GeoRateLimitMiddleware
{
    public function handle(Request $request, Closure $next, ?int $maxAttempts = null, int $decayMinutes = 1): Response
    {
        if (! config('geolocation.addons.rate_limiting.enabled', false)) {
            return $next($request);
        }

        $countryCode = $this->getCountryCode($request);
        $limit = $maxAttempts ?? $this->getLimitForCountry($countryCode);
        $key = $this->resolveRequestKey($request, $countryCode);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too Many Attempts',
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => time() + $seconds,
                'Retry-After' => $seconds,
            ]);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->add([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => max(0, $limit - RateLimiter::attempts($key)),
                'X-RateLimit-Reset' => time() + RateLimiter::availableIn($key),
            ]);
        }

        return $response;
    }

    /**
     * Resolve the country code without letting a provider failure break the
     * request: fall back to 'XX' (unknown) when the lookup throws.
     */
    protected function getCountryCode(Request $request): string
    {
        try {
            $details = Geolocation::lookup($request->ip());

            return $details->getCountryCode() ?? 'XX';
        } catch (\Throwable $e) {
            return 'XX';
        }
    }

    /**
     * Resolve the limit for a country, supporting both the array form
     * (['requests_per_minute' => 100]) and the plain integer form.
     */
    protected function getLimitForCountry(string $countryCode): int
    {
        $limits = config('geolocation.addons.rate_limiting.limits', []);
        $limit = $limits[$countryCode] ?? $limits['*'] ?? 60;

        if (is_array($limit)) {
            $limit = $limit['requests_per_minute']
                ?? $limit['limit']
                ?? $limit['max_attempts']
                ?? 60;
        }

        return max(1, (int) $limit);
    }

    /**
     * Build a stable rate-limit key for the request.
     */
    protected function resolveRequestKey(Request $request, string $countryCode): string
    {
        return sha1(
            'geo-ratelimit|'.$countryCode.'|'.($request->user()?->getAuthIdentifier() ?? $request->ip())
        );
    }
}
