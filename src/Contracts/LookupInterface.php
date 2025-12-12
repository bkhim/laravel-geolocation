<?php

namespace Bkhim\Geolocation\Contracts;

use Bkhim\Geolocation\GeolocationDetails;

/**
 * LookupInterface.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 17:50
 *
 * @package Bkhim\Geolocation
 */
interface LookupInterface
{
    /**
     * @param  string $ipAddress
     *
     * @param  string $responseFilter
     *
     * @return \Bkhim\Geolocation\GeolocationDetails
     */
    public function lookup($ipAddress, $responseFilter = 'geo'): GeolocationDetails;
}
