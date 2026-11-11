<?php

namespace Bkhim\Geolocation\Services;

use Bkhim\Geolocation\Contracts\AuditLoggerInterface;
use Illuminate\Support\Facades\Log;

class AuditLogger implements AuditLoggerInterface
{
    /**
     * Log a security event with PII-safe context.
     *
     * IPs are anonymized consistently with the GDPR addon (last octet for
     * IPv4, last 80 bits for IPv6) instead of the previous crude truncation.
     */
    public function log(string $event, array $context = []): void
    {
        foreach (['ip', 'ip_address'] as $key) {
            if (isset($context[$key]) && is_string($context[$key])) {
                $context[$key] = $this->maskIp($context[$key]);
            }
        }

        Log::channel(config('geolocation.logging.audit_channel', 'stack'))
            ->info("Geolocation Audit: {$event}", $context);
    }

    /**
     * Mask an IP without depending on the anonymization addon being enabled.
     */
    protected function maskIp(string $ip): string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return '[invalid-ip]';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        $binary = @inet_pton($ip);

        if ($binary === false) {
            return '[invalid-ip]';
        }

        for ($i = 6; $i < 16; $i++) {
            $binary[$i] = "\0";
        }

        $masked = @inet_ntop($binary);

        return $masked !== false ? $masked : '[invalid-ip]';
    }
}
