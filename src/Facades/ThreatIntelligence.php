<?php

namespace Bkhim\Geolocation\Facades;

use Bkhim\Geolocation\Services\ThreatIntelligenceService;
use Illuminate\Support\Facades\Facade;

class ThreatIntelligence extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThreatIntelligenceService::class;
    }
}