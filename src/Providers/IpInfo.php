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
            $data['timezone'] = $data['timezone'] ?? null;
            $data['postalCode'] = $data['postal'] ?? null;
            $data['org'] = $data['org'] ?? null;
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
