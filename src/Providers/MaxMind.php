<?php

namespace Bkhim\GeoLocation\Providers;

use Bkhim\GeoLocation\Contracts\LookupInterface;
use Bkhim\GeoLocation\GeoLocationDetails;
use Bkhim\GeoLocation\GeoLocationException;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Contracts\Cache\Store;

/**
 * Class MaxMind.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2024-01-15
 */
class MaxMind implements LookupInterface
{
    /**
     * @var \GeoIp2\Database\Reader
     */
    protected $reader;

    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * MaxMind constructor.
     *
     * @param Reader $reader
     * @param Store $cache
     */
    public function __construct(Reader $reader, Store $cache)
    {
        $this->reader = $reader;
        $this->cache = $cache;
    }

    /**
     * Lookup geolocation data for an IP address.
     *
     * @param string|null $ipAddress
     * @param string $responseFilter
     * @return GeoLocationDetails
     * @throws GeoLocationException
     */
    public function lookup($ipAddress = null, $responseFilter = 'geo'): GeoLocationDetails
    {
        // Validate IP address
        if ($ipAddress && !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new GeoLocationException("Invalid IP address: {$ipAddress}");
        }

        // Use client IP if none provided
        $ipAddress = $ipAddress ?: request()->ip();

        // Create cache key
        $cacheKey = 'geolocation:maxmind:' . md5($ipAddress);

        // Check cache first
        if (!is_null($data = $this->cache->get($cacheKey))) {
            return new GeoLocationDetails($data);
        }

        try {
            // Query MaxMind database
            $record = $this->reader->city($ipAddress);

            // Prepare response data (matching IpInfo format)
            $data = [
                'ip' => $ipAddress,
                'city' => $record->city->name ?? 'Unknown',
                'region' => $record->mostSpecificSubdivision->name ?? 'Unknown',
                'country' => $record->country->name ?? 'Unknown',
                'countryCode' => $record->country->isoCode ?? 'XX',
                'latitude' => $record->location->latitude ?? 0,
                'longitude' => $record->location->longitude ?? 0,
                'loc' => ($record->location->latitude ?? 0) . ',' . ($record->location->longitude ?? 0)
            ];

            // Cache the result
            if (config('geolocation.cache.enabled', true)) {
                $this->cache->put(
                    $cacheKey,
                    $data,
                    config('geolocation.cache.ttl', 86400)
                );
            }

            return new GeoLocationDetails($data);

        } catch (AddressNotFoundException $e) {
            throw new GeoLocationException("IP address not found in database: {$ipAddress}");
        } catch (\Exception $e) {
            throw new GeoLocationException("MaxMind database error: " . $e->getMessage());
        }
    }
}
