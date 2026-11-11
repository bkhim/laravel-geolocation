<?php

use Bkhim\Geolocation\Addons\Anonymization\IpAnonymizer;
use Illuminate\Support\Facades\Http;

function anonymizerConfig(array $overrides = []): array
{
    return array_merge([
        'enabled' => true,
        'ipv4_mask' => '255.255.255.0',
        'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
        'preserve_local' => true,
        'gdpr_countries' => [],
    ], $overrides);
}

beforeEach(function () {
    config(['geolocation.cache.enabled' => false]);
});

it('does nothing when the addon is disabled', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['enabled' => false])]);

    $anonymizer = new IpAnonymizer;

    expect($anonymizer->isEnabled())->toBeFalse()
        ->and($anonymizer->scope())->toBe('none')
        ->and($anonymizer->anonymize('8.8.8.1'))->toBe('8.8.8.1');
});

it('anonymizes everything when the country list is empty', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig()]);

    $anonymizer = new IpAnonymizer;

    expect($anonymizer->scope())->toBe('all')
        ->and($anonymizer->anonymize('8.8.8.1'))->toBe('8.8.8.0')
        ->and($anonymizer->anonymize('2001:4860:4860::8888'))->toBe('2001:4860:4860::');
});

it('treats the wildcard as anonymize-all', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['gdpr_countries' => ['*']])]);

    expect((new IpAnonymizer)->anonymize('8.8.8.1'))->toBe('8.8.8.0');
});

it('anonymizes only countries in the configured list', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['gdpr_countries' => ['DE']])]);

    Http::fake([
        'ipinfo.io/85.*' => Http::response([
            'ip' => '85.214.132.117', 'country' => 'DE', 'city' => 'Berlin',
        ]),
        'ipinfo.io/8.8.8.*' => Http::response([
            'ip' => '8.8.8.8', 'country' => 'US', 'city' => 'Mountain View',
        ]),
    ]);

    $anonymizer = new IpAnonymizer;

    expect($anonymizer->scope())->toBe('gdpr')
        ->and($anonymizer->anonymize('85.214.132.117'))->toBe('85.214.132.0')
        ->and($anonymizer->anonymize('8.8.8.8'))->toBe('8.8.8.8');
});

it('does not anonymize when the lookup fails', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['gdpr_countries' => ['DE']])]);

    Http::fake(['ipinfo.io/*' => Http::response([], 500)]);

    expect((new IpAnonymizer)->anonymize('85.214.132.117'))->toBe('85.214.132.117');
});

it('preserves local ips when configured', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['preserve_local' => true])]);

    $anonymizer = new IpAnonymizer;

    expect($anonymizer->anonymize('127.0.0.1'))->toBe('127.0.0.1')
        ->and($anonymizer->anonymize('10.0.0.1'))->toBe('10.0.0.1')
        ->and($anonymizer->anonymize('172.16.0.1'))->toBe('172.16.0.1')
        ->and($anonymizer->anonymize('192.168.1.1'))->toBe('192.168.1.1');
});

it('masks local ips when preserve_local is disabled', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['preserve_local' => false])]);

    expect((new IpAnonymizer)->anonymize('192.168.1.1'))->toBe('192.168.1.0');
});

it('honours a custom ipv4 mask', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig(['ipv4_mask' => '255.255.0.0'])]);

    expect((new IpAnonymizer)->anonymize('8.8.8.1'))->toBe('8.8.0.0');
});

it('honours a custom ipv6 mask', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig([
        'ipv6_mask' => 'ffff:ffff:ffff:0000:0000:0000:0000:0000',
    ])]);

    expect((new IpAnonymizer)->anonymize('2a00:1450:4009:81f::200e'))->toBe('2a00:1450:4009::');
});

it('returns the original value for an invalid ip', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig()]);

    expect((new IpAnonymizer)->anonymize('invalid-ip'))->toBe('invalid-ip');
});

it('mask() always masks regardless of scope', function () {
    config(['geolocation.addons.anonymization' => anonymizerConfig([
        'enabled' => false,
        'gdpr_countries' => ['DE'],
    ])]);

    $anonymizer = new IpAnonymizer;

    expect($anonymizer->anonymize('8.8.8.1'))->toBe('8.8.8.1')
        ->and($anonymizer->mask('8.8.8.1'))->toBe('8.8.8.0');
});
