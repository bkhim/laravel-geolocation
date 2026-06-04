<?php

namespace Bkhim\Geolocation\Facades;

use Illuminate\Support\Facades\Facade;

class IpAnonymizer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bkhim\Geolocation\Addons\Anonymization\IpAnonymizer::class;
    }
}
