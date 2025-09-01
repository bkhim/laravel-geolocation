<?php

namespace Adrianorosa\GeoLocation;

use GeoIp2\Database\Reader;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Class GeoLocationManager.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 11:50
 *
 * @package Adrianorosa\GeoLocation
 */
class GeoLocationManager
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheProvider;

    /**
     * GeoLocation constructor.
     *
     * @param  array $config
     * @param  \Illuminate\Contracts\Cache\Repository $cacheProvider
     */
    public function __construct($config, CacheRepository $cacheProvider)
    {
        $this->config = $config;
        $this->cacheProvider = $cacheProvider;

        $this->setDefaultDriver();
    }

    /**
     * Get a GeoLocation driver instance.
     *
     * @param string|null $name
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     */
    public function driver(?string $name = null): Contracts\LookupInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->providers[$name] = $this->provider($name);
    }

    /**
     * Set the default driver.
     *
     * @param string|null $name
     * @return void
     */
    protected function setDefaultDriver(?string $name = null): void
    {
        $provider = $name ?? $this->getDefaultDriver();

        if ($provider) {
            $this->providers[$provider] = $this->resolve($provider);
        }
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    protected function getDefaultDriver(): string
    {
        return $this->config['drivers']['default'];
    }

    /**
     * Create IpInfo driver instance.
     *
     * @param  array $config
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     */
    protected function createIpinfoDriver(array $config): Contracts\LookupInterface
    {
        $options = $config['client_options'] ?? [];

        return new Providers\IpInfo(new Client($options), $this->cacheProvider);
    }

    /**
     * Create MaxMind driver instance.
     *
     * @param  array  $config
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     * @throws InvalidArgumentException
     */
    protected function createMaxmindDriver(array $config): Contracts\LookupInterface
    {
        $databasePath = $config['database_path'] ?? null;

        if (!$databasePath) {
            throw new InvalidArgumentException("MaxMind database path is not configured.");
        }

        // Resolve relative paths
        if (!Str::startsWith($databasePath, '/')) {
            $databasePath = storage_path($databasePath);
        }

        if (!file_exists($databasePath)) {
            throw new InvalidArgumentException(
                "MaxMind database not found at: {$databasePath}. " .
                "Please download from: https://dev.maxmind.com/geoip/geolite2-free-geolocation-data"
            );
        }

        if (!is_readable($databasePath)) {
            throw new InvalidArgumentException(
                "MaxMind database is not readable: {$databasePath}. " .
                "Check file permissions. Current: " . substr(sprintf('%o', fileperms($databasePath)), -4)
            );
        }

        try {
            $reader = new Reader($databasePath);
            return new Providers\MaxMind($reader, $this->cacheProvider);

        } catch (InvalidDatabaseException $e) {
            throw new InvalidArgumentException(
                "MaxMind database is corrupt or invalid: " . $e->getMessage()
            );
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                "Failed to initialize MaxMind reader: " . $e->getMessage()
            );
        }
    }

    /**
     * Get a provider instance.
     *
     * @param string|null $name
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     */
    protected function provider(?string $name = null): Contracts\LookupInterface
    {
        $name = $name ?: $this->getDefaultDriver();
        return $this->providers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given provider.
     *
     * @param  string  $name
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Contracts\LookupInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("GeoLocation Driver [{$name}] is not defined.");
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("GeoLocation Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Get configuration for a provider.
     *
     * @param string $name
     * @return array|null
     */
    protected function getConfig(string $name): ?array
    {
        return $this->config['providers'][$name] ?? null;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->provider()->$method(...$parameters);
    }
}
