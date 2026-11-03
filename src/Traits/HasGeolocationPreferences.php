<?php

namespace Bkhim\Geolocation\Traits;

/**
 * Trait HasGeolocationPreferences
 *
 * Personalization-focused geolocation functionality for providing
 * timezone and currency information based on user's location.
 */
trait HasGeolocationPreferences
{
    use HasGeolocation;

    /**
     * Get the detected timezone from the user's last login.
     */
    public function getDetectedTimezone(): ?string
    {
        // If personalization is disabled, return null
        if (! config('geolocation.personalization.enable_timezone', true)) {
            return null;
        }

        $lastLogin = $this->getLastLogin();

        return $lastLogin ? $lastLogin->timezone : null;
    }

    /**
     * Get the local currency from the user's last login.
     */
    public function getLocalCurrency(): ?string
    {
        // If personalization is disabled, return null
        if (! config('geolocation.personalization.enable_currency', true)) {
            return null;
        }

        $lastLogin = $this->getLastLogin();

        return $lastLogin ? $lastLogin->currency_code : null;
    }

    /**
     * Get the detected timezone with fallback to app timezone.
     */
    public function getTimezone(): string
    {
        $detected = $this->getDetectedTimezone();

        return $detected ?: config('app.timezone', 'UTC');
    }

    /**
     * Get the local currency with fallback to default currency.
     *
     * @param  string  $default  Default currency code (e.g., 'USD')
     */
    public function getCurrency(string $default = 'USD'): string
    {
        $detected = $this->getLocalCurrency();

        return $detected ?: $default;
    }
}
