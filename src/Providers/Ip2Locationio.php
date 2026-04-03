<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Traits\CalculatesTimezoneOffset;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Class Ip2Locationio
 *
 * @author ooi18 <https://github.com/ooi2018>
 * @date 2026-04-03 08:37
 */
 
 Class Ip2Locationio implements LookupInterface
{

    /**
     * @const Define the baseurl.
     */
    const BASEURL = 'https://api.ip2location.io';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Ip2Locationio constructor.
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

        $cacheKey = 'geolocation:ip2locationio:'.md5($ipAddress ?? 'current');
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
     * Fetch geolocation data from Ip2Locationio API.
     *
     * @param string|null $ipAddress
     * @return array
     * @throws GeolocationException
     */
    protected function fetchGeolocationData($ipAddress): array
    {
        $endpoint    = static::BASEURL;

        $params = [
            'format' => 'json'
        ];		
		
        $apiKey = config('geolocation.providers.ip2locationio.api_key');
		
		if ($apiKey) {
			$params['key'] = $apiKey;
		}

        // Add IP address if provided
        if ($ipAddress) {
            $params['ip'] = $ipAddress;
        }

        // Add optional language parameter
        $language = config('geolocation.providers.ip2locationio.language', 'en');
		
		if (! in_array($language, array('ar','cs','da','de','en','es','et','fi','fr','ga','it','ja','ko','ms','nl','pt','ru','sv','tr','vi','zh-cn','zh-tw'))) {
			throw new GeolocationException("Invalid language value. Please refer to the https://www.ip2location.io/ip2location-documentation for the valid language value.");
		}
		
		$params['lang'] = $language;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('geolocation.timeout', 5))
                ->get($endpoint, $params);

            $statusCode = $response->status();

            $data = $response->json();

            if (($statusCode !== 200) && (isset($data['error'])) && (isset($data['error']['error_message']))) {
				$errorMessage = $data['error']['error_message'];
                throw new GeolocationException($errorMessage);
            }

            if ( ! isset($data['ip'])) {
                throw new GeolocationException("Incomplete geolocation data received from IP2Location.io API");
            }
			
			// Transform IP2Location.io result to standard format
            $transformedData = [
                'ip' => $data['ip'],
                'countryCode' => $data['country_code'] ?? null,
                'country' => $data['country_code'] ?? null, // Will be transformed to country name in GeolocationDetails
                'region' => $data['region_name'] ?? null,
                'city' => $data['city_name'] ?? null,
                'latitude' => (float) $data['latitude'] ?? null,
                'longitude' => (float) $data['longitude'] ?? null,
                'timezone' => (isset($data['time_zone_info']) && isset($data['time_zone_info']['olson'])) ? $data['time_zone_info']['olson'] : null,
                'timezoneOffset' => (int) $data['time_zone'] ?? null,
                'org' => $data['asn'] ?? null,
                'asn' => $data['asn'] ?? null,
                'asnName' => $data['as'] ?? null,
                'postalCode' => $data['zip_code'] ?? null,
                'isProxy' => (bool) $data['is_proxy'] ?? null,
                'isp' => isset($data['isp']) ? $data['isp'] : null,
                'hostname' => isset($data['domain']) ? $data['domain'] : null,
                'currency' => (isset($data['country']) && isset($data['country']['currency'])) ? $data['country']['currency']['name'] : null,
                'currencyCode' => (isset($data['country']) && isset($data['country']['currency'])) ? $data['country']['currency']['code'] : null,
                'currencySymbol' => (isset($data['country']) && isset($data['country']['currency'])) ? $data['country']['currency']['symbol'] : null,
                'continent' => (isset($data['continent']) && isset($data['continent']['name'])) ? $data['continent']['name'] : null,
                'continentCode' => (isset($data['continent']) && isset($data['continent']['code'])) ? $data['continent']['code'] : null,
                'isTor' => (isset($data['proxy']) && isset($data['proxy']['is_tor'])) ? (bool) $data['proxy']['is_tor'] : null,
                'isCrawler' => (isset($data['proxy']) && isset($data['proxy']['is_web_crawler'])) ? (bool) $data['proxy']['is_web_crawler'] : null,
				'source' => 'iplio',
            ];
			
			if ((isset($data['mcc'])) && (isset($data['mnc'])) && (isset($data['mobile_brand']))) {
				if ($data['mcc'] === '-' && $data['mnc'] === '-' && $data['mobile_brand'] === '-' ) {
					$transformedData['isMobile'] = false;
				} else if ($data['mcc'] != '-' && $data['mnc'] != '-' && $data['mobile_brand'] != '-' ) {
					$transformedData['isMobile'] = true;
				} else {
					$transformedData['isMobile'] = null;
				}
			} else {
				$transformedData['isMobile'] = null;
			}

			$transformedData['connectionType'] = null;

            return $transformedData;
			

        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new GeolocationException("IP2Location.io API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new GeolocationException("Unexpected error: ".$e->getMessage(), $e->getCode(), $e);
        }
		
		
    }
	
}