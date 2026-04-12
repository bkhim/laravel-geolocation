<?php

namespace Bkhim\Geolocation\Facades;

use Bkhim\Geolocation\Services\AnomalyDetector;
use Illuminate\Support\Facades\Facade;

class GeoAnomalyDetector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnomalyDetector::class;
    }
}