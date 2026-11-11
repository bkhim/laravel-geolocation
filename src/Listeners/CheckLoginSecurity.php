<?php

namespace Bkhim\Geolocation\Listeners;

use Bkhim\Geolocation\Events\HighRiskIpDetected;
use Bkhim\Geolocation\Events\LoginLocationRecorded;
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;

/**
 * Evaluates a freshly recorded login for security signals and dispatches
 * the matching events.
 *
 * Registered exactly once by the service provider (previously it was
 * registered inside a trait boot method, which duplicated listeners for every
 * model that used the trait).
 */
class CheckLoginSecurity
{
    public function handle(LoginLocationRecorded $event): void
    {
        $user = $event->user;
        $loginHistory = $event->loginHistory;

        if (! is_object($user) || ! method_exists($user, 'requiresMfaDueToLocation')) {
            return;
        }

        $ip = $loginHistory->ip ?? null;

        if (! $ip) {
            return;
        }

        try {
            if (config('geolocation.security.enable_mfa_trigger', true)
                && $user->requiresMfaDueToLocation($ip)) {
                SuspiciousLocationDetected::dispatch($user, $loginHistory);
            }

            if (method_exists($user, 'isHighRiskLogin') && $user->isHighRiskLogin($ip)) {
                HighRiskIpDetected::dispatch($user, $loginHistory);
            }
        } catch (\Throwable $e) {
            // Never let a security evaluation break the login flow.
            report($e);
        }
    }
}
