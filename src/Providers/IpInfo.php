<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class IpInfo.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 13:55
 */
class IpInfo implements LookupInterface
{

    /**
     * @const Define the baseurl.
     */
    const BASEURL = 'https://ipinfo.io';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * IpInfo constructor.
     *
     * @param $client
     * @param  CacheRepository  $cache
     */
    public function __construct(\GuzzleHttp\Client $client, CacheRepository $cache)
    {
        $this->client        = $client;
        $this->cache         = $cache;
    }

    /**
     * Filter the API response down to specific fields or objects
     * by adding the field or object name to the URL.
     *
     * @param  string|null  $ipAddress  An Ip or 'me' For yourself IP
     * @param  string  $responseFilter  Options are: (city / org / geo)
     *
     * @return GeolocationDetails
     * @throws GeolocationException
     */
    public function lookup($ipAddress = null, $responseFilter = 'geo'): GeolocationDetails
    {
        // Validate IP address format before any processing
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new GeolocationException("Invalid IP address: {$ipAddress}");
        }

        // Create secure, namespaced cache key to prevent collisions
        $cacheKey = 'geolocation:ipinfo:'.md5($ipAddress ?? 'current');

        // Check cache first to avoid unnecessary API calls
        if ( ! is_null($data = $this->cache->get($cacheKey))) {
            return new GeolocationDetails($data);
        }

        // Build API endpoint
        $endpoint    = static::BASEURL;
        $accessToken = config('geolocation.providers.ipinfo.access_token');

        // Validate API key presence
        if (empty($accessToken)) {
            throw new GeolocationException("IpInfo API key is missing. Set IPINFO_API_KEY in your .env file");
        }

        // Always use 'geo' filter for consistent response format
        // Other filters may return different data structures that break GeolocationDetails
        $filter = 'geo';
        if ($ipAddress) {
            $endpoint .= "/{$ipAddress}/{$filter}";
        }

        try {
            // Make API request with timeout to prevent hanging
            $response = \Illuminate\Support\Facades\Http::withOptions($this->client->getConfig() ?? [])
                ->timeout(config('geolocation.timeout', 5))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json'
                ])
                ->get($endpoint);

            // Handle HTTP errors
            $statusCode = $response->status();
            if ($statusCode !== 200) {
                $errorMessage = match($statusCode) {
                    401 => "Invalid API key - please check your IPINFO_API_KEY",
                    403 => "Access forbidden - verify your API key permissions",
                    429 => "Rate limit exceeded - too many requests",
                    500 => "IpInfo API server error",
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeolocationException($errorMessage);
            }

            // Get JSON data from response
            $data = $response->json();

            // Strict JSON validation - reject malformed responses
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GeolocationException("Invalid JSON response from IpInfo API");
            }

            // Validate essential response fields exist
            if ( ! isset($data['ip']) || ! isset($data['country'])) {
                throw new GeolocationException("Incomplete geolocation data received from API");
            }

            // Parse coordinates from 'loc' field if present
            if (isset($data['loc'])) {
                $coordinates = explode(',', $data['loc']);
                if (count($coordinates) === 2) {
                    $data['latitude']  = (float) $coordinates[0];
                    $data['longitude'] = (float) $coordinates[1];
                }
            }

            $data['timezone'] = $data['timezone'] ?? null;

            // Cache successful response with TTL from config (default: 24 hours)
            $this->cache->put(
                $cacheKey,
                $data,
                config('geolocation.cache.ttl', 86400)
            );

            return new GeolocationDetails($data);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle HTTP client exceptions
            throw new GeolocationException("API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }

}
