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
class GeolocationDetails implements \JsonSerializable, Arrayable, \ArrayAccess
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
     * @var int|null
     */
    protected $timezoneOffset;

    /**
     * @var string|null
     */
    protected $currency;

    /**
     * @var string|null
     */
    protected $currencyCode;

    /**
     * @var string|null
     */
    protected $currencySymbol;

    /**
     * @var string|null
     */
    protected $continent;

    /**
     * @var string|null
     */
    protected $continentCode;

    /**
     * @var string|null
     */
    protected $postalCode;

    /**
     * @var string|null
     */
    protected $org;

    /**
     * @var string|null
     */
    protected $isp;

    /**
     * @var string|null
     */
    protected $asn;

    /**
     * @var string|null
     */
    protected $asnName;

    /**
     * @var string|null
     */
    protected $connectionType;

    /**
     * @var bool|null
     */
    protected $isMobile;

    /**
     * @var bool|null
     */
    protected $isProxy;

    /**
     * @var bool|null
     */
    protected $isCrawler;

    /**
     * @var bool|null
     */
    protected $isTor;

    /**
     * @var string|null
     */
    protected $hostname;

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
     * Get the postal code for the IP address.
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Get the organization for the IP address.
     *
     * @return string|null
     */
    public function getOrg(): ?string
    {
        return $this->org;
    }

    /**
     * Get the timezone offset in hours from UTC.
     *
     * @return int|null
     */
    public function getTimezoneOffset(): ?int
    {
        return $this->timezoneOffset;
    }

    /**
     * Get the currency name for the IP address location.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Get the currency code for the IP address location.
     *
     * @return string|null
     */
    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * Get the currency symbol for the IP address location.
     *
     * @return string|null
     */
    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    /**
     * Get the continent name for the IP address location.
     *
     * @return string|null
     */
    public function getContinent(): ?string
    {
        return $this->continent;
    }

    /**
     * Get the continent code for the IP address location.
     *
     * @return string|null
     */
    public function getContinentCode(): ?string
    {
        return $this->continentCode;
    }

    /**
     * Get the ISP for the IP address.
     *
     * @return string|null
     */
    public function getIsp(): ?string
    {
        return $this->isp;
    }

    /**
     * Get the ASN for the IP address.
     *
     * @return string|null
     */
    public function getAsn(): ?string
    {
        return $this->asn;
    }

    /**
     * Get the ASN organization name for the IP address.
     *
     * @return string|null
     */
    public function getAsnName(): ?string
    {
        return $this->asnName;
    }

    /**
     * Get the connection type for the IP address.
     *
     * @return string|null
     */
    public function getConnectionType(): ?string
    {
        return $this->connectionType;
    }

    /**
     * Check if the IP address is from a mobile connection.
     *
     * @return bool|null
     */
    public function isMobile(): ?bool
    {
        return $this->isMobile;
    }

    /**
     * Check if the IP address is from a proxy.
     *
     * @return bool|null
     */
    public function isProxy(): ?bool
    {
        return $this->isProxy;
    }

    /**
     * Check if the IP address is from a crawler/bot.
     *
     * @return bool|null
     */
    public function isCrawler(): ?bool
    {
        return $this->isCrawler;
    }

    /**
     * Check if the IP address is from a Tor exit node.
     *
     * @return bool|null
     */
    public function isTor(): ?bool
    {
        return $this->isTor;
    }

    /**
     * Get the hostname for the IP address.
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /**
     * Get formatted full address string.
     *
     * @return string|null
     */
    public function getFormattedAddress(): ?string
    {
        $parts = array_filter([
            $this->city,
            $this->region,
            $this->country
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get short formatted address string.
     *
     * @return string|null
     */
    public function getShortAddress(): ?string
    {
        $parts = array_filter([
            $this->city,
            $this->countryCode
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get full address including postal code.
     *
     * @return string|null
     */
    public function getFullAddress(): ?string
    {
        $addressParts = array_filter([
            $this->city,
            $this->region,
            $this->postalCode,
            $this->countryCode
        ]);

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    /**
     * Get Google Maps link for the coordinates.
     *
     * @return string|null
     */
    public function getGoogleMapsLink(): ?string
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return "https://maps.google.com/?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Get OpenStreetMap link for the coordinates.
     *
     * @return string|null
     */
    public function getOpenStreetMapLink(): ?string
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}&zoom=12";
    }

    /**
     * Get Apple Maps link for the coordinates.
     *
     * @return string|null
     */
    public function getAppleMapsLink(): ?string
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return "maps://maps.apple.com/?ll={$this->latitude},{$this->longitude}";
    }

    /**
     * Get country flag emoji.
     *
     * @return string|null
     */
    public function getCountryFlag(): ?string
    {
        return $this->getCountryFlagEmoji();
    }

    /**
     * Get country flag emoji.
     *
     * @return string|null
     */
    public function getCountryFlagEmoji(): ?string
    {
        if (!$this->countryCode || strlen($this->countryCode) !== 2) {
            return null;
        }

        $countryCode = strtoupper($this->countryCode);

        // More compatible approach using predefined regional indicators
        $regionalIndicators = [
            'A' => '🇦', 'B' => '🇧', 'C' => '🇨', 'D' => '🇩', 'E' => '🇪',
            'F' => '🇫', 'G' => '🇬', 'H' => '🇭', 'I' => '🇮', 'J' => '🇯',
            'K' => '🇰', 'L' => '🇱', 'M' => '🇲', 'N' => '🇳', 'O' => '🇴',
            'P' => '🇵', 'Q' => '🇶', 'R' => '🇷', 'S' => '🇸', 'T' => '🇹',
            'U' => '🇺', 'V' => '🇻', 'W' => '🇼', 'X' => '🇽', 'Y' => '🇾', 'Z' => '🇿'
        ];

        $first = $regionalIndicators[$countryCode[0]] ?? '';
        $second = $regionalIndicators[$countryCode[1]] ?? '';

        return $first && $second ? $first . $second : null;
    }

    /**
     * Check if the location data is valid/complete.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->ip) &&
               !empty($this->countryCode) &&
               !empty($this->latitude) &&
               !empty($this->longitude);
    }

    /**
     * Check if IP is IPv4.
     *
     * @return bool
     */
    public function isIPv4(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check if IP is IPv6.
     *
     * @return bool
     */
    public function isIPv6(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Get country flag URL from flagcdn.com.
     *
     * @param int $width Width of the flag image (default: 320)
     * @return string|null
     */
    public function getCountryFlagUrl(int $width = 320): ?string
    {
        if (!$this->countryCode) {
            return null;
        }

        $code = strtolower($this->countryCode);
        return "https://flagcdn.com/w{$width}/{$code}.png";
    }

    /**
     * Parses the raw response.
     *
     * @param  mixed $data
     */
    protected function parse($data): void
    {
        // Handle string data (JSON)
        if (is_string($data)) {
            try {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                } else {
                    return; // Invalid JSON, skip parsing
                }
            } catch (\Exception $e) {
                return; // JSON parsing failed, skip
            }
        }

        // Handle GeolocationDetails objects
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        // Handle stdClass objects
        if ($data instanceof \stdClass) {
            $data = (array) $data;
        }

        // Ensure we have an array to work with
        if (!is_array($data)) {
            return;
        }

        // Set IP if present in data (handle different IP field names)
        if (isset($data['ip']) || isset($data['query'])) {
            $this->ip = $data['ip'] ?? $data['query'] ?? null;
        }

        foreach ((array) $data as $key => $value) {
            // Skip null keys or non-string keys
            if (!is_string($key) || $key === '') {
                continue;
            }

            if (property_exists($this, $key)) {
                if ($key === 'country') {
                    $this->countryCode = $value;
                    $this->{$key} = $this->formatCountry($value);
                } else {
                    $this->{$key} = $value;
                }
            }

            if ($key === 'loc' && is_string($value)) {
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
        if (!is_string($value) || empty($value) || !str_contains($value, ',')) {
            return;
        }

        $coordinates = explode(',', $value, 2);

        if (count($coordinates) !== 2) {
            return;
        }

        $latitude = trim($coordinates[0]);
        $longitude = trim($coordinates[1]);

        // Validate that the coordinates are numeric
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return;
        }

        $this->latitude = (float) $latitude;
        $this->longitude = (float) $longitude;
    }

    /**
     * Format the country name.
     *
     * @param  string $countryCode
     *
     * @return string
     */
    protected function formatCountry($countryCode)
    {
        if (empty($countryCode) || !is_string($countryCode)) {
            return $countryCode ?? '';
        }

        // First try to get from config/countries if it exists
        try {
            if (function_exists('trans') && function_exists('app') && app()->bound('translator')) {
                $translator = app('translator');
                if ($translator->has('geolocation::countries')) {
                    $countries = trans('geolocation::countries');
                    if (is_array($countries) && array_key_exists($countryCode, $countries)) {
                        return $countries[$countryCode];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fall back to built-in countries if translation fails
        }

        // Fallback: Use a built-in array or return the code
        $builtInCountries = $this->getBuiltInCountries();
        return $builtInCountries[$countryCode] ?? $countryCode;
    }

    /**
     * Get built-in country mappings as fallback.
     *
     * @return array
     */
    private function getBuiltInCountries(): array
    {
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'IE' => 'Ireland',
            'PT' => 'Portugal',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'CN' => 'China',
            'IN' => 'India',
            'RU' => 'Russia',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'ZA' => 'South Africa',
            'EG' => 'Egypt',
            'NG' => 'Nigeria',
            'KE' => 'Kenya',
            'TZ' => 'Tanzania'
        ];
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
            'timezoneOffset' => $this->timezoneOffset,
            'currency' => $this->currency,
            'currencyCode' => $this->currencyCode,
            'currencySymbol' => $this->currencySymbol,
            'continent' => $this->continent,
            'continentCode' => $this->continentCode,
            'postalCode' => $this->postalCode,
            'org' => $this->org,
            'isp' => $this->isp,
            'asn' => $this->asn,
            'asnName' => $this->asnName,
            'connectionType' => $this->connectionType,
            'isMobile' => $this->isMobile,
            'isProxy' => $this->isProxy,
            'isCrawler' => $this->isCrawler,
            'isTor' => $this->isTor,
            'hostname' => $this->hostname,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Magic getter for convenience.
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \InvalidArgumentException("Property {$name} does not exist.");
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFormattedAddress() ?? $this->getIp() ?? '';
    }

    /**
     * ArrayAccess implementation - check if offset exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset) || method_exists($this, 'get' . ucfirst($offset));
    }

    /**
     * ArrayAccess implementation - get offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (method_exists($this, 'get' . ucfirst($offset))) {
            return $this->{'get' . ucfirst($offset)}();
        }

        return $this->$offset ?? null;
    }

    /**
     * ArrayAccess implementation - set offset (immutable).
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws \RuntimeException
     */
    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException('GeolocationDetails is immutable');
    }

    /**
     * ArrayAccess implementation - unset offset (immutable).
     *
     * @param mixed $offset
     * @throws \RuntimeException
     */
    public function offsetUnset($offset): void
    {
        throw new \RuntimeException('GeolocationDetails is immutable');
    }
}
