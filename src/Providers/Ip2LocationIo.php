<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Class Ip2LocationIo
 *
 * @author ooi18 <https://github.com/ooi2018>
 *
 * @date 2026-04-03 08:37
 */
class Ip2LocationIo implements LookupInterface
{
    /**
     * @const Define the baseurl.
     */
    const BASEURL = 'https://api.ip2location.io';

    /**
     * @var CacheRepository
     */
    protected $cache;

    /**
     * Ip2LocationIo constructor.
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
     *
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
        if (! config('geolocation.cache.enabled', true)) {
            $data = $this->fetchGeolocationData($ipAddress);

            return new GeolocationDetails($data);
        }

        $cacheKey = 'geolocation:ip2locationio:'.md5($ipAddress ?? 'current');
        $cacheTtl = config('geolocation.cache.ttl', 86400);

        try {
            $data = $this->cache->remember($cacheKey, $cacheTtl, function () use ($ipAddress) {
                return $this->fetchGeolocationData($ipAddress);
            });

            return new GeolocationDetails($data);
        } catch (GeolocationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new GeolocationException('Unexpected error: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fetch geolocation data from IP2Location.io API.
     *
     * @param  string|null  $ipAddress
     * @param  bool  $skipLanguage  Skip language parameter (used for retry)
     * @return array
     * @throws GeolocationException
     */
    protected function fetchGeolocationData($ipAddress, $skipLanguage = false): array
    {
        $endpoint = static::BASEURL;

        $params = [
            'format' => 'json',
        ];

        $apiKey = config('geolocation.providers.ip2locationio.api_key');

        if ($apiKey) {
            $params['key'] = $apiKey;
        }

        // Add IP address if provided
        if ($ipAddress) {
            $params['ip'] = $ipAddress;
        }

        // Add optional language parameter (only for paid plans with API key)
        $language = config('geolocation.providers.ip2locationio.language', 'en');

        // Validate language if specified
        if ($language && ! in_array($language, [
            'ar', 'cs', 'da', 'de', 'en', 'es', 'et', 'fi', 'fr', 'ga', 'it', 'ja', 'ko', 'ms', 'nl', 'pt', 'ru', 'sv', 'tr', 'vi', 'zh-cn', 'zh-tw',
        ])) {
            throw new GeolocationException('Invalid language value. Please refer to the https://www.ip2location.io/ip2location-documentation for the valid language value.');
        }

        // Only include lang parameter if:
        // 1. Not skipping language (due to previous error)
        // 2. API key is present (paid plans support translation)
        // 3. Language is not 'en' (default, no translation needed)
        // 4. Language is explicitly configured and not empty
        if (!$skipLanguage && $apiKey && $language && $language !== 'en') {
            $params['lang'] = $language;
        }

        try {
            $response = Http::timeout(config('geolocation.timeout', 5))
                ->get($endpoint, $params);

            $statusCode = $response->status();

            if ($statusCode !== 200) {
                $errorMessage = match ($statusCode) {
                    400 => 'Bad Request - Invalid IP address format or parameters',
                    401 => 'Authentication Failed - Invalid API key',
                    403 => 'Forbidden - API key does not have sufficient permissions',
                    404 => 'Not Found - Invalid endpoint or resource not found',
                    429 => 'Rate limit exceeded - Too many requests',
                    500 => 'IP2Location.io server error - Please try again later',
                    502 => 'Bad Gateway - IP2Location.io service temporarily unavailable',
                    503 => 'Service Unavailable - IP2Location.io service is down',
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeolocationException($errorMessage);
            }

            $data = $response->json();

            // Check for API errors in the response (HTTP 200 but error in JSON)
            if (isset($data['error']) && isset($data['error']['error_message'])) {
                $errorMessage = $data['error']['error_message'];
                $errorCode = $data['error']['error_code'] ?? null;

                // Handle translation not available error (10004) - retry without lang parameter
                if (!$skipLanguage && ($errorCode === 10004 || strpos($errorMessage, 'Translation is not available') !== false)) {
                    // Retry without language parameter
                    return $this->fetchGeolocationData($ipAddress, true);
                }

                throw new GeolocationException("IP2Location.io API error: {$errorMessage}");
            }

            if (! isset($data['ip'])) {
                throw new GeolocationException('Incomplete geolocation data received from IP2Location.io API');
            }

            // Transform IP2Location.io result to standard format
            $transformedData = [
                'ip' => $data['ip'],
                'countryCode' => $data['country_code'] ?? null,
                'country' => $data['country_name'] ?? $data['country_code'] ?? null,
                'region' => $data['region_name'] ?? null,
                'city' => $data['city_name'] ?? null,
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'timezone' => (isset($data['time_zone_info']['olson']))
                                   ? $data['time_zone_info']['olson']
                                   : null,
                'timezoneOffset' => $this->calculateTimezoneOffset($data),
                'org' => $data['asn'] ?? null,
                'asn' => isset($data['asn']) ? 'AS'.$data['asn'] : null,
                'asnName' => $data['as'] ?? null,
                'postalCode' => (isset($data['zip_code']) && $data['zip_code'] !== '-') ? $data['zip_code'] : null,
                'isProxy' => isset($data['is_proxy']) ? (bool) $data['is_proxy'] : null,
                'isp' => $data['isp'] ?? null,
                'hostname' => $data['domain'] ?? null,
                'currency' => $data['country']['currency']['name'] ?? null,
                'currencyCode' => $data['country']['currency']['code'] ?? null,
                'currencySymbol' => $data['country']['currency']['symbol'] ?? null,
                'continent' => $data['continent']['name'] ?? null,
                'continentCode' => $data['continent']['code'] ?? null,
                'connectionType' => $this->mapConnectionType($data['net_speed'] ?? null),
                'isTor' => (bool) ($data['proxy']['is_tor'] ?? false),
                'isCrawler' => (bool) ($data['proxy']['is_web_crawler'] ?? false),
            ];

            if ((isset($data['mcc'])) && (isset($data['mnc'])) && (isset($data['mobile_brand']))) {
                if ($data['mcc'] === '-' && $data['mnc'] === '-' && $data['mobile_brand'] === '-') {
                    $transformedData['isMobile'] = false;
                } else {
                    if ($data['mcc'] != '-' && $data['mnc'] != '-' && $data['mobile_brand'] != '-') {
                        $transformedData['isMobile'] = true;
                    } else {
                        $transformedData['isMobile'] = null;
                    }
                }
            } else {
                $transformedData['isMobile'] = null;
            }

            return $transformedData;

        } catch (RequestException $e) {
            throw new GeolocationException('IP2Location.io API request failed: '.$e->getMessage());
        } catch (Exception $e) {
            throw new GeolocationException('Unexpected error: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Calculate timezone offset from IP2Location.io response data.
     *
     * @param  array  $data
     */
    protected function calculateTimezoneOffset($data): ?float
    {
        // First try gmt_offset from time_zone_info (more accurate, in seconds)
        if (isset($data['time_zone_info']['gmt_offset'])) {
            return $data['time_zone_info']['gmt_offset'] / 3600; // Convert seconds to hours
        }

        // Fallback to time_zone field (format: "+08:00" or "-05:00")
        if (isset($data['time_zone']) && is_string($data['time_zone'])) {
            if (preg_match('/([+-])(\d{2}):(\d{2})/', $data['time_zone'], $matches)) {
                $sign = $matches[1] === '+' ? 1 : -1;
                $hours = (int) $matches[2];
                $minutes = (int) $matches[3];

                return $sign * ($hours + ($minutes / 60));
            }
        }

        return null;
    }

    /**
     * Map IP2Location.io net_speed values to connection types.
     */
    protected function mapConnectionType(?string $netSpeed): ?string
    {
        if (! $netSpeed) {
            return null;
        }

        return match (strtoupper($netSpeed)) {
            'DIAL' => 'dialup',
            'DSL' => 'broadband',
            'COMP' => 'corporate',
            'T1' => 'datacenter',
            'SAT' => 'satellite',
            default => strtolower($netSpeed)
        };
    }
}
