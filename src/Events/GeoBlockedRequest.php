<?php

namespace Bkhim\Geolocation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class GeoBlockedRequest.
 */
class GeoBlockedRequest
{
    use Dispatchable, SerializesModels;

    /**
     * The user instance.
     */
    public mixed $user;

    /**
     * The IP address that was blocked.
     */
    public string $ip;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(mixed $user, string $ip)
    {
        $this->user = $user;
        $this->ip = $ip;
    }
}
