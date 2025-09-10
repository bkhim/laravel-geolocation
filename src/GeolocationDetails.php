<?php

namespace Bkhim\Geolocation;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class GeolocationDetails.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 13:24
 *
 * @package Bkhim\Geolocation
 */
class GeolocationDetails implements \JsonSerializable, Arrayable
{
    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $region;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $countryCode;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var string|null
     */
    protected $timezone;

    /**
     * GeolocationDetails constructor.
     *
     * @param  array $data
     */
    public function __construct($data = [])
    {
        $this->parse($data);
    }

    /**
     * Get the IP Address.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Get the City name.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Get the region name.
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Get the country name.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get the country ISO Code.
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Get the Latitude value.
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get the Longitude value.
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get the timezone for the IP address.
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * Parses the raw response.
     *
     * @param  mixed $data
     */
    protected function parse($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        foreach ((array) $data as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key === 'country') {
                    $this->countryCode = $value;
                    $this->{$key} = $this->formatCountry($value);
                } else {
                    $this->{$key} = $value;
                }
            }

            if ($key === 'loc') {
                $this->formatCoordinates($value);
            }
        }
    }

    /**
     * Parses the coordinates values into latitude and longitude.
     *
     * @param  string $value
     */
    protected function formatCoordinates($value): void
    {
        [$latitude, $longitude] = explode(',', $value);

        $this->latitude = (float) $latitude;
        $this->longitude = (float) $longitude;
    }

    /**
     * Format the country name.
     *
     * @param  string $countryCode
     *
     * @return mixed
     */
    protected function formatCountry($countryCode)
    {
        $countries = trans('geolocation::countries');

        return array_key_exists($countryCode, $countries) ? $countries[$countryCode] : $countryCode;
    }

    /**
     * Check if timezone data is available.
     *
     * @return bool
     */
    public function hasTimezone(): bool
    {
        return !empty($this->timezone);
    }

    /**
     * Get current time in the IP's timezone.
     *
     * @return \DateTime|null
     */
    public function getCurrentTime(): ?\DateTime
    {
        if (!$this->timezone) {
            return null;
        }

        try {
            return new \DateTime('now', new \DateTimeZone($this->timezone));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert a datetime to the IP's timezone.
     *
     * @param \DateTimeInterface $dateTime
     * @return \DateTime|null
     */
    public function convertToLocalTime(\DateTimeInterface $dateTime): ?\DateTime
    {
        if (!$this->timezone) {
            return null;
        }

        try {
            return (new \DateTime($dateTime->format('Y-m-d H:i:s')))
                ->setTimezone(new \DateTimeZone($this->timezone));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get list of location items as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'countryCode' => $this->countryCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
