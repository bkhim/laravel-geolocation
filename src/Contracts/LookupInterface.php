<?php

namespace Bkhim\GeoLocation\Contracts;

use Bkhim\GeoLocation\GeoLocationDetails;

/**
 * LookupInterface.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 17:50
 *
 * @package Bkhim\GeoLocation
 */
interface LookupInterface
{
    /**
     * @param  string $ipAddress
     *
     * @param  string $responseFilter
     *
     * @return \Bkhim\GeoLocation\GeoLocationDetails
     */
    public function lookup($ipAddress, $responseFilter = 'geo'): GeoLocationDetails;
}
