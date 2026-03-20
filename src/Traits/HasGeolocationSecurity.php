<?php

namespace Bkhim\Geolocation\Traits;

use Bkhim\Geolocation\Events\HighRiskIpDetected;
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Trait HasGeolocationSecurity
 *
 * Security-focused geolocation functionality for detecting suspicious logins
 * and triggering security events like MFA requirements.
 *
 * @package Bkhim\Geolocation\Traits
 */
trait HasGeolocationSecurity
{
    use HasGeolocation;

    /**
     * Determine if MFA is required due to location-based security concerns.
     *
     * @param string $ip IP address to check
     * @return bool
     */
    public function requiresMfaDueToLocation(string $ip): bool
    {
        // Check if MFA trigger is enabled
        if (!config('geolocation.security.enable_mfa_trigger', true)) {
            return false;
        }

        $details = app('geolocation')->getDetails($ip);
        $riskLevel = $this->getLastLoginRiskLevel($ip);

        // Require MFA for high or critical risk levels
        $riskThreshold = config('geolocation.security.risk_threshold', 'high');

        return $this->isRiskLevelAtLeast($riskLevel, $riskThreshold);
    }

    /**
     * Determine if a login is high risk based on configurable rules.
     *
     * @param string $ip IP address to check
     * @return bool
     */
    public function isHighRiskLogin(string $ip): bool
    {
        return $this->getRiskScore($ip)['is_high_risk'];
    }

    /**
     * Get the risk score breakdown for an IP address.
     *
     * @param string $ip IP address to check
     * @return array{score: int, is_high_risk: bool, threshold: int, triggers: array, trusted_country: bool}
     */
    public function getRiskScore(string $ip): array
    {
        $details = app('geolocation')->lookup($ip);
        $rules = config('geolocation.security.rules', []);
        $threshold = config('geolocation.security.high_risk_threshold', 70);
        $trustedCountries = config('geolocation.security.trusted_countries', []);

        $triggers = [];
        $score = 0;

        if (in_array($details->getCountryCode(), $trustedCountries)) {
            return [
                'score' => 0,
                'is_high_risk' => false,
                'threshold' => $threshold,
                'triggers' => [],
                'trusted_country' => true,
            ];
        }

        if ($details->isProxy()) {
            $triggers['proxy'] = true;
            $score += $rules['proxy'] ?? 0;
        }

        if ($details->isTor()) {
            $triggers['tor'] = true;
            $score += $rules['tor'] ?? 0;
        }

        if (method_exists($details, 'isCrawler') && $details->isCrawler()) {
            $triggers['crawler'] = true;
            $score += $rules['crawler'] ?? 0;
        }

        if ($this->isLoginFromNewCountry($ip)) {
            $triggers['new_country'] = true;
            $score += $rules['new_country'] ?? 0;
        }

        if ($this->isLoginFromNewCity($ip)) {
            $triggers['new_city'] = true;
            $score += $rules['new_city'] ?? 0;
        }

        $customRules = config('geolocation.security.custom_rules', []);
        foreach ($customRules as $ruleClass) {
            if (class_exists($ruleClass)) {
                $rule = app($ruleClass);
                if (method_exists($rule, 'score')) {
                    $additionalScore = $rule->score($this, $details);
                    $score += $additionalScore;
                    if ($additionalScore > 0) {
                        $triggers['custom_' . class_basename($ruleClass)] = true;
                    }
                }
            }
        }

        return [
            'score' => $score,
            'is_high_risk' => $score >= $threshold,
            'threshold' => $threshold,
            'triggers' => $triggers,
            'trusted_country' => false,
        ];
    }

    /**
     * Evaluate risk details and return a score.
     *
     * @param  mixed  $details
     * @return int
     */
    protected function evaluateRiskDetails($details): int
    {
        $score = 0;
        $rules = config('geolocation.security.rules', []);

        // Proxy
        if ($details->isProxy()) {
            $score += $rules['proxy'] ?? 0;
        }

        // Tor
        if ($details->isTor()) {
            $score += $rules['tor'] ?? 0;
        }

        // Crawler - check if method exists (some providers may not have it)
        if (method_exists($details, 'isCrawler') && $details->isCrawler()) {
            $score += $rules['crawler'] ?? 0;
        }

        // New country
        if ($this->isLoginFromNewCountry($details->getIp())) {
            $score += $rules['new_country'] ?? 0;
        }

        // Custom rules - for future extensibility
        $customRules = config('geolocation.security.custom_rules', []);
        foreach ($customRules as $ruleClass) {
            if (class_exists($ruleClass)) {
                $rule = app($ruleClass);
                if (method_exists($rule, 'score')) {
                    $score += $rule->score($this, $details);
                }
            }
        }

        return $score;
    }

    /**
     * Get the count of suspicious logins for the user.
     *
     * @return int
     */
    public function getSuspiciousLoginCount(): int
    {
        $threshold = now()->subDays(30); // Last 30 days

        return $this->loginHistories()
            ->where('occurred_at', '>=', $threshold)
            ->where(function ($query) {
                $query->where('is_proxy', true)
                    ->orWhere('is_tor', true);
            })
            ->count();
    }

    /**
     * Get the risk level of the last login.
     *
     * @param string|null $ip Optional IP to check (uses last login IP if not provided)
     * @return string low|high|critical
     */
    public function getLastLoginRiskLevel(?string $ip = null): string
    {
        if (!$ip) {
            $lastLogin = $this->getLastLogin();
            if (!$lastLogin) {
                return 'low';
            }
            $ip = $lastLogin->ip;
        }

        // If we can't get details, return low risk
        try {
            $details = app('geolocation')->getDetails($ip);
        } catch (\Exception $e) {
            return 'low';
        }

        $riskScore = 0;

        // Proxy/VPN detection
        if ($details->isProxy ?? false) {
            $riskScore += 3;
        }

        // Tor detection
        if ($details->isTor ?? false) {
            $riskScore += 3;
        }

        // New country detection
        if ($this->isLoginFromNewCountry($ip)) {
            $riskScore += 2;
        }

        // Convert score to risk level
        if ($riskScore >= 5) {
            return 'critical';
        } elseif ($riskScore >= 3) {
            return 'high';
        } else {
            return 'low';
        }
    }

    /**
     * Check if the risk level is at least the specified level.
     *
     * @param string $currentLevel Current risk level
     * @param string $thresholdLevel Threshold risk level
     * @return bool
     */
    protected function isRiskLevelAtLeast(string $currentLevel, string $thresholdLevel): bool
    {
        $levels = ['low' => 1, 'high' => 2, 'critical' => 3];

        return ($levels[$currentLevel] ?? 0) >= ($levels[$thresholdLevel] ?? 0);
    }

    /**
     * Boot the trait - fire events when suspicious activity is detected.
     */
    protected static function bootHasGeolocationSecurity(): void
    {
        static::created(function ($model) {
            // When a user is created, we don't have login history yet
        });

        static::updated(function ($model) {
            // Check for suspicious activity on user updates if needed
        });

        // Listen for login location recorded events to check for suspicious activity
        \Illuminate\Support\Facades\Event::listen(
            \Bkhim\Geolocation\Events\LoginLocationRecorded::class,
            function (\Bkhim\Geolocation\Events\LoginLocationRecorded $event) {
                /** @var \Bkhim\Geolocation\Traits\HasGeolocationSecurity $user */
                $user = $event->user;
                $loginHistory = $event->loginHistory;

                // Check if this login requires MFA
                if ($user instanceof self && $user->requiresMfaDueToLocation($loginHistory->ip)) {
                    // Fire suspicious location detected event
                    \Illuminate\Support\Facades\Event::dispatch(
                        new \Bkhim\Geolocation\Events\SuspiciousLocationDetected($user, $loginHistory)
                    );
                }

                // Check if IP is high risk using the new method
                if ($user instanceof self && $user->isHighRiskLogin($loginHistory->ip)) {
                    // Fire high risk IP detected event
                    \Illuminate\Support\Facades\Event::dispatch(
                        new \Bkhim\Geolocation\Events\HighRiskIpDetected($user, $loginHistory)
                    );
                }
            }
        );
    }
}