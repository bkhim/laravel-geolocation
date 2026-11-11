<?php

namespace Bkhim\Geolocation\Traits;

use Bkhim\Geolocation\Addons\Anonymization\IpAnonymizer;
use Bkhim\Geolocation\Events\LoginLocationRecorded;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

/**
 * Trait HasGeolocation
 *
 * Core geolocation functionality for recording login locations and retrieving login history.
 */
trait HasGeolocation
{
    /**
     * Record the login location for the user.
     *
     * @param  string  $ip  IP address of the login
     */
    public function recordLoginLocation(string $ip): void
    {
        // Get geolocation details
        $details = app('geolocation')->lookup($ip);

        // Determine what to store based on anonymization settings
        $storeIp = config('geolocation.user_trait.store_ip', true);
        $anonymizationMode = config('geolocation.user_trait.anonymization_mode', 'partial');

        $ipToStore = $storeIp ? $this->getIpToStore($ip, $anonymizationMode) : null;
        $ipHash = $storeIp && $anonymizationMode !== 'none' ? Hash::make($ip) : null;

        // Create login history record
        $loginHistory = LoginHistory::create([
            'user_id' => $this->getKey(),
            'ip' => $ipToStore,
            'ip_hash' => $ipHash,
            'country_code' => $details->getCountryCode(),
            'city' => $details->getCity(),
            'timezone' => $details->getTimezone(),
            'currency_code' => $details->getCurrencyCode(),
            'is_proxy' => $details->isProxy() ?? false,
            'is_tor' => $details->isTor() ?? false,
            'occurred_at' => now(),
        ]);

        // Fire event
        Event::dispatch(new LoginLocationRecorded($this, $loginHistory));
    }

    /**
     * Get the last login record for the user.
     */
    public function getLastLogin(): ?LoginHistory
    {
        // Check if the model has the loginHistories method (relationship)
        if (method_exists($this, 'loginHistories')) {
            return $this->loginHistories()->latest()->first();
        }

        // Fallback: query directly
        return LoginHistory::where('user_id', $this->getKey())
            ->latest()
            ->first();
    }

    /**
     * Get the country code of the last login.
     */
    public function getLastLoginCountry(): ?string
    {
        $lastLogin = $this->getLastLogin();

        return $lastLogin ? $lastLogin->country_code : null;
    }

    /**
     * Check if the login is from a new country compared to the user's history.
     *
     * @param  string  $ip  IP address to check
     */
    public function isLoginFromNewCountry(string $ip): bool
    {
        $details = app('geolocation')->lookup($ip);
        $countryCode = $details->getCountryCode();

        if (! $countryCode) {
            return false;
        }

        // Check if the model has the loginHistories method (relationship)
        if (method_exists($this, 'loginHistories')) {
            return ! $this->loginHistories()
                ->where('country_code', $countryCode)
                ->exists();
        }

        // Fallback: query directly
        return ! LoginHistory::where('user_id', $this->getKey())
            ->where('country_code', $countryCode)
            ->exists();
    }

    /**
     * Check if the login is from a new city compared to the user's history.
     *
     * @param  string  $ip  IP address to check
     */
    public function isLoginFromNewCity(string $ip): bool
    {
        $details = app('geolocation')->lookup($ip);
        $city = $details->getCity();

        if (! $city) {
            return false;
        }

        // Check if the model has the loginHistories method (relationship)
        if (method_exists($this, 'loginHistories')) {
            return ! $this->loginHistories()
                ->where('city', $city)
                ->exists();
        }

        // Fallback: query directly
        return ! LoginHistory::where('user_id', $this->getKey())
            ->where('city', $city)
            ->exists();
    }

    /**
     * Get the login history relationship.
     *
     * @return HasMany|null
     */
    public function loginHistories()
    {
        // Only return the relationship if the model is an Eloquent model
        if ($this instanceof Model) {
            return $this->hasMany(
                config('geolocation.user_trait.login_history_model', LoginHistory::class),
                'user_id'
            );
        }

        return null;
    }

    /**
     * Determine what IP to store based on anonymization mode.
     *
     * @param  string  $ip  Original IP address
     * @param  string  $mode  Anonymization mode (none, partial, full)
     */
    protected function getIpToStore(string $ip, string $mode): ?string
    {
        if ($mode === 'none') {
            return $ip;
        }

        if ($mode === 'full') {
            return null;
        }

        // partial mode (default): delegate to the anonymizer so the
        // configured ipv4_mask / ipv6_mask are honoured and both code paths
        // (storage + addon) behave identically.
        return $this->anonymizer()->mask($ip);
    }

    /**
     * Resolve the IP anonymizer (falls back to a fresh instance outside Laravel).
     */
    protected function anonymizer(): IpAnonymizer
    {
        if (function_exists('app') && app()->bound(IpAnonymizer::class)) {
            return app(IpAnonymizer::class);
        }

        return new IpAnonymizer;
    }
}
