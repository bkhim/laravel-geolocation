<?php

/**
 * Verifies the packaged config actually reads environment variables.
 *
 * The config file is re-required after mutating $_ENV so we observe the same
 * env() resolution the framework uses at boot.
 */
function loadGeolocationConfig(): array
{
    return require __DIR__.'/../../../config/geolocation.php';
}

function setGeoEnv(array $values): void
{
    foreach ($values as $key => $value) {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function clearGeoEnv(array $keys): void
{
    foreach ($keys as $key) {
        unset($_ENV[$key], $_SERVER[$key]);
    }
}

it('defaults the addon flags to disabled', function () {
    $config = loadGeolocationConfig();

    expect($config['addons']['middleware']['enabled'])->toBeFalse()
        ->and($config['addons']['rate_limiting']['enabled'])->toBeFalse()
        ->and($config['addons']['anonymization']['enabled'])->toBeFalse()
        ->and($config['addons']['gdpr']['enabled'])->toBeFalse()
        ->and($config['addons']['gdpr']['routes']['enabled'])->toBeFalse();
});

it('reads the addon toggles from the environment', function () {
    setGeoEnv([
        'GEOLOCATION_MIDDLEWARE_ENABLED' => 'true',
        'GEOLOCATION_RATE_LIMITING_ENABLED' => 'true',
        'GEOLOCATION_ANONYMIZATION_ENABLED' => 'true',
        'GEOLOCATION_GDPR_ENABLED' => 'true',
        'GEOLOCATION_GDPR_ROUTES_ENABLED' => 'true',
    ]);

    try {
        $config = loadGeolocationConfig();

        expect($config['addons']['middleware']['enabled'])->toBeTrue()
            ->and($config['addons']['rate_limiting']['enabled'])->toBeTrue()
            ->and($config['addons']['anonymization']['enabled'])->toBeTrue()
            ->and($config['addons']['gdpr']['enabled'])->toBeTrue()
            ->and($config['addons']['gdpr']['routes']['enabled'])->toBeTrue();
    } finally {
        clearGeoEnv([
            'GEOLOCATION_MIDDLEWARE_ENABLED',
            'GEOLOCATION_RATE_LIMITING_ENABLED',
            'GEOLOCATION_ANONYMIZATION_ENABLED',
            'GEOLOCATION_GDPR_ENABLED',
            'GEOLOCATION_GDPR_ROUTES_ENABLED',
        ]);
    }
});

it('parses the anonymization country scope from a comma-separated env value', function () {
    setGeoEnv(['GEOLOCATION_ANONYMIZATION_COUNTRIES' => 'DE, FR ,NL']);

    try {
        $config = loadGeolocationConfig();

        expect($config['addons']['anonymization']['gdpr_countries'])->toBe(['DE', 'FR', 'NL']);
    } finally {
        clearGeoEnv(['GEOLOCATION_ANONYMIZATION_COUNTRIES']);
    }
});

it('treats an empty country scope as anonymize-all', function () {
    setGeoEnv(['GEOLOCATION_ANONYMIZATION_COUNTRIES' => '']);

    try {
        $config = loadGeolocationConfig();

        expect($config['addons']['anonymization']['gdpr_countries'])->toBe([]);
    } finally {
        clearGeoEnv(['GEOLOCATION_ANONYMIZATION_COUNTRIES']);
    }
});

it('reads anonymization masks and preserve_local from the environment', function () {
    setGeoEnv([
        'GEOLOCATION_ANONYMIZATION_IPV4_MASK' => '255.255.0.0',
        'GEOLOCATION_ANONYMIZATION_IPV6_MASK' => 'ffff:ffff:ffff:0000:0000:0000:0000:0000',
        'GEOLOCATION_ANONYMIZATION_PRESERVE_LOCAL' => 'false',
    ]);

    try {
        $config = loadGeolocationConfig();

        expect($config['addons']['anonymization']['ipv4_mask'])->toBe('255.255.0.0')
            ->and($config['addons']['anonymization']['ipv6_mask'])->toBe('ffff:ffff:ffff:0000:0000:0000:0000:0000')
            ->and($config['addons']['anonymization']['preserve_local'])->toBeFalse();
    } finally {
        clearGeoEnv([
            'GEOLOCATION_ANONYMIZATION_IPV4_MASK',
            'GEOLOCATION_ANONYMIZATION_IPV6_MASK',
            'GEOLOCATION_ANONYMIZATION_PRESERVE_LOCAL',
        ]);
    }
});

it('reads the gdpr consent settings from the environment', function () {
    setGeoEnv([
        'GEOLOCATION_GDPR_REGIONS' => 'EU,EEA',
        'GEOLOCATION_GDPR_CONSENT_COOKIE' => 'custom_consent',
        'GEOLOCATION_GDPR_CONSENT_LIFETIME' => '30',
        'GEOLOCATION_GDPR_ROUTE_GIVE' => '/accept',
        'GEOLOCATION_GDPR_ROUTE_WITHDRAW' => '/withdraw',
    ]);

    try {
        $config = loadGeolocationConfig();

        expect($config['addons']['gdpr']['require_consent_for'])->toBe(['EU', 'EEA'])
            ->and($config['addons']['gdpr']['consent_cookie'])->toBe('custom_consent')
            ->and($config['addons']['gdpr']['consent_lifetime'])->toBe('30')
            ->and($config['addons']['gdpr']['routes']['give'])->toBe('/accept')
            ->and($config['addons']['gdpr']['routes']['withdraw'])->toBe('/withdraw');
    } finally {
        clearGeoEnv([
            'GEOLOCATION_GDPR_REGIONS',
            'GEOLOCATION_GDPR_CONSENT_COOKIE',
            'GEOLOCATION_GDPR_CONSENT_LIFETIME',
            'GEOLOCATION_GDPR_ROUTE_GIVE',
            'GEOLOCATION_GDPR_ROUTE_WITHDRAW',
        ]);
    }
});
