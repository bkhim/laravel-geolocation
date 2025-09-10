<?php

namespace Bkhim\Geolocation;

use Bkhim\Geolocation\Contracts\LookupInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class GeoLocation.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 10:02
 *
 * @package Bkhim
 *
 * @method static|GeolocationDetails lookup($ipAddress, $responseFilter = 'geo')
 * @method static|LookupInterface driver($name)
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
        return static::$app['translator']->get('geolocation::countries', [], $locale);
    }

    /**
     * @method static GeolocationDetails lookup($ipAddress, $responseFilter = 'geo')
     * @method static LookupInterface driver($name)
     */
    protected static function getFacadeAccessor()
    {
        return 'geolocation';
    }
}
