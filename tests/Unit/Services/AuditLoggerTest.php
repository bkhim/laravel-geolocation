<?php

use Bkhim\Geolocation\Services\AuditLogger;
use Illuminate\Support\Facades\Log;

function auditMaskedIp(string $ip): string
{
    $logger = new AuditLogger;
    $method = new ReflectionMethod($logger, 'maskIp');
    $method->setAccessible(true);

    return $method->invoke($logger, $ip);
}

it('masks the last octet of an ipv4 address', function () {
    expect(auditMaskedIp('8.8.8.8'))->toBe('8.8.8.0');
});

it('masks the last 80 bits of a compressed ipv6 address', function () {
    expect(auditMaskedIp('2001:4860:4860::8888'))->toBe('2001:4860:4860::');
});

it('replaces invalid ip values instead of leaking them', function () {
    expect(auditMaskedIp('not-an-ip'))->toBe('[invalid-ip]');
});

it('logs the event with masked pii context', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Login attempt')
                && $context['ip'] === '8.8.8.0'
                && $context['user_id'] === 1;
        });

    (new AuditLogger)->log('Login attempt', ['ip' => '8.8.8.8', 'user_id' => 1]);
});

it('does not throw for invalid context values', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info');

    expect(fn () => (new AuditLogger)->log('Event', ['ip' => 'garbage']))
        ->not->toThrow(Throwable::class);
});
