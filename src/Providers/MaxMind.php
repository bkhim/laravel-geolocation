<?php

namespace Bkhim\Geolocation\Providers;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\GeolocationException;
use Bkhim\Geolocation\Traits\CalculatesTimezoneOffset;
use GeoIp2\Database\Reader;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Class MaxMind.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2024-01-15
 */
class MaxMind implements LookupInterface
{
    use CalculatesTimezoneOffset;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     * */
    protected $cache;

    /**
     * MaxMind constructor.
     *
     * @param  Reader  $reader
     * @param  CacheRepository  $cache
     */
    public function __construct(Reader $reader, CacheRepository $cache)
    {
        $this->reader = $reader;
        $this->cache  = $cache;
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
        // Validate IP address
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new GeolocationException("Invalid IP address: {$ipAddress}");
        }

        // Use client IP if none provided
        $ipAddress = $ipAddress ?: request()->ip();

        // Create cache key
        $cacheKey = 'geolocation:maxmind:'.md5($ipAddress);

        // Check if caching is enabled before attempting to cache
        if (!config('geolocation.cache.enabled', true)) {
            $data = $this->fetchGeolocationData($ipAddress);
            return new GeolocationDetails($data);
        }

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
     * Fetch geolocation data from MaxMind database.
     *
     * @param string $ipAddress
     * @return array
     * @throws GeolocationException
     */
    protected function fetchGeolocationData(string $ipAddress): array
    {

        try {
            // Query MaxMind database
            $record = $this->reader->city($ipAddress);

            // Prepare response data (matching IpInfo format)
            $data = [
                'ip'          => $ipAddress,
                'city'        => $record->city->name ?? 'Unknown',
                'region'      => $record->mostSpecificSubdivision->name ?? 'Unknown',
                'country'     => $record->country->name ?? 'Unknown',
                'countryCode' => $record->country->isoCode ?? 'XX',
                'latitude'    => $record->location->latitude ?? 0,
                'longitude'   => $record->location->longitude ?? 0,
                'timezone'    => $record->location->timeZone ?? null,
                'timezoneOffset' => null,
                'currency' => null, // MaxMind doesn't provide currency data
                'currencyCode' => null,
                'currencySymbol' => null,
                'continent' => $record->continent->name ?? null,
                'continentCode' => $record->continent->code ?? null,
                'postalCode'  => $record->postal->code ?? null,
                'org'         => $record->traits->autonomousSystemOrganization ?? null,
                'isp'         => $record->traits->autonomousSystemOrganization ?? null,
                'asn'         => isset($record->traits->autonomousSystemNumber) ? 'AS' . $record->traits->autonomousSystemNumber : null,
                'asnName'     => $record->traits->autonomousSystemOrganization ?? null,
                'connectionType' => $record->traits->connectionType ?? null,
                'isMobile'    => isset($record->traits->isMobile) ? (bool) $record->traits->isMobile : null,
                'isProxy'     => isset($record->traits->isAnonymousProxy) ? (bool) $record->traits->isAnonymousProxy : null,
                'isCrawler'   => null, // Not available in MaxMind
                'isTor'       => isset($record->traits->isTorExitNode) ? (bool) $record->traits->isTorExitNode : null,
                'hostname'    => null, // Not available in basic MaxMind database
                'loc'         => ($record->location->latitude ?? 0).','.($record->location->longitude ?? 0)
            ];

            // Calculate timezone offset if timezone is available
            $data['timezoneOffset'] = $this->calculateTimezoneOffset($data['timezone']);

            return $data;

        } catch (AddressNotFoundException $e) {
            throw new GeolocationException("IP address not found in database: {$ipAddress}");
        } catch (InvalidDatabaseException $e) {
            throw new GeolocationException(
                "MaxMind database is corrupt or invalid: ".$e->getMessage()
            );
        }
    }

}
