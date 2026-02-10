<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class IpStack.
 *
 * @author Blancos Khim <https://www.briankimathi.com>
 * @date 2025-12-08
 */
class IpStack implements LookupInterface
{
    /**
     * @const Define the base URL for IPStack API.
     */
    const BASEURL = 'http://api.ipstack.com';
    const BASEURL_SECURE = 'https://api.ipstack.com';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * IpStack constructor.
     *
     * @param  CacheRepository  $cache
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the appropriate base URL based on configuration.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        $secure = config('geolocation.providers.ipstack.secure', true);
        return $secure ? self::BASEURL_SECURE : self::BASEURL;
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

        $cacheKey = 'geolocation:ipstack:'.md5($ipAddress);
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
     * Fetch geolocation data from IPStack API.
     *
     * @param string $ipAddress
     * @return array
     * @throws GeolocationException
     */
    protected function fetchGeolocationData(string $ipAddress): array
    {
        $accessKey = config('geolocation.providers.ipstack.access_key');

        if (empty($accessKey)) {
            throw new GeolocationException("IPStack API key is missing. Set IPSTACK_ACCESS_KEY in your .env file");
        }

        $endpoint = $this->getBaseUrl() . "/{$ipAddress}";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('geolocation.timeout', 5))
                ->get($endpoint, [
                    'access_key' => $accessKey,
                    'format' => 'json'
                ]);

            $statusCode = $response->status();

            if ($statusCode !== 200) {
                $errorMessage = match($statusCode) {
                    401 => "Invalid API key - please check your IPSTACK_ACCESS_KEY",
                    403 => "Access forbidden - verify your API key permissions",
                    429 => "Rate limit exceeded - too many requests",
                    500 => "IPStack API server error",
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeolocationException($errorMessage);
            }

            $data = $response->json();

            // Check for API errors in the response
            if (isset($data['error'])) {
                $errorCode = $data['error']['code'] ?? 'unknown';
                $errorType = $data['error']['type'] ?? 'unknown_error';
                $errorInfo = $data['error']['info'] ?? 'Unknown error occurred';

                throw new GeolocationException("IPStack API error [{$errorCode}] {$errorType}: {$errorInfo}");
            }

            if ( ! isset($data['ip']) || ! isset($data['country_code'])) {
                throw new GeolocationException("Incomplete geolocation data received from IPStack API");
            }

            // Transform IPStack response to our standard format
            $transformedData = [
                'ip' => $data['ip'],
                'city' => $data['city'] ?? null,
                'region' => $data['region_name'] ?? null,
                'country' => $data['country_code'] ?? null,
                'countryCode' => $data['country_code'] ?? null,
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'timezone' => $data['time_zone']['id'] ?? null,
                'timezoneOffset' => isset($data['time_zone']['gmt_offset']) ? ($data['time_zone']['gmt_offset'] / 3600) : null,
                'currency' => $data['currency']['name'] ?? null,
                'currencyCode' => $data['currency']['code'] ?? null,
                'currencySymbol' => $data['currency']['symbol'] ?? null,
                'continent' => $data['continent_name'] ?? null,
                'continentCode' => $data['continent_code'] ?? null,
                'postalCode' => $data['zip'] ?? null,
                'org' => null,
                'isp' => $data['connection']['isp'] ?? null,
                'asn' => isset($data['connection']['asn']) ? 'AS' . $data['connection']['asn'] : null,
                'asnName' => $data['connection']['asn_org'] ?? null,
                'connectionType' => $data['connection_type'] ?? null,
                'isMobile' => null,
                'isProxy' => isset($data['security']['is_proxy']) ? (bool) $data['security']['is_proxy'] : null,
                'isCrawler' => isset($data['security']['is_crawler']) ? (bool) $data['security']['is_crawler'] : null,
                'isTor' => isset($data['security']['is_tor']) ? (bool) $data['security']['is_tor'] : null,
                'hostname' => null
            ];

            return $transformedData;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new GeolocationException("IPStack API request failed: " . $e->getMessage());
        } catch (GeolocationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }
}
