<?php

namespace Bkhim\Geolocation\Events;

use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class HighRiskIpDetected.
 *
 * @package Bkhim\Geolocation\Events
 */
class HighRiskIpDetected
{
    use Dispatchable, SerializesModels;

    /**
     * The user instance.
     */
    public mixed $user;

    /**
     * The login history record.
     */
    public LoginHistory $loginHistory;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $user
     * @param  \Bkhim\Geolocation\Models\LoginHistory  $loginHistory
     * @return void
     */
    public function __construct(mixed $user, LoginHistory $loginHistory)
    {
        $this->user = $user;
        $this->loginHistory = $loginHistory;
    }
}