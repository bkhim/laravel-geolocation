<?php

namespace Bkhim\Geolocation\Addons\Anonymization;

use Throwable;

/**
 * IP anonymization with three modes driven by config:
 *
 *   enabled=false               → never mask (master switch)
 *   enabled=true, countries=[]  → mask every IP      (default)
 *   enabled=true, countries=[…] → mask only listed countries ('*' = all)
 */
class IpAnonymizer
{
    /**
     * Whether the anonymization addon is switched on.
     */
    public function isEnabled(): bool
    {
        return (bool) config('geolocation.addons.anonymization.enabled', false);
    }

    /**
     * Resolve the effective scope: 'none', 'all' or 'gdpr'.
     */
    public function scope(): string
    {
        if (! $this->isEnabled()) {
            return 'none';
        }

        $countries = $this->countries();

        if (empty($countries) || in_array('*', $countries, true)) {
            return 'all';
        }

        return 'gdpr';
    }

    /**
     * Anonymize an IP according to the configured policy.
     *
     * Returns the original address when the addon is disabled, the IP is
     * local and `preserve_local` is on, or the country is out of scope.
     */
    public function anonymize(string $ip): string
    {
        if (! $this->isEnabled()) {
            return $ip;
        }

        if (! $this->shouldAnonymize($ip)) {
            return $ip;
        }

        return $this->mask($ip);
    }

    /**
     * Decide whether the given IP falls within the masking policy.
     */
    public function shouldAnonymize(string $ip): bool
    {
        $config = config('geolocation.addons.anonymization', []);

        if (($config['preserve_local'] ?? false) && $this->isLocalIp($ip)) {
            return false;
        }

        $countries = $this->countries();

        // Empty list or '*' means: mask everything.
        if (empty($countries) || in_array('*', $countries, true)) {
            return true;
        }

        return $this->isInCountries($ip, $countries);
    }

    /**
     * Mask an IP unconditionally using the configured masks.
     *
     * This is the low-level operation used by storage policies (e.g. the
     * HasGeolocation trait) that have already decided the IP should be masked.
     */
    public function mask(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->anonymizeIPv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->anonymizeIPv6($ip);
        }

        return $ip;
    }

    /**
     * Whether the IP resolves to one of the given country codes.
     *
     * @param  array<int, string>  $countries
     */
    public function isInCountries(string $ip, array $countries): bool
    {
        $countries = array_map(fn ($c) => strtoupper((string) $c), $countries);

        try {
            $location = app('geolocation')->lookup($ip);
        } catch (Throwable $e) {
            // If the lookup fails, do not anonymize by default.
            return false;
        }

        $countryCode = $this->countryCodeOf($location);

        return $countryCode !== '' && in_array($countryCode, $countries, true);
    }

    protected function anonymizeIPv4(string $ip): string
    {
        $mask = config('geolocation.addons.anonymization.ipv4_mask', '255.255.255.0');
        $ipLong = ip2long($ip);
        $maskLong = ip2long($mask);

        if ($ipLong === false || $maskLong === false) {
            return $ip;
        }

        return long2ip($ipLong & $maskLong);
    }

    protected function anonymizeIPv6(string $ip): string
    {
        $mask = config('geolocation.addons.anonymization.ipv6_mask', 'ffff:ffff:ffff:ffff:0000:0000:0000:0000');

        $ipBin = @inet_pton($ip);
        $maskBin = @inet_pton($mask);

        if ($ipBin === false || $maskBin === false) {
            return $ip;
        }

        $masked = @inet_ntop($ipBin & $maskBin);

        return $masked !== false ? $masked : $ip;
    }

    /**
     * Normalise the configured country list.
     *
     * @return array<int, string>
     */
    protected function countries(): array
    {
        $countries = config('geolocation.addons.anonymization.gdpr_countries', []);

        return is_array($countries) ? array_values($countries) : [];
    }

    protected function countryCodeOf($location): string
    {
        if (! $location) {
            return '';
        }

        if (is_callable([$location, 'getCountryCode'])) {
            return strtoupper((string) ($location->getCountryCode() ?? ''));
        }

        if (is_object($location)) {
            return strtoupper((string) ($location->country ?? $location->country_code ?? $location->countryCode ?? ''));
        }

        if (is_array($location)) {
            return strtoupper((string) ($location['country'] ?? $location['country_code'] ?? $location['countryCode'] ?? ''));
        }

        return '';
    }

    protected function isLocalIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
