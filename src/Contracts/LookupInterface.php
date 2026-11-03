<?php

namespace Bkhim\Geolocation\Contracts;

use Bkhim\Geolocation\GeolocationDetails;

/**
 * LookupInterface.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 *
 * @date 2019-08-13 17:50
 */
interface LookupInterface
{
    public function lookup(?string $ipAddress = null, string $responseFilter = 'geo'): GeolocationDetails;
}
