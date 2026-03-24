<?php

require 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Bkhim\Geolocation\GeolocationServiceProvider;

$app = new Application;
$app->register(GeolocationServiceProvider::class);

echo 'Default driver: ' . config('geolocation.drivers.default') . PHP_EOL;