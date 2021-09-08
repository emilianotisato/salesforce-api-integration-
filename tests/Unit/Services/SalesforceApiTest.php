<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SalesforceApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SalesforceApiTest extends TestCase
{
    /** @test */
    public function it_obteins_the_auth_token_from_cache_or_renew_it()
    {
        $this->app['config']->set('salesforce.token_ttl', 600); // 10 minutes

        Cache::shouldReceive('get')
            ->once()
            ->with('salesforce_token')
            ->andReturn(null);

        Http::fake([config('salesforce.base_api_url') . 'login/' => Http::response([
            "message" => "Success",
            "token" => "some-token"
        ])]);

        Cache::shouldReceive('put')
            ->once()
            ->with('salesforce_token', 'some-token', 600);

        new SalesforceApi();
    }
}
