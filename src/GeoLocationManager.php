<?php

namespace Adrianorosa\GeoLocation;

use GeoIp2\Database\Reader;
use GuzzleHttp\Client;
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
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cacheProvider;

    /**
     * GeoLocation constructor.
     *
     * @param  array $config
     * @param  \Illuminate\Cache\CacheManager $cacheProvider
     */
    public function __construct($config, \Illuminate\Cache\CacheManager $cacheProvider)
    {
        $this->config = $config;
        $this->cacheProvider = $cacheProvider;

        $this->setDefaultDriver();
    }

    /**
     * Get a GeoLocation driver instance.
     *
     * @param $name
     *
     * @return mixed
     */
    public function driver($name)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->providers[$name] = $this->provider($name);
    }

    /**
     * @param  null $name
     */
    protected function setDefaultDriver($name = null)
    {
        $provider = $name ?? $this->getDefaultDriver();

        if ($provider) {
            $this->providers[$name] = $this->resolve($provider);
        }
    }

    /**
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return $this->config['drivers']['default'];
    }

    /**
     * @param  array $config
     *
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     */
    protected function createIpinfoDriver($config)
    {
        $options = $config['client_options'] ?? [];

        return new Providers\IpInfo(new Client($options), $this->cacheProvider->getStore());
    }

    /**
     * @param  array  $config
     *
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     * @throws InvalidDatabaseException
     */
    protected function createMaxmindDriver($config)
    {
        $databasePath = $config['database_path'] ?? null;

        if (!$databasePath) {
            throw new InvalidArgumentException("MaxMind database path is not configured.");
        }

        // Resolve the path properly - handle both absolute and relative paths
        if (!str_starts_with($databasePath, '/')) {
            // If it's a relative path, resolve it from storage_path or base_path
            $databasePath = storage_path($databasePath);
        }

        if (!file_exists($databasePath)) {
            throw new InvalidArgumentException(
                "MaxMind database not found at: {$databasePath}. " .
                "Please download the database from https://dev.maxmind.com/geoip/geolite2-free-geolocation-data " .
                "and place it in the specified location."
            );
        }

        if (!is_readable($databasePath)) {
            throw new InvalidArgumentException(
                "MaxMind database is not readable: {$databasePath}. " .
                "Check file permissions."
            );
        }

        $reader = new Reader($databasePath);

        return new Providers\MaxMind($reader, $this->cacheProvider->getStore());
    }

    /**
     * @param  null $name
     *
     * @return mixed
     */
    protected function provider($name = null)
    {
        return $this->providers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Adrianorosa\GeoLocation\Contracts\LookupInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("GeoLocation Driver [{$name}] is not defined.");
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        } else {
            throw new InvalidArgumentException("GeoLocation Driver [{$config['driver']}] is not supported.");
        }
    }

    /**
     * @param $name
     *
     * @return string|null
     */
    protected function getConfig($name)
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
