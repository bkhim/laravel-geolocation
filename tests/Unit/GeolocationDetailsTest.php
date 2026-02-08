<?php

use Bkhim\Geolocation\GeolocationDetails;

it('can instantiate with array data', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'region' => 'California',
        'country' => 'US',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($data);

    expect($details->getIp())->toBe('8.8.8.8')
        ->and($details->getCity())->toBe('Mountain View')
        ->and($details->getCountryCode())->toBe('US');
});

it('can instantiate with json string', function () {
    $json = json_encode([
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
    ]);

    $details = new GeolocationDetails($json);

    expect($details->getIp())->toBe('8.8.8.8');
    expect($details->getCity())->toBe('Mountain View');
});

it('can instantiate with another GeolocationDetails instance', function () {
    $original = new GeolocationDetails([
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
    ]);

    $details = new GeolocationDetails($original);

    expect($details->getIp())->toBe('8.8.8.8')
        ->and($details->getCity())->toBe('Mountain View');
});

it('returns all getters properly', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'region' => 'California',
        'country' => 'United States',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
        'timezone' => 'America/Los_Angeles',
        'timezoneOffset' => -8,
        'currency' => 'US Dollar',
        'currencyCode' => 'USD',
        'currencySymbol' => '$',
        'continent' => 'North America',
        'continentCode' => 'NA',
        'postalCode' => '94043',
        'org' => 'Google LLC',
        'isp' => 'Google LLC',
        'asn' => 'AS15169',
        'asnName' => 'Google',
        'connectionType' => 'corporate',
        'isMobile' => false,
        'isProxy' => false,
        'isCrawler' => false,
        'isTor' => false,
        'hostname' => 'dns.google',
    ];

    $details = new GeolocationDetails($data);

    expect($details->getIp())->toBe('8.8.8.8')
        ->and($details->getCity())->toBe('Mountain View')
        ->and($details->getCountryCode())->toBe('US')
        ->and($details->getLatitude())->toBe(37.386)
        ->and($details->getLongitude())->toBe(-122.084)
        ->and($details->getTimezone())->toBe('America/Los_Angeles')
        ->and($details->getTimezoneOffset())->toBe(-8)
        ->and($details->getCurrency())->toBe('US Dollar')
        ->and($details->getCurrencyCode())->toBe('USD')
        ->and($details->getCurrencySymbol())->toBe('$')
        ->and($details->getContinent())->toBe('North America')
        ->and($details->getContinentCode())->toBe('NA')
        ->and($details->getPostalCode())->toBe('94043')
        ->and($details->getOrg())->toBe('Google LLC')
        ->and($details->getIsp())->toBe('Google LLC')
        ->and($details->getAsn())->toBe('AS15169')
        ->and($details->getAsnName())->toBe('Google')
        ->and($details->getConnectionType())->toBe('corporate')
        ->and($details->isMobile())->toBe(false)
        ->and($details->isProxy())->toBe(false)
        ->and($details->isCrawler())->toBe(false)
        ->and($details->isTor())->toBe(false)
        ->and($details->getHostname())->toBe('dns.google');
});

it('generates correct formatted addresses', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'region' => 'California',
        'country' => 'United States',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
        'postalCode' => '94043',
    ];

    $details = new GeolocationDetails($data);

    expect($details->getFormattedAddress())->toBe('Mountain View, California, United States')
        ->and($details->getShortAddress())->toBe('Mountain View, US')
        ->and($details->getFullAddress())->toBe('Mountain View, California, 94043, US');
});

it('generates correct map links', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'latitude' => 37.386,
        'longitude' => -122.084,
        'countryCode' => 'US',
    ];

    $details = new GeolocationDetails($data);

    expect($details->getGoogleMapsLink())
        ->toBe('https://maps.google.com/?q=37.386,-122.084')
        ->and($details->getOpenStreetMapLink())
        ->toContain('openstreetmap.org')
        ->and($details->getAppleMapsLink())
        ->toContain('maps://maps.apple.com');

});

it('generates country flag emoji', function () {
    $data = [
        'ip' => '8.8.8.8',
        'countryCode' => 'US',
    ];

    $details = new GeolocationDetails($data);

    expect($details->getCountryFlag())->toBe('🇺🇸')
        ->and($details->getCountryFlagEmoji())->toBe('🇺🇸');
});

it('generates country flag url', function () {
    $data = [
        'ip' => '8.8.8.8',
        'countryCode' => 'US',
    ];

    $details = new GeolocationDetails($data);

    expect($details->getCountryFlagUrl())
        ->toBe('https://flagcdn.com/w320/us.png')
        ->and($details->getCountryFlagUrl(64))
        ->toBe('https://flagcdn.com/w64/us.png');

});

it('validates ipv4', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($data);

    expect($details->isIPv4())->toBe(true)
        ->and($details->isIPv6())->toBe(false);
});

it('validates ipv6', function () {
    $data = [
        'ip' => '2001:4860:4860::8888',
        'city' => 'Mountain View',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($data);

    expect($details->isIPv6())->toBe(true);
    expect($details->isIPv4())->toBe(false);
});

it('checks if data is valid', function () {
    $validData = [
        'ip' => '8.8.8.8',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($validData);
    expect($details->isValid())->toBe(true);

    $invalidData = [
        'ip' => '8.8.8.8',
        // Missing countryCode, latitude, longitude
    ];

    $details = new GeolocationDetails($invalidData);
    expect($details->isValid())->toBe(false);
});

it('has timezone information check', function () {
    $dataWithTimezone = [
        'ip' => '8.8.8.8',
        'timezone' => 'America/Los_Angeles',
    ];

    $details = new GeolocationDetails($dataWithTimezone);
    expect($details->hasTimezone())->toBe(true);

    $dataWithoutTimezone = [
        'ip' => '8.8.8.8',
    ];

    $details = new GeolocationDetails($dataWithoutTimezone);
    expect($details->hasTimezone())->toBe(false);
});

it('gets current time in timezone', function () {
    $data = [
        'ip' => '8.8.8.8',
        'timezone' => 'America/Los_Angeles',
    ];

    $details = new GeolocationDetails($data);
    $currentTime = $details->getCurrentTime();

    expect($currentTime)->toBeInstanceOf(\DateTime::class);
    expect($currentTime->getTimezone()->getName())->toBe('America/Los_Angeles');
});

it('converts datetime to local timezone', function () {
    $data = [
        'ip' => '8.8.8.8',
        'timezone' => 'America/Los_Angeles',
    ];

    $details = new GeolocationDetails($data);
    $utcTime = new \DateTime('2024-01-01 12:00:00', new \DateTimeZone('UTC'));
    $localTime = $details->convertToLocalTime($utcTime);

    expect($localTime)->toBeInstanceOf(\DateTime::class);
    expect($localTime->getTimezone()->getName())->toBe('America/Los_Angeles');
});

it('converts to array', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($data);
    $array = $details->toArray();

    expect($array)->toBeArray();
    expect($array['ip'])->toBe('8.8.8.8');
    expect($array['city'])->toBe('Mountain View');
});

it('is json serializable', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'countryCode' => 'US',
        'latitude' => 37.386,
        'longitude' => -122.084,
    ];

    $details = new GeolocationDetails($data);
    $json = json_encode($details);

    expect($json)->toBeString();
    expect(json_decode($json, true))->toHaveKey('ip');
});

it('is immutable', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
    ];

    $details = new GeolocationDetails($data);

    expect(fn () => $details['ip'] = '1.1.1.1')
        ->toThrow(RuntimeException::class, 'immutable');
});

it('implements array access', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
    ];

    $details = new GeolocationDetails($data);

    expect(isset($details['ip']))->toBe(true);
    expect($details['ip'])->toBe('8.8.8.8');
    expect($details['city'])->toBe('Mountain View');
});

it('converts to string', function () {
    $data = [
        'ip' => '8.8.8.8',
        'city' => 'Mountain View',
        'region' => 'California',
        'country' => 'United States',
        'countryCode' => 'US',
    ];

    $details = new GeolocationDetails($data);

    expect((string) $details)->toContain('Mountain View');
});
