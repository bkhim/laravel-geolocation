<?php

namespace Bkhim\Geolocation\Tests\Unit\Traits;

use Bkhim\Geolocation\Traits\HasGeolocationSecurity;
use Bkhim\Geolocation\Models\LoginHistory;
use Bkhim\Geolocation\Events\SuspiciousLocationDetected;
use Bkhim\Geolocation\Events\HighRiskIpDetected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

/**
 * Class HasGeolocationSecurityTest.
 *
 * @package Bkhim\Geolocation\Tests\Unit\Traits
 */
class HasGeolocationSecurityTest extends \Bkhim\Geolocation\Tests\TestCase
{
    /** @test */
    public function it_can_detect_if_mfa_is_required_due_to_location()
    {
        // Mock the geolocation API response for a normal IP
        Http::fake([
            '*' => Http::response([
                'ip' => '8.8.8.8',
                'country_code' => 'US',
            ])
        ]);

        // Create test user
        $user = $this->createTestUserWithSecurityTrait();

        // Initially no logins, so MFA should not be required for known country
        $this->assertFalse($user->requiresMfaDueToLocation('8.8.8.8'));

        // Create a login history record for US
        LoginHistory::factory()->create([
            'user_id' => $user->getKey(),
            'country_code' => 'US',
        ]);

        // Login from known US should not require MFA
        $this->assertFalse($user->requiresMfaDueToLocation('8.8.8.8'));

        // Mock the geolocation API response for a proxy IP
        Http::fake([
            '*' => Http::response([
                'ip' => '1.2.3.4',
                'country_code' => 'US',
                'is_proxy' => true,
            ])
        ]);

        // Login from proxy IP should require MFA (high risk)
        $this->assertTrue($user->requiresMfaDueToLocation('1.2.3.4'));
    }

    /** @test */
    public function it_can_get_suspicious_login_count()
    {
        // Create test user
        $user = $this->createTestUserWithSecurityTrait();

        // Initially no suspicious logins
        $this->assertEquals(0, $user->getSuspiciousLoginCount());

        // Create a normal login history record
        LoginHistory::factory()->create([
            'user_id' => $user->getKey(),
            'country_code' => 'US',
            'is_proxy' => false,
            'is_tor' => false,
        ]);

        // Still no suspicious logins
        $this->assertEquals(0, $user->getSuspiciousLoginCount());

        // Create a login history record with proxy
        LoginHistory::factory()->create([
            'user_id' => $user->getKey(),
            'country_code' => 'CN',
            'is_proxy' => true,
            'is_tor' => false,
        ]);

        // Now we have 1 suspicious login
        $this->assertEquals(1, $user->getSuspiciousLoginCount());

        // Create a login history record with Tor
        LoginHistory::factory()->create([
            'user_id' => $user->getKey(),
            'country_code' => 'RU',
            'is_proxy' => false,
            'is_tor' => true,
        ]);

        // Now we have 2 suspicious logins
        $this->assertEquals(2, $user->getSuspiciousLoginCount());
    }

    /** @test */
    public function it_can_get_last_login_risk_level()
    {
        // Create test user
        $user = $this->createTestUserWithSecurityTrait();

        // No logins, should be low risk
        $this->assertEquals('low', $user->getLastLoginRiskLevel());

        // Create a normal login history record
        LoginHistory::factory()->create([
            'user_id' => $user->getKey(),
            'country_code' => 'US',
        ]);

        // Still low risk (known country, no proxy/tor)
        $this->assertEquals('low', $user->getLastLoginRiskLevel());

        // Mock the geolocation API response for a proxy IP
        Http::fake([
            '*' => Http::response([
                'ip' => '1.2.3.4',
                'country_code' => 'US',
                'is_proxy' => true,
            ])
        ]);

        // Login from proxy IP - should be high risk
        $user->recordLoginLocation('1.2.3.4');
        $this->assertEquals('high', $user->getLastLoginRiskLevel('1.2.3.4'));

        // Mock the geolocation API response for a Tor IP
        Http::fake([
            '*' => Http::response([
                'ip' => '5.6.7.8',
                'country_code' => 'US',
                'is_tor' => true,
            ])
        ]);

        // Login from Tor IP - should be high risk
        $user->recordLoginLocation('5.6.7.8');
        $this->assertEquals('high', $user->getLastLoginRiskLevel('5.6.7.8'));

        // Mock the geolocation API response for a new country
        Http::fake([
            '*' => Http::response([
                'ip' => '9.10.11.12',
                'country_code' => 'CN', // Different country
            ])
        ]);

        // Login from new country - should be medium risk (we don't have medium, so low)
        // Actually, new country adds 2 points, which is still low risk (< 3)
        $this->assertEquals('low', $user->getLastLoginRiskLevel('9.10.11.12'));

        // Mock the geolocation API response for a new country with proxy
        Http::fake([
            '*' => Http::response([
                'ip' => '13.14.15.16',
                'country_code' => 'CN', // Different country
                'is_proxy' => true,
            ])
        ]);

        // Login from new country with proxy - 2 (new country) + 3 (proxy) = 5 = critical risk
        $user->recordLoginLocation('13.14.15.16');
        $this->assertEquals('critical', $user->getLastLoginRiskLevel('13.14.15.16'));
    }

    /** @test */
    public function it_fires_events_for_suspicious_activity()
    {
        // Mock the geolocation API response for a proxy IP
        Http::fake([
            '*' => Http::response([
                'ip' => '1.2.3.4',
                'country_code' => 'US',
                'is_proxy' => true,
            ])
        ]);

        // Create test user
        $user = $this->createTestUserWithSecurityTrait();

        // Record login location from proxy IP
        $user->recordLoginLocation('1.2.3.4');

        // Check that suspicious location detected event was fired
        Event::assertDispatched(function ($event) use ($user) {
            return $event instanceof SuspiciousLocationDetected &&
                   $event->user->getKey() === $user->getKey();
        });

        // Check that high risk IP detected event was fired
        Event::assertDispatched(function ($event) use ($user) {
            return $event instanceof HighRiskIpDetected &&
                   $event->user->getKey() === $user->getKey();
        });
    }

    /**
     * Create a test user instance with the security trait.
     *
     * @return object
     */
    protected function createTestUserWithSecurityTrait()
    {
        // Create a simple test class that uses the trait
        $testUser = new class {
            use HasGeolocationSecurity;

            public $id = 1;

            public function getKey()
            {
                return $this->id;
            }
        };

        return $testUser;
    }
}