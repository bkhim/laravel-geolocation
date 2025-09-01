<?php

namespace Adrianorosa\GeoLocation\Tests;

use Illuminate\Support\Facades\Http;

abstract class HttpMockTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Always fake HTTP responses
        Http::fake();

        // Mock successful response by default
        $this->mockSuccessfulResponse();
    }

    protected function mockSuccessfulResponse($ip = '8.8.8.8')
    {
        Http::fake([
            "ipinfo.io/{$ip}/geo" => Http::response([
                'ip' => $ip,
                'city' => 'Mountain View',
                'region' => 'California',
                'country' => 'US',
                'loc' => '37.3860,-122.0838'
            ])
        ]);
    }

    protected function mockErrorResponse($statusCode, $ip = '8.8.8.8')
    {
        Http::fake([
            "ipinfo.io/{$ip}/geo" => Http::response([], $statusCode)
        ]);
    }
}
