<?php

namespace Adrianorosa\GeoLocation\Providers;

use Illuminate\Contracts\Cache\Store;
use GuzzleHttp\Exception\GuzzleException;
use Adrianorosa\GeoLocation\GeoLocationDetails;
use Adrianorosa\GeoLocation\GeoLocationException;
use Adrianorosa\GeoLocation\Contracts\LookupInterface;

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
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * IpInfo constructor.
     *
     * @param $client
     * @param Store $cache
     */
    public function __construct($client, Store $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * Filter the API response down to specific fields or objects
     * by adding the field or object name to the URL.
     *
     * @param  string|null $ipAddress  An Ip or 'me' For yourself IP
     * @param  string $responseFilter Options are: (city / org / geo)
     *
     * @return GeoLocationDetails
     * @throws GeoLocationException
     */
    public function lookup($ipAddress = null, $responseFilter = 'geo'): GeoLocationDetails
    {
        // Validate IP address format before any processing
        if ($ipAddress && !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new GeoLocationException("Invalid IP address: {$ipAddress}");
        }

        // Create secure, namespaced cache key to prevent collisions
        $cacheKey = 'geolocation:ipinfo:' . md5($ipAddress ?? 'current');

        // Check cache first to avoid unnecessary API calls
        if (!is_null($data = $this->cache->get($cacheKey))) {
            return new GeoLocationDetails($data);
        }

        // Build API endpoint
        $endpoint = static::BASEURL;
        $accessToken = config('geolocation.providers.ipinfo.access_token');

        // Validate API key presence
        if (empty($accessToken)) {
            throw new GeoLocationException("IpInfo API key is missing. Set IPINFO_API_KEY in your .env file");
        }

        // Always use 'geo' filter for consistent response format
        // Other filters may return different data structures that break GeoLocationDetails
        $filter = 'geo';
        if ($ipAddress) {
            $endpoint .= "/{$ipAddress}/{$filter}";
        }

        try {
            // Make API request with timeout to prevent hanging
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json'
                ],
                'timeout' => config('geolocation.timeout', 5),
            ]);

            // Handle HTTP errors and rate limits
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorMessage = match($statusCode) {
                    401 => "Invalid API key - please check your IPINFO_API_KEY",
                    403 => "Access forbidden - verify your API key permissions",
                    429 => "Rate limit exceeded - too many requests",
                    500 => "IpInfo API server error",
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeoLocationException($errorMessage);
            }

            // Parse and validate JSON response
            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            // Strict JSON validation - reject malformed responses
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GeoLocationException("Invalid JSON response from IpInfo API");
            }

            // Validate essential response fields exist
            if (!isset($data['ip']) || !isset($data['country'])) {
                throw new GeoLocationException("Incomplete geolocation data received from API");
            }

            // Parse coordinates from 'loc' field if present
            if (isset($data['loc'])) {
                $coordinates = explode(',', $data['loc']);
                if (count($coordinates) === 2) {
                    $data['latitude'] = (float) $coordinates[0];
                    $data['longitude'] = (float) $coordinates[1];
                }
            }

            // Cache successful response with TTL from config (default: 24 hours)
            $this->cache->put(
                $cacheKey,
                $data,
                config('geolocation.cache.ttl', 86400)
            );

            return new GeoLocationDetails($data);

        } catch (GuzzleException $e) {
            // Handle network errors with specific messaging
            $errorCode = $e->getCode();
            $errorMessage = match(true) {
                str_contains($e->getMessage(), 'cURL error 28') => "Connection timeout - please try again",
                $errorCode === 0 => "Network error: " . $e->getMessage(),
                default => "API request failed: " . $e->getMessage()
            };

            throw new GeoLocationException($errorMessage, $errorCode, $e);
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            throw new GeoLocationException("Unexpected error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
