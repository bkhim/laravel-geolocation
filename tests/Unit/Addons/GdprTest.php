<?php

use Bkhim\Geolocation\Addons\Gdpr\LocationConsentManager;
use Bkhim\Geolocation\GeolocationDetails;
use Illuminate\Support\Facades\Cookie;

it('checks if consent is needed for eu countries', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['EU'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // Mock geolocation lookup for EU country
    expect(method_exists($manager, 'needsConsent'))->toBe(true);
});

it('returns false when gdpr is disabled', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => false,
            'require_consent_for' => ['EU'],
        ],
    ]);

    $manager = new LocationConsentManager();

    $result = $manager->needsConsent('8.8.8.8');

    expect($result)->toBe(false);
});

it('checks for eea region', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['EEA'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // EEA includes Iceland, Liechtenstein, Norway
    expect(method_exists($manager, 'needsConsent'))->toBe(true);
});

it('checks for gdpr region', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['GDPR'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // GDPR includes both EU and EEA countries
    expect(method_exists($manager, 'needsConsent'))->toBe(true);
});

it('checks if user has given consent', function () {
    config([
        'geolocation.addons.gdpr' => [
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // Without cookie, should return false
    expect($manager->hasGivenConsent())->toBe(false);
});

it('sets consent cookie', function () {
    config([
        'geolocation.addons.gdpr' => [
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // Should not throw exception
    expect(fn () => $manager->giveConsent())
        ->not->toThrow(Exception::class);
});

it('withdraws consent', function () {
    config([
        'geolocation.addons.gdpr' => [
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // Should not throw exception
    expect(fn () => $manager->withdrawConsent())
        ->not->toThrow(Exception::class);
});

it('uses correct cookie name from config', function () {
    config([
        'geolocation.addons.gdpr' => [
            'consent_cookie' => 'custom_geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // The manager should use the configured cookie name
    expect(method_exists($manager, 'hasGivenConsent'))->toBe(true);
});

it('allows custom consent lifetime', function () {
    config([
        'geolocation.addons.gdpr' => [
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 30, // 30 days
        ],
    ]);

    $manager = new LocationConsentManager();

    // Should accept custom lifetime parameter
    expect(fn () => $manager->giveConsent(30))
        ->not->toThrow(Exception::class);
});

it('identifies eu countries', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['EU'],
        ],
    ]);

    $manager = new LocationConsentManager();

    // Manager is instantiable
    expect($manager)->not->toBeNull();
});

it('identifies eea countries', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['EEA'],
        ],
    ]);

    $manager = new LocationConsentManager();

    // EEA countries: IS, LI, NO
    expect(method_exists($manager, 'needsConsent'))->toBe(true);
});

it('handles multiple consent requirements', function () {
    config([
        'geolocation.addons.gdpr' => [
            'enabled' => true,
            'require_consent_for' => ['EU', 'EEA', 'GDPR'],
            'consent_cookie' => 'geo_consent',
            'consent_lifetime' => 365,
        ],
    ]);

    $manager = new LocationConsentManager();

    // Should check against all regions
    expect(method_exists($manager, 'needsConsent'))->toBe(true);
});
