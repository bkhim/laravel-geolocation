<?php

namespace Bkhim\Geolocation\Addons\Middleware;

use Bkhim\Geolocation\Models\IpBlocklist;
use Bkhim\Geolocation\Services\ThreatIntelligenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityCheckMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($this->isBlocked($ip)) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP has been blocked due to suspicious activity',
            ], 403);
        }

        if ($this->isThreat($ip)) {
            app(\Bkhim\Geolocation\Contracts\AuditLoggerInterface::class)->log('Threat detected', [
                'ip' => $ip,
                'path' => $request->path(),
            ]);

            if (config('geolocation.threat_intelligence.auto_block', false)) {
                IpBlocklist::block($ip, 'Automatic block - threat intelligence');
            }
        }

        return $next($request);
    }

    protected function isBlocked(string $ip): bool
    {
        if (!config('geolocation.security.enable_blocking', true)) {
            return false;
        }

        return IpBlocklist::isBlocked($ip);
    }

    protected function isThreat(string $ip): bool
    {
        if (!config('geolocation.threat_intelligence.enabled', false)) {
            return false;
        }

        $service = app(ThreatIntelligenceService::class);
        return $service->isThreat($ip);
    }
}