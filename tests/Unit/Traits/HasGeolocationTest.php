<?php

namespace Bkhim\Geolocation\Tests\Unit\Traits;

use Bkhim\Geolocation\Traits\HasGeolocation;
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;
use Bkhim\Geolocation\Traits\HasGeolocationPreferences;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Test model that uses all three traits
 */
class TestUser extends Model
{
    use HasGeolocation, HasGeolocationSecurity, HasGeolocationPreferences;

    protected $table = 'users';
    public $incrementing = true;
    protected $keyType = 'int';
    
    public function getKey()
    {
        return $this->id;
    }
}

/**
 * Class HasGeolocationTest.
 *
 * @package Bkhim\Geolocation\Tests\Unit\Traits
 */
class HasGeolocationTest extends BaseTestCase
{
    /** @test */
    public function it_can_load_all_traits_without_errors()
    {
        // This test verifies that all traits can be used together without errors
        $user = new TestUser();
        $user->id = 1;
        
        // Verify the traits are loaded by checking method existence
        $this->assertTrue(method_exists($user, 'recordLoginLocation'));
        $this->assertTrue(method_exists($user, 'getLastLogin'));
        $this->assertTrue(method_exists($user, 'getLastLoginCountry'));
        $this->assertTrue(method_exists($user, 'isLoginFromNewCountry'));
        $this->assertTrue(method_exists($user, 'requiresMfaDueToLocation'));
        $this->assertTrue(method_exists($user, 'getSuspiciousLoginCount'));
        $this->assertTrue(method_exists($user, 'getLastLoginRiskLevel'));
        $this->assertTrue(method_exists($user, 'getDetectedTimezone'));
        $this->assertTrue(method_exists($user, 'getLocalCurrency'));
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_use_core_traits_individually()
    {
        // Test HasGeolocation alone
        $user1 = new class extends Model
        {
            use HasGeolocation;
            
            protected $table = 'users';
            public $id = 1;
            
            public function getKey()
            {
                return $this->id;
            }
        };
        
        $this->assertTrue(method_exists($user1, 'recordLoginLocation'));
        $this->assertTrue(method_exists($user1, 'getLastLogin'));
        
        // Test HasGeolocationSecurity alone
        $user2 = new class extends Model
        {
            use HasGeolocationSecurity;
            
            protected $table = 'users';
            public $id = 1;
            
            public function getKey()
            {
                return $this->id;
            }
        };
        
        $this->assertTrue(method_exists($user2, 'requiresMfaDueToLocation'));
        $this->assertTrue(method_exists($user2, 'getSuspiciousLoginCount'));
        
        // Test HasGeolocationPreferences alone
        $user3 = new class extends Model
        {
            use HasGeolocationPreferences;
            
            protected $table = 'users';
            public $id = 1;
            
            public function getKey()
            {
                return $this->id;
            }
        };
        
        $this->assertTrue(method_exists($user3, 'getDetectedTimezone'));
        $this->assertTrue(method_exists($user3, 'getLocalCurrency'));
        
        $this->assertTrue(true);
    }
}