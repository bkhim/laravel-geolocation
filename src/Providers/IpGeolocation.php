<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class IpGeolocation.
 *
 * @author Blancos Khim <https://www.briankimathi.com>
 * @date 2025-12-08
 */
class IpGeolocation implements LookupInterface
{
    /**
     * @const Define the base URL for IPGeolocation API.
     */
    const BASEURL = 'https://api.ipgeolocation.io/ipgeo';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * IpGeolocation constructor.
     *
     * @param  CacheRepository  $cache
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Lookup geolocation data for an IP address.
     *
     * @param  string|null  $ipAddress
     * @param  string  $responseFilter
     * @return GeolocationDetails
     * @throws GeolocationException
     */
    public function lookup($ipAddress = null, $responseFilter = 'geo'): GeolocationDetails
    {
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new GeolocationException("Invalid IP address: {$ipAddress}");
        }

        // Use client IP if none provided
        $ipAddress = $ipAddress ?: request()->ip();

        // Check if caching is enabled before attempting to cache
        if (!config('geolocation.cache.enabled', true)) {
            $data = $this->fetchGeolocationData($ipAddress);
            return new GeolocationDetails($data);
        }

        $cacheKey = 'geolocation:ipgeolocation:'.md5($ipAddress ?? 'current');
        $cacheTtl = config('geolocation.cache.ttl', 86400);

        try {
            $data = $this->cache->remember($cacheKey, $cacheTtl, function () use ($ipAddress) {
                return $this->fetchGeolocationData($ipAddress);
            });

            return new GeolocationDetails($data);
        } catch (GeolocationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fetch geolocation data from IPGeolocation API.
     *
     * @param string|null $ipAddress
     * @return array
     * @throws GeolocationException
     */
    protected function fetchGeolocationData($ipAddress): array
    {

        $apiKey = config('geolocation.providers.ipgeolocation.api_key');

        if (empty($apiKey)) {
            throw new GeolocationException("IPGeolocation API key is missing. Set IPGEOLOCATION_API_KEY in your .env file");
        }

        $endpoint = static::BASEURL;

        $params = [
            'apiKey' => $apiKey,
            'format' => 'json'
        ];

        // Add IP address if provided
        if ($ipAddress) {
            $params['ip'] = $ipAddress;
        }

        // Add optional language parameter
        $language = config('geolocation.providers.ipgeolocation.language', 'en');
        if ($language !== 'en') {
            $params['lang'] = $language;
        }

        // Add optional fields for paid plans
        $includeHostname = config('geolocation.providers.ipgeolocation.include_hostname', false);
        $includeSecurity = config('geolocation.providers.ipgeolocation.include_security', false);
        $includeUserAgent = config('geolocation.providers.ipgeolocation.include_useragent', false);

        $fields = [];
        if ($includeHostname) $fields[] = 'hostname';
        if ($includeSecurity) $fields[] = 'security';
        if ($includeUserAgent) $fields[] = 'user_agent';

        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('geolocation.timeout', 5))
                ->get($endpoint, $params);

            $statusCode = $response->status();

            if ($statusCode !== 200) {
                $errorMessage = match($statusCode) {
                    401 => "Invalid API key - please check your IPGEOLOCATION_API_KEY",
                    403 => "Access forbidden - verify your API key permissions or subscription status",
                    423 => "Request quota exceeded - upgrade your plan",
                    429 => "Rate limit exceeded - too many requests",
                    500 => "IPGeolocation API server error",
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeolocationException($errorMessage);
            }

            $data = $response->json();

            // Check for API errors in the response
            if (isset($data['message']) && str_contains(strtolower($data['message']), 'error')) {
                throw new GeolocationException("IPGeolocation API error: " . $data['message']);
            }

            if ( ! isset($data['ip'])) {
                throw new GeolocationException("Incomplete geolocation data received from IPGeolocation API");
            }

            // Transform IPGeolocation response to our standard format
            // Handle both flat and nested response structures
            $location = $data['location'] ?? $data;

            $transformedData = [
                'ip' => $data['ip'],
                'city' => $location['city'] ?? null,
                'region' => $location['state_prov'] ?? null,
                'country' => $location['country_code2'] ?? null, // Will be transformed to country name in GeolocationDetails
                'countryCode' => $location['country_code2'] ?? null,
                'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
                'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
                'timezone' => $data['time_zone']['name'] ?? null,
                'timezoneOffset' => isset($data['time_zone']['offset']) ? (float) $data['time_zone']['offset'] : null,
                'currency' => $data['currency']['name'] ?? null,
                'currencyCode' => $data['currency']['code'] ?? null,
                'currencySymbol' => $data['currency']['symbol'] ?? null,
                'continent' => $location['continent_name'] ?? null,
                'continentCode' => $location['continent_code'] ?? null,
                'postalCode' => $location['zipcode'] ?? null,
                'org' => $data['isp'] ?? $data['organization'] ?? null,
                'isp' => $data['isp'] ?? null,
                'asn' => $data['asn'] ?? null,
                'asnName' => $data['organization'] ?? null,
                'connectionType' => $data['connection_type'] ?? null,
                'isMobile' => isset($data['device']['is_mobile']) ? (bool) $data['device']['is_mobile'] : null,
                'isProxy' => isset($data['security']['is_proxy']) ? (bool) $data['security']['is_proxy'] : null,
                'isCrawler' => isset($data['security']['is_crawler']) ? (bool) $data['security']['is_crawler'] : null,
                'isTor' => isset($data['security']['is_tor']) ? (bool) $data['security']['is_tor'] : null,
                'hostname' => $data['hostname'] ?? null
            ];

            return $transformedData;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new GeolocationException("IPGeolocation API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }
}
