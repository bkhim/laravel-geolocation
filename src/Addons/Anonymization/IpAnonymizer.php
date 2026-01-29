<?php

namespace Bkhim\Geolocation\Addons\Anonymization;

class IpAnonymizer
{
    public function anonymize(string $ip): string
    {
        if (!$this->shouldAnonymize($ip)) {
            return $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->anonymizeIPv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->anonymizeIPv6($ip);
        }

        return $ip;
    }

    protected function anonymizeIPv4(string $ip): string
    {
        $config = config('geolocation.addons.anonymization', []);
        $mask = $config['ipv4_mask'] ?? '255.255.255.0';

        return long2ip(ip2long($ip) & ip2long($mask));
    }

    protected function anonymizeIPv6(string $ip): string
    {
        $config = config('geolocation.addons.anonymization', []);
        $mask = $config['ipv6_mask'] ?? 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';

        $ipHex = bin2hex(inet_pton($ip));
        $maskHex = bin2hex(inet_pton($mask));
        $anonymizedHex = $ipHex & $maskHex;

        return inet_ntop(hex2bin(str_pad($anonymizedHex, 32, '0', STR_PAD_LEFT)));
    }

    protected function shouldAnonymize(string $ip): bool
    {
        $config = config('geolocation.addons.anonymization', []);

        // Don't anonymize local IPs if configured
        if (($config['preserve_local'] ?? false) && $this->isLocalIp($ip)) {
            return false;
        }

        $gdprCountries = $config['gdpr_countries'] ?? [];

        // Anonymize all if gdpr_countries is empty or contains '*'
        if (empty($gdprCountries) || (is_array($gdprCountries) && in_array('*', $gdprCountries, true))) {
            return true;
        }

        // Check if user is from a GDPR country
        $location = null;
        try {
            $location = app('geolocation')->lookup($ip);
        } catch (\Throwable $e) {
            // If lookup fails for any reason, do not anonymize by default.
            return false;
        }

        $countryCode = '';
        if ($location) {
            if (is_callable([$location, 'getCountryCode'])) {
                $countryCode = $location->getCountryCode() ?? '';
            } elseif (is_object($location)) {
                $countryCode = $location->country ?? $location->country_code ?? $location->countryCode ?? '';
            } elseif (is_array($location)) {
                $countryCode = $location['country'] ?? $location['country_code'] ?? $location['countryCode'] ?? '';
            }
        }

        return in_array(strtoupper($countryCode), array_map('strtoupper', $gdprCountries), true);
    }

    protected function isLocalIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
