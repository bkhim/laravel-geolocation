<?php

namespace Bkhim\Geolocation;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class Geolocation.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 10:02
 *
 * @package Bkhim
 *
 * @method static GeolocationDetails lookup($ipAddress, $responseFilter = 'geo')
 * @method static LookupInterface driver($name)
 * @method static bool clearCache(?string $ip = null, ?string $provider = null)
 * @method static string getCacheKey(string $ip, ?string $provider = null)
 *
 * @see \Bkhim\Geolocation\Providers\IpInfo
 */
class Geolocation extends Facade
{
    /**
     * Convenient method to get the translation list of countries codes.
     *
     * @param  null $locale
     *
     * @return mixed
     */
    public static function countries($locale = null)
    {
        return trans('geolocation::countries', [], $locale);
    }

    /**
     * Clear geolocation cache.
     *
     * @param  string|null  $ip
     * @param  string|null  $provider
     * @return bool
     */
    public static function clearCache(?string $ip = null, ?string $provider = null): bool
    {
        return app('geolocation')->clearCache($ip, $provider);
    }

    /**
     * Get cache key for an IP address.
     *
     * @param  string  $ip
     * @param  string|null  $provider
     * @return string
     */
    public static function getCacheKey(string $ip, ?string $provider = null): string
    {
        return app('geolocation')->getCacheKey($ip, $provider);
    }

    /**
     * @method static GeolocationDetails lookup($ipAddress, $responseFilter = 'geo')
     * @method static LookupInterface driver($name)
     * @method static bool clearCache(?string $ip = null, ?string $provider = null)
     * @method static string getCacheKey(string $ip, ?string $provider = null)
     */
    protected static function getFacadeAccessor()
    {
        return 'geolocation';
    }
}
