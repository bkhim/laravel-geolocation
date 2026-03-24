<?php

namespace Bkhim\Geolocation\Tests\Unit\Traits;

use Bkhim\Geolocation\Traits\HasGeolocation;
use Bkhim\Geolocation\Traits\HasGeolocationSecurity;
use Bkhim\Geolocation\Traits\HasGeolocationPreferences;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
 * Class HasGeolocationTest
 */
class HasGeolocationTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory SQLite database
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Create the table used by the HasGeolocation trait
        Schema::create('user_login_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('ip')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('country_code')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency_code')->nullable();
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_tor')->default(false);
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        // Optional: create users table if needed
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    /** @test */
    public function it_can_load_all_traits_without_errors()
    {
        $user = new TestUser();
        $user->id = 1;

        $this->assertTrue(method_exists($user, 'recordLoginLocation'));
        $this->assertTrue(method_exists($user, 'getLastLogin'));
        $this->assertTrue(method_exists($user, 'getLastLoginCountry'));
        $this->assertTrue(method_exists($user, 'isLoginFromNewCountry'));
        $this->assertTrue(method_exists($user, 'requiresMfaDueToLocation'));
        $this->assertTrue(method_exists($user, 'getSuspiciousLoginCount'));
        $this->assertTrue(method_exists($user, 'getLastLoginRiskLevel'));
        $this->assertTrue(method_exists($user, 'getDetectedTimezone'));
        $this->assertTrue(method_exists($user, 'getLocalCurrency'));
    }

    /** @test */
    public function it_can_use_core_traits_individually()
    {
        $user1 = new class extends Model { use HasGeolocation; protected $table='users'; public $id=1; function getKey(){return $this->id;} };
        $user2 = new class extends Model { use HasGeolocationSecurity; protected $table='users'; public $id=1; function getKey(){return $this->id;} };
        $user3 = new class extends Model { use HasGeolocationPreferences; protected $table='users'; public $id=1; function getKey(){return $this->id;} };

        $this->assertTrue(method_exists($user1, 'recordLoginLocation'));
        $this->assertTrue(method_exists($user1, 'getLastLogin'));
        $this->assertTrue(method_exists($user2, 'requiresMfaDueToLocation'));
        $this->assertTrue(method_exists($user2, 'getSuspiciousLoginCount'));
        $this->assertTrue(method_exists($user3, 'getDetectedTimezone'));
        $this->assertTrue(method_exists($user3, 'getLocalCurrency'));
    }
}
