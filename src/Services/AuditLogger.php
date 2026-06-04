<?php

namespace Bkhim\Geolocation\Services;

use Bkhim\Geolocation\Contracts\AuditLoggerInterface;
use Illuminate\Support\Facades\Log;

class AuditLogger implements AuditLoggerInterface
{
    public function log(string $event, array $context = []): void
    {
        // Ensure PII is masked, especially IPs
        if (isset($context['ip'])) {
            $context['ip'] = substr($context['ip'], 0, 7) . '...';
        }

        Log::channel(config('geolocation.audit_log_channel', 'stack'))
            ->info("Geolocation Audit: {$event}", $context);
    }
}
