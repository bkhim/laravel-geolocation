<?php

namespace Bkhim\Geolocation\Addons\Middleware;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationException;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RateLimitByGeo
{
    protected RateLimiter $limiter;

    /**
     * The headers that should be present on a response.
     *
     * @var array
     */
    protected $headers = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'Retry-After',
    ];

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return mixed
     * @throws GeolocationException
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $location    = null;
        try {
            $location = Geolocation::lookup($request->ip());
        } catch (\Throwable $e) {
            // If lookup fails, continue with unknown country default
        }

        $countryCode = $location->country_code ?? $location->country ?? 'unknown';

        // Get specific limit for country or use default
        $limits       = config('geolocation.addons.rate_limiting.limits', []);
        $countryLimit = $limits[$countryCode] ?? $limits['*'] ?? ['requests_per_minute' => $maxAttempts];

        // Handle both array and integer configurations
        if (is_array($countryLimit) && isset($countryLimit['requests_per_minute'])) {
            $maxAttempts = $countryLimit['requests_per_minute'];
        } else {
            $maxAttempts = (int) $countryLimit;
        }

        $key = $this->resolveRequestSignature($request, $countryCode);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts, $decayMinutes);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->calculateRetryAfter($key, $decayMinutes)
        );
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * @param Request $request
     * @param string $countryCode
     * @return string
     */
    protected function resolveRequestSignature($request, $countryCode): string
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME') .
            '|' . $request->path() .
            '|' . $countryCode .
            '|' . $request->ip()
        );
    }

    /**
     * Build a rate-limited response.
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return SymfonyResponse
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): SymfonyResponse
    {
        $retryAfter = $this->calculateRetryAfter($key, $decayMinutes);
        $response = $this->createRateLimitedResponse($retryAfter);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $retryAfter
        );
    }

    /**
     * Create the rate-limited response.
     *
     * @param int $retryAfter
     * @return SymfonyResponse
     */
    protected function createRateLimitedResponse(int $retryAfter): SymfonyResponse
    {
        $message = config('geolocation.addons.rate_limiting.message', 'Too Many Attempts.');

        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Return a minimal 429 response; Laravel will handle rendering for HTML responses
        // (this avoids referencing a view that may not exist in user applications).
        return response('', 429);
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }

    /**
     * Calculate the retry-after time in seconds.
     *
     * @param string $key
     * @param int $decayMinutes
     * @return int
     */
    protected function calculateRetryAfter(string $key, int $decayMinutes): int
    {
        $availableAt = $this->limiter->availableIn($key);

        // If the limiter doesn't return availableIn, calculate it
        if ($availableAt === null || $availableAt === false || $availableAt === 0) {
            $availableAt = $decayMinutes * 60;
        }

        return (int) $availableAt;
    }

    /**
     * Add rate limiting headers to the response.
     *
     * @param SymfonyResponse $response
     * @param int $maxAttempts
     * @param int $remainingAttempts
     * @param int $retryAfter
     * @return SymfonyResponse
     */
    protected function addHeaders(
        SymfonyResponse $response,
        int $maxAttempts,
        int $remainingAttempts,
        int $retryAfter
    ): SymfonyResponse {
        // Remove any stale rate limiting headers first
        $this->removeStaleHeaders($response);

        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ];

        // Only add Retry-After header when rate limited (429)
        if ($response->getStatusCode() === 429) {
            $headers['Retry-After'] = $retryAfter;
        }

        $response->headers->add($headers);


        return $response;
    }

    /**
     * Remove any stale rate limiting headers from the response.
     *
     * @param SymfonyResponse $response
     * @return void
     */
    protected function removeStaleHeaders(SymfonyResponse $response): void
    {
        foreach ($this->headers as $header) {
            $response->headers->remove($header);
        }
    }

    /**
     * Get the rate limiter instance.
     *
     * @return RateLimiter
     */
    public function getLimiter(): RateLimiter
    {
        return $this->limiter;
    }
}
