<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class IpInfo.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @maintainer Blancos khim <https://www.briankimathi.com>
 * @date 2019-08-13 13:55
 */
class IpInfo implements LookupInterface
{

    /**
     * @const Define the baseurl.
     */
    const BASEURL = 'https://ipinfo.io';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * IpInfo constructor.
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
        $cacheKey = 'geolocation:ipinfo:'.md5($ipAddress ?? 'current');
        if ( ! is_null($data = $this->cache->get($cacheKey))) {
            return new GeolocationDetails($data);
        }
        $endpoint    = static::BASEURL;
        $accessToken = config('geolocation.providers.ipinfo.access_token');
        if (empty($accessToken)) {
            throw new GeolocationException("IpInfo API key is missing. Set IPINFO_API_KEY in your .env file");
        }
        $filter = 'geo';
        if ($ipAddress) {
            $endpoint .= "/{$ipAddress}/{$filter}";
        }
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json'
            ])->timeout(config('geolocation.timeout', 5))
              ->get($endpoint);
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
            $data = $response->json();
            if ( ! isset($data['ip']) || ! isset($data['country'])) {
                throw new GeolocationException("Incomplete geolocation data received from API");
            }
            if (isset($data['loc'])) {
                $coordinates = explode(',', $data['loc']);
                if (count($coordinates) === 2) {
                    $data['latitude']  = (float) $coordinates[0];
                    $data['longitude'] = (float) $coordinates[1];
                }
            }

            // Map IpInfo response to standard format
            $data['timezone'] = $data['timezone'] ?? null;
            $data['postalCode'] = $data['postal'] ?? null;
            $data['org'] = $data['org'] ?? null;

            // Additional fields from IpInfo
            $data['continent'] = $data['continent'] ?? null;
            $data['continentCode'] = $data['continent'] ?? null;

            // Currency information (if available)
            $data['currency'] = $data['currency'] ?? null;
            $data['currencyCode'] = $data['currency'] ?? null;
            $data['currencySymbol'] = null; // IpInfo doesn't provide symbol directly

            // ISP and network information
            $data['isp'] = $data['org'] ?? null;
            $data['asn'] = null;
            $data['asnName'] = null;
            $data['connectionType'] = null;
            $data['isMobile'] = null;
            $data['isProxy'] = null;
            $data['isCrawler'] = null;
            $data['isTor'] = null;
            $data['hostname'] = $data['hostname'] ?? null;

            // Parse ASN from org field if available (format: "AS15169 Google LLC")
            if (!empty($data['org']) && preg_match('/^AS(\d+)\s+(.+)/', $data['org'], $matches)) {
                $data['asn'] = 'AS' . $matches[1];
                $data['asnName'] = trim($matches[2]);
            }

            // Calculate timezone offset if timezone is available
            $data['timezoneOffset'] = null;
            if (!empty($data['timezone'])) {
                try {
                    $tz = new \DateTimeZone($data['timezone']);
                    $utc = new \DateTimeZone('UTC');
                    $datetime = new \DateTime('now', $tz);
                    $data['timezoneOffset'] = $tz->getOffset($datetime) / 3600; // Convert seconds to hours
                } catch (\Exception $e) {
                    $data['timezoneOffset'] = null;
                }
            }
            $this->cache->put(
                $cacheKey,
                $data,
                config('geolocation.cache.ttl', 86400)
            );
            return new GeolocationDetails($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new GeolocationException("API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
    }

}
