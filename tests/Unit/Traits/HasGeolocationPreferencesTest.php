<?php

namespace Bkhim\Geolocation\Tests\Unit\Traits;

use Bkhim\Geolocation\Traits\HasGeolocationPreferences;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Support\Facades\Http;

/**
 * Class HasGeolocationPreferencesTest.
 *
 * @package Bkhim\Geolocation\Tests\Unit\Traits
 */
class HasGeolocationPreferencesTest extends \Bkhim\Geolocation\Tests\TestCase
{
    /** @test */
    public function it_can_get_detected_timezone()
    {
        // Mock the geolocation API response
        Http::fake([
            '*' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'region' => 'California',
                'country_code' => 'US',
                'loc' => '37.386,-122.084',
                'timezone' => 'America/Los_Angeles',
                'currency' => 'USD',
                'org' => 'AS15169 Google LLC',
            ])
        ]);

        // Create test user
        $user = $this->createTestUserWithPreferencesTrait();

        // Record a login to set timezone
        $user->recordLoginLocation('8.8.8.8');

        // Check that we can get the detected timezone
        $this->assertEquals('America/Los_Angeles', $user->getDetectedTimezone());
    }

    /** @test */
    public function it_can_get_local_currency()
    {
        // Mock the geolocation API response
        Http::fake([
            '*' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'region' => 'California',
                'country_code' => 'US',
                'loc' => '37.386,-122.084',
                'timezone' => 'America/Los_Angeles',
                'currency' => 'USD',
                'org' => 'AS15169 Google LLC',
            ])
        ]);

        // Create test user
        $user = $this->createTestUserWithPreferencesTrait();

        // Record a login to set currency
        $user->recordLoginLocation('8.8.8.8');

        // Check that we can get the local currency
        $this->assertEquals('USD', $user->getLocalCurrency());
    }

    /** @test */
    public function it_returns_null_when_personalization_is_disabled()
    {
        // Disable personalization
        config(['geolocation.personalization.enable_timezone' => false]);
        config(['geolocation.personalization.enable_currency' => false]);

        // Create test user
        $user = $this->createTestUserWithPreferencesTrait();

        // These should return null when personalization is disabled
        $this->assertNull($user->getDetectedTimezone());
        $this->assertNull($user->getLocalCurrency());
    }

    /** @test */
    public function it_provides_fallback_values()
    {
        // Create test user with no login history
        $user = $this->createTestUserWithPreferencesTrait();

        // Should return app timezone as fallback
        $this->assertEquals(config('app.timezone', 'UTC'), $user->getTimezone());

        // Should return default currency as fallback
        $this->assertEquals('USD', $user->getCurrency('USD'));
        $this->assertEquals('EUR', $user->getCurrency('EUR'));
    }

    /** @test */
    public function it_handles_missing_timezone_or_currency_gracefully()
    {
        // Mock the geolocation API response with missing timezone/currency
        Http::fake([
            '*' => Http::response([
                'ip' => '8.8.8.8',
                'city' => 'Mountain View',
                'region' => 'California',
                'country_code' => 'US',
                'loc' => '37.386,-122.084',
                // Missing timezone and currency
            ])
        ]);

        // Create test user
        $user = $this->createTestUserWithPreferencesTrait();

        // Record a login
        $user->recordLoginLocation('8.8.8.8');

        // Should return null for missing values
        $this->assertNull($user->getDetectedTimezone());
        $this->assertNull($user->getLocalCurrency());

        // But fallback methods should still work
        $this->assertEquals(config('app.timezone', 'UTC'), $user->getTimezone());
        $this->assertEquals('USD', $user->getCurrency('USD'));
    }

    /**
     * Create a test user instance with the preferences trait.
     *
     * @return object
     */
    protected function createTestUserWithPreferencesTrait()
    {
        // Create a simple test class that uses the trait
        $testUser = new class {
            use HasGeolocationPreferences;

            public $id = 1;

            public function getKey()
            {
                return $this->id;
            }
        };

        return $testUser;
    }
}