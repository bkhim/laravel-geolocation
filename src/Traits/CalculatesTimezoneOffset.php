<?php

namespace Bkhim\Geolocation\Traits;

/**
 * Trait CalculatesTimezoneOffset.
 *
 * Provides shared functionality for calculating timezone offsets from timezone identifiers.
 */
trait CalculatesTimezoneOffset
{
    /**
     * Calculate timezone offset in hours from UTC for a given timezone identifier.
     *
     * @param string|null $timezone Timezone identifier (e.g., 'America/New_York')
     * @return float|null Hours offset from UTC, or null if timezone is invalid
     */
    protected function calculateTimezoneOffset(?string $timezone): ?float
    {
        if (empty($timezone)) {
            return null;
        }

        try {
            $tz = new \DateTimeZone($timezone);
            $datetime = new \DateTime('now', $tz);
            return $tz->getOffset($datetime) / 3600; // Convert seconds to hours
        } catch (\Exception $e) {
            return null;
        }
    }
}
