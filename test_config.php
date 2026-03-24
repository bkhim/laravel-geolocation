<?php

require 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Bkhim\Geolocation\GeolocationServiceProvider;

$app = new Application;
$app->register(GeolocationServiceProvider::class);
$app->boot();

echo config('geolocation.drivers.default');
