<?php

namespace Bkhim\Geolocation\Addons\Middleware;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationException;
use Closure;
use Illuminate\Http\Request;

class GeoMiddleware
{

    /**
     * @throws GeolocationException
     */
    public function handle(Request $request, Closure $next, $type = 'allow', $locations = null)
    {
        if ( ! $locations) {
            return $next($request);
        }

        $locations    = explode(',', $locations);
        $userLocation = null;

        try {
            $userLocation = Geolocation::lookup($request->ip());
        } catch (\Throwable $e) {
            // If lookup fails, default to denying when type is 'allow' (safer behaviour)
            if ($type === 'allow') {
                return $this->denyAccess($request);
            }
            return $next($request);
        }

        if ($type === 'allow') {
            // Allow only specific countries/continents
            if ( ! $this->isAllowed($userLocation, $locations)) {
                return $this->denyAccess($request);
            }
        } elseif ($type === 'deny') {
            // Deny specific countries/continents
            if ($this->isDenied($userLocation, $locations)) {
                return $this->denyAccess($request);
            }
        }

        return $next($request);
    }

    protected function isAllowed($location, array $allowedLocations): bool
    {
        if (!$location) {
            return false;
        }

        $country = is_callable([$location, 'getCountryCode']) ? $location->getCountryCode() : ($location->country_code ?? $location->country ?? '');
        $continent = $location->continent_code ?? $location->continent ?? '';

        return in_array($country, $allowedLocations, true) || in_array($continent, $allowedLocations, true);
    }

    protected function denyAccess(Request $request)
    {
        $config = config('geolocation.addons.middleware', []);

        $responseType = $config['response_type'] ?? 'abort';
        $statusCode = $config['status_code'] ?? 403;

        if ($request->expectsJson() || $responseType === 'json') {
            return response()->json([
                'error' => 'Access denied from your location',
                'code'  => 'GEO_BLOCKED'
            ], $statusCode);
        }

        if ($responseType === 'redirect') {
            return redirect($config['redirect_to'] ?? '/');
        }

        abort($statusCode, 'Access denied from your location');
    }

    protected function isDenied($location, array $deniedLocations): bool
    {
        if (!$location) {
            return false;
        }

        $country = is_callable([$location, 'getCountryCode']) ? $location->getCountryCode() : ($location->country_code ?? $location->country ?? '');
        $continent = $location->continent_code ?? $location->continent ?? '';

        return in_array($country, $deniedLocations, true) || in_array($continent, $deniedLocations, true);
    }

}
