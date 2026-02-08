<?php

namespace Bkhim\Geolocation;

use GeoIp2\Database\Reader;
use GuzzleHttp\Client;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Class GeolocationManager.
 *
 * @author Adriano Rosa <https://adrianorosa.com>
 * @date 2019-08-13 11:50
 *
 * @package Bkhim\Geolocation
 */
class GeolocationManager
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
     * @var CacheManager
     */
    protected $cacheProvider;

    /**
     * Geolocation constructor.
     *
     * @param  array  $config
     * @param  \Illuminate\Contracts\Cache\Repository  $cacheProvider
     */
    public function __construct($config, \Illuminate\Contracts\Cache\Repository $cacheProvider)
    {
        $this->config = $config;
        $this->cacheProvider = $cacheProvider;

        $this->setDefaultDriver();
    }

    /**
     * Get a Geolocation driver instance.
     *
     * @param string|null $name
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
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
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
     */
    protected function createIpinfoDriver($config)
    {
        // Use Laravel's HTTP client instead of raw Guzzle
        return new Providers\IpInfo(
            $this->cacheProvider
        );
    }

    /**
     * Create MaxMind driver instance.
     *
     * @param  array  $config
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
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
            return new Providers\MaxMind($reader, $this->cacheProvider->store());

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
     * Create IPStack driver instance.
     *
     * @param  array $config
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
     */
    protected function createIpstackDriver($config)
    {
        return new Providers\IpStack(
            $this->cacheProvider->store()
        );
    }

    /**
     * Create IPGeolocation driver instance.
     *
     * @param  array $config
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
     */
    protected function createIpgeolocationDriver($config)
    {
        return new Providers\IpGeolocation(
            $this->cacheProvider->store()
        );
    }

    /**
     * Create IP-API driver instance.
     *
     * @param  array $config
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
     */
    protected function createIpapiDriver($config)
    {
        return new Providers\IpApi(
            $this->cacheProvider
        );
    }


    /**
     * Get a provider instance.
     *
     * @param string|null $name
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
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
     * @return \Bkhim\Geolocation\Contracts\LookupInterface
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Contracts\LookupInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Geolocation Driver [{$name}] is not defined.");
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Geolocation Driver [{$config['driver']}] is not supported.");
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
