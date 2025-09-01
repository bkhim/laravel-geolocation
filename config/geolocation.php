<?php

return [
    'timeout' => env('GEOLOCATION_TIMEOUT', 5), // Request timeout in seconds

    'retry' => [
        'attempts' => env('GEOLOCATION_RETRY_ATTEMPTS', 2),
        'delay' => env('GEOLOCATION_RETRY_DELAY', 100), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Default IP Lookup Driver
    |--------------------------------------------------------------------------
    |
    | Here we use as a default driver the IpInfo Service, in the future we may
    | implement the GeoIp by MaxMind as well, for now we just support IpInfo.
    |
    */

    'drivers' => [
        'default' => 'ipinfo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurations for each Provider
    |--------------------------------------------------------------------------
    |
    */

    'providers' => [

        'ipinfo' => [
            'driver' => 'ipinfo',
            'access_token' => env('GEOLOCATION_IPINFO_ACCESS_TOKEN', null),
            // These options are passed to GuzzleClient config constructor
            'client_options' => [
                //
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache config when using API request like IpInfo and MaxMind WebService
    |--------------------------------------------------------------------------
    |
    */

    'cache' => [
        'ttl' => env('GEOLOCATION_CACHE_TTL', 86400),
    ],

];
