<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class IpApi.
 *
 * @author Blancos Khim <https://www.briankimathi.com>
 * @date 2025-12-08
 */
class IpApi implements LookupInterface
{
    /**
     * @const Define the base URL for ipapi.co.
     */
    const BASEURL = 'https://ipapi.co';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * IpApi constructor.
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

        $cacheKey = 'geolocation:ipapi:'.md5($ipAddress ?? 'current');

        if ( ! is_null($data = $this->cache->get($cacheKey))) {
            return new GeolocationDetails($data);
        }

        // Build endpoint URL
        $endpoint = static::BASEURL;

        if ($ipAddress) {
            $endpoint .= "/{$ipAddress}";
        }

        $endpoint .= '/json/';

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('geolocation.timeout', 5))
                ->withHeaders([
                    'User-Agent' => 'Laravel-Geolocation-Package/1.0',
                    'Accept' => 'application/json'
                ])
                ->get($endpoint);

            $statusCode = $response->status();

            if ($statusCode !== 200) {
                $errorMessage = match($statusCode) {
                    400 => "Bad Request - Invalid IP address format",
                    403 => "Authentication Failed - Rate limit exceeded",
                    404 => "URL Not Found - Invalid endpoint",
                    405 => "Method Not Allowed",
                    429 => "Quota exceeded - Too many requests",
                    500 => "ipapi.co server error",
                    default => "API returned HTTP error: {$statusCode}"
                };
                throw new GeolocationException($errorMessage);
            }

            $data = $response->json();

            // Check for API errors in the response (HTTP 200 but error in JSON)
            if (isset($data['error']) && $data['error'] === true) {
                $reason = $data['reason'] ?? 'Unknown error';
                throw new GeolocationException("ipapi.co API error: {$reason}");
            }

            if ( ! isset($data['ip'])) {
                throw new GeolocationException("Incomplete geolocation data received from ipapi.co API");
            }

            // Transform ipapi.co response to our standard format
            $transformedData = [
                'ip' => $data['ip'],
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country_code'] ?? $data['country'] ?? null, // Will be transformed to country name in GeolocationDetails
                'countryCode' => $data['country_code'] ?? $data['country'] ?? null,
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'timezone' => $data['timezone'] ?? null,
                'timezoneOffset' => null, // Calculate from UTC offset if available
                'currency' => $data['currency_name'] ?? null,
                'currencyCode' => $data['currency'] ?? null,
                'currencySymbol' => null, // ipapi.co doesn't provide currency symbol
                'continent' => null, // Not provided by ipapi.co in basic response
                'continentCode' => $data['continent_code'] ?? null,
                'postalCode' => $data['postal'] ?? null,
                'org' => $data['org'] ?? null,
                'isp' => $data['org'] ?? null, // Use org as ISP fallback
                'asn' => $data['asn'] ?? null,
                'asnName' => $data['org'] ?? null,
                'connectionType' => null, // Not provided by ipapi.co
                'isMobile' => null, // Not provided by ipapi.co
                'isProxy' => null, // Not provided by ipapi.co
                'isCrawler' => null, // Not provided by ipapi.co
                'isTor' => null, // Not provided by ipapi.co
                'hostname' => null // Not provided by ipapi.co
            ];

            // Calculate timezone offset if utc_offset is available
            if (!empty($data['utc_offset'])) {
                // Convert "+0200" or "-0500" format to hours
                $offset = $data['utc_offset'];
                if (preg_match('/([+-])(\d{2})(\d{2})/', $offset, $matches)) {
                    $sign = $matches[1] === '+' ? 1 : -1;
                    $hours = (int) $matches[2];
                    $minutes = (int) $matches[3];
                    $transformedData['timezoneOffset'] = $sign * ($hours + ($minutes / 60));
                }
            }

            return $transformedData;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new GeolocationException("ipapi.co API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }
}
