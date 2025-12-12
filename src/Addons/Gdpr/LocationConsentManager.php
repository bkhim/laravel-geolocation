<?php

namespace Bkhim\Geolocation\Addons\Gdpr;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationException;

class LocationConsentManager
{

    /**
     * @throws GeolocationException
     */
    public function needsConsent(string $ip): bool
    {
        $config = config('geolocation.addons.gdpr');

        if (!$config['enabled']) {
            return false;
        }

        $location = Geolocation::lookup($ip);

        // Check if user is from a GDPR-affected region
        foreach ($config['require_consent_for'] as $region) {
            if ($this->isInRegion($location, $region)) {
                return true;
            }
        }

        return false;
    }

    public function hasGivenConsent(): bool
    {
        $cookieName = config('geolocation.addons.gdpr.consent_cookie', 'geo_consent');
        return request()->cookie($cookieName) === 'accepted';
    }

    public function giveConsent(int $lifetime = null): void
    {
        $config = config('geolocation.addons.gdpr');
        $lifetime = $lifetime ?? $config['consent_lifetime'] ?? 365;

        cookie()->queue(
            $config['consent_cookie'],
            'accepted',
            $lifetime * 24 * 60
        );
    }

    public function withdrawConsent(): void
    {
        $cookieName = config('geolocation.addons.gdpr.consent_cookie', 'geo_consent');
        cookie()->queue(cookie()->forget($cookieName));
    }

    protected function isInRegion($location, string $region): bool
    {
        if (!$location) {
            return false;
        }

        $regions = [
            'EU' => ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'],
            'EEA' => ['IS','LI','NO'],
            'GDPR' => ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB','IS','LI','NO'],
        ];

        return in_array($location->countryCode ?? '', $regions[$region] ?? []);
    }
}
