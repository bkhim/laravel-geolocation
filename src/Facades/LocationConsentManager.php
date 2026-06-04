<?php

namespace Bkhim\Geolocation\Facades;

use Illuminate\Support\Facades\Facade;

class LocationConsentManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bkhim\Geolocation\Addons\Gdpr\LocationConsentManager::class;
    }
}
