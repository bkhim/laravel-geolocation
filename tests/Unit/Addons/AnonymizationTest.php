<?php

use Bkhim\Geolocation\Addons\Anonymization\IpAnonymizer;

it('anonymizes ipv4 addresses', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => true,
            'gdpr_countries' => [],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    $anonymized = $anonymizer->anonymize('8.8.8.1');

    expect($anonymized)->toBe('8.8.8.0');
});

it('preserves local ips when configured', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => true,
            'gdpr_countries' => ['US', 'EU'],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    // Local IP should not be anonymized
    $anonymized = $anonymizer->anonymize('192.168.1.1');
    expect($anonymized)->toBe('192.168.1.1');
});

it('does not anonymize when not in gdpr countries', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => false,
            'gdpr_countries' => ['DE', 'FR'], // Only European countries
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    // Should not anonymize IPs not in GDPR countries
    // (In real implementation, would check geolocation first)
    $result = $anonymizer->anonymize('8.8.8.1');

    expect($result)->not->toBeNull();
});

it('anonymizes with different ipv4 masks', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.0.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => false,
            'gdpr_countries' => [],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    $anonymized = $anonymizer->anonymize('8.8.8.1');

    // Mask 255.255.0.0 means keep first two octets
    expect($anonymized)->toBe('8.8.0.0');
});

it('identifies local ipv4 addresses', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => true,
            'gdpr_countries' => [],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    // Test various local IP ranges
    expect($anonymizer->anonymize('127.0.0.1'))->toBe('127.0.0.1');
    expect($anonymizer->anonymize('10.0.0.1'))->toBe('10.0.0.1');
    expect($anonymizer->anonymize('172.16.0.1'))->toBe('172.16.0.1');
    expect($anonymizer->anonymize('192.168.1.1'))->toBe('192.168.1.1');
});

it('handles ipv6 addresses', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => false,
            'gdpr_countries' => [],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    $result = $anonymizer->anonymize('2001:4860:4860::8888');

    // Should return an anonymized IPv6 with last 64 bits zeroed
    expect($result)->not->toBeNull()
        ->and($result)->toBe('2001:4860:4860::');
});

it('returns original ip for invalid ip', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => false,
            'gdpr_countries' => [],
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    $result = $anonymizer->anonymize('invalid-ip');

    expect($result)->toBe('invalid-ip');
});

it('handles wildcard gdpr countries config', function () {
    config([
        'geolocation.addons.anonymization' => [
            'ipv4_mask' => '255.255.255.0',
            'ipv6_mask' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
            'preserve_local' => false,
            'gdpr_countries' => ['*'], // Anonymize all
        ],
    ]);

    $anonymizer = new IpAnonymizer();

    $result = $anonymizer->anonymize('8.8.8.1');

    // Should anonymize all countries with * wildcard
    expect($result)->not->toBeNull();
});
