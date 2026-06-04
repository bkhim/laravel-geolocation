<?php

namespace Bkhim\Geolocation\Services;

use Bkhim\Geolocation\Geolocation;
use Bkhim\Geolocation\GeolocationDetails;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Support\Collection;

/**
 * Class AnomalyDetector
 *
 * Detects unusual location patterns that may indicate fraudulent activity.
 * Features include impossible travel detection, new country/city detection,
 * and location history analysis.
 *
 * @package Bkhim\Geolocation\Services
 */
class AnomalyDetector
{
    /**
     * Maximum distance (km) that can be traveled in 1 hour to be considered possible.
     */
    protected int $maxSpeedKmh = 1000;

    /**
     * Maximum number of different countries in 30 days before flagging.
     */
    protected int $maxCountries = 3;

    /**
     * Number of days to analyze for location history.
     */
    protected int $historyDays = 30;

    /**
     * Check if a login from an IP is anomalous for a user.
     *
     * @param string $ip IP address to check
     * @param int $userId User ID to check history for
     * @return bool
     */
    public function isAnomalous(string $ip, int $userId): bool
    {
        $current = Geolocation::lookup($ip);
        $history = $this->getHistory($userId);

        if ($history->isEmpty()) {
            return false;
        }

        if ($this->hasTooManyCountries($history)) {
            return true;
        }

        foreach ($history as $login) {
            if ($this->isImpossibleTravel($login, $current)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for impossible travel between two locations.
     *
     * @param LoginHistory $past Past login record
     * @param GeolocationDetails $current Current location
     * @param int|null $hours Time window in hours (defaults to login interval)
     * @return bool
     */
    public function isImpossibleTravel(LoginHistory $past, GeolocationDetails $current, ?int $hours = null): bool
    {
        if (!$past->latitude || !$past->longitude || !$current->getLatitude() || !$current->getLongitude()) {
            return false;
        }

        $distance = $this->calculateDistance(
            $past->latitude,
            $past->longitude,
            $current->getLatitude(),
            $current->getLongitude()
        );

        $hours = $hours ?? max(1, $past->occurred_at->diffInHours(now()));
        $speed = $distance / $hours;

        return $speed > $this->maxSpeedKmh;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if this is a login from a new country for the user.
     *
     * @param string $ip IP address to check
     * @param int $userId User ID
     * @return bool
     */
    public function isNewCountry(string $ip, int $userId): bool
    {
        $current = Geolocation::lookup($ip);
        $currentCountry = $current->getCountryCode();

        if (!$currentCountry) {
            return false;
        }

        $previousCountries = $this->getUniqueCountries($userId);

        return !$previousCountries->contains($currentCountry);
    }

    /**
     * Check if this is a login from a new city for the user.
     *
     * @param string $ip IP address to check
     * @param int $userId User ID
     * @return bool
     */
    public function isNewCity(string $ip, int $userId): bool
    {
        $current = Geolocation::lookup($ip);
        $currentCity = $current->getCity();

        if (!$currentCity) {
            return false;
        }

        $previousCities = $this->getUniqueCities($userId);

        return !$previousCities->contains($currentCity);
    }

    /**
     * Check if user has logged in from too many different countries.
     *
     * @param Collection|array $history Login history
     * @return bool
     */
    public function hasTooManyCountries($history): bool
    {
        $history = $history instanceof Collection ? $history : collect($history);

        $uniqueCountries = $history
            ->pluck('country_code')
            ->filter()
            ->unique()
            ->count();

        return $uniqueCountries > $this->maxCountries;
    }

    /**
     * Get user's login history within the configured time window.
     *
     * @param int $userId User ID
     * @return Collection
     */
    public function getHistory(int $userId): Collection
    {
        return LoginHistory::where('user_id', $userId)
            ->where('occurred_at', '>', now()->subDays($this->historyDays))
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get unique countries from user's login history.
     *
     * @param int $userId User ID
     * @return Collection
     */
    public function getUniqueCountries(int $userId): Collection
    {
        return LoginHistory::where('user_id', $userId)
            ->where('occurred_at', '>', now()->subDays($this->historyDays))
            ->pluck('country_code')
            ->filter()
            ->unique();
    }

    /**
     * Get unique cities from user's login history.
     *
     * @param int $userId User ID
     * @return Collection
     */
    public function getUniqueCities(int $userId): Collection
    {
        return LoginHistory::where('user_id', $userId)
            ->where('occurred_at', '>', now()->subDays($this->historyDays))
            ->pluck('city')
            ->filter()
            ->unique();
    }

    /**
     * Get detailed anomaly report for a login.
     *
     * @param string $ip IP address
     * @param int $userId User ID
     * @return array{
     *     is_anomalous: bool,
     *     is_impossible_travel: bool,
     *     is_new_country: bool,
     *     is_new_city: bool,
     *     too_many_countries: bool,
     *     country_count: int,
     *     previous_countries: array,
     *     distance_from_last_login: float|null,
     *     travel_speed_kmh: float|null
     * }
     */
    public function getAnomalyReport(string $ip, int $userId): array
    {
        $current = Geolocation::lookup($ip);
        $history = $this->getHistory($userId);

        $previousCountries = $this->getUniqueCountries($userId);
        $countryCount = $previousCountries->count();

        $lastLogin = $history->first();
        $distanceFromLast = null;
        $travelSpeed = null;

        if ($lastLogin && $lastLogin->latitude && $lastLogin->longitude) {
            $distanceFromLast = $this->calculateDistance(
                $lastLogin->latitude,
                $lastLogin->longitude,
                $current->getLatitude() ?? 0,
                $current->getLongitude() ?? 0
            );

            $hours = max(1, $lastLogin->occurred_at->diffInHours(now()));
            $travelSpeed = $distanceFromLast / $hours;
        }

        return [
            'is_anomalous' => $this->isAnomalous($ip, $userId),
            'is_impossible_travel' => $lastLogin ? $this->isImpossibleTravel($lastLogin, $current) : false,
            'is_new_country' => $this->isNewCountry($ip, $userId),
            'is_new_city' => $this->isNewCity($ip, $userId),
            'too_many_countries' => $this->hasTooManyCountries($history),
            'country_count' => $countryCount,
            'previous_countries' => $previousCountries->toArray(),
            'distance_from_last_login' => $distanceFromLast,
            'travel_speed_kmh' => $travelSpeed,
        ];
    }

    /**
     * Set maximum speed in km/h for travel validation.
     *
     * @param int $speed
     * @return self
     */
    public function setMaxSpeed(int $speed): self
    {
        $this->maxSpeedKmh = $speed;
        return $this;
    }

    /**
     * Set maximum number of countries allowed in history period.
     *
     * @param int $count
     * @return self
     */
    public function setMaxCountries(int $count): self
    {
        $this->maxCountries = $count;
        return $this;
    }

    /**
     * Set the number of days to analyze for history.
     *
     * @param int $days
     * @return self
     */
    public function setHistoryDays(int $days): self
    {
        $this->historyDays = $days;
        return $this;
    }
}