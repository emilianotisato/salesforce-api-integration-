<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SalesforceApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SalesforceApiTest extends TestCase
{
    /** @test */
    public function it_obteins_the_auth_token_from_cache_or_renew_it_if_missing()
    {
        $this->app['config']->set('salesforce.token_ttl', 600); // 10 minutes

        Cache::shouldReceive('get')
            ->once()
            ->with('salesforce_token')
            ->andReturn(null);

        Http::fake([config('salesforce.base_api_url') . 'login/' => http::response([
            "message" => "Success",
            "token" => "some-token"
        ])]);

        Cache::shouldReceive('put')
            ->once()
            ->with('salesforce_token', 'some-token', 600);

        new SalesforceApi();
    }

    /** @test */
    public function it_fetch_a_list_of_contacts_from_salesforce()
    {
        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/' => Http::response([
            "records" => [
                [
                    "account_id" => "0015e00000F2CgJAAV",
                    "description" => null,
                    "email" => "agreen@uog.com",
                    "first_name" => "Avi",
                    "id" => "0035e00000BVJ4hAAH",
                    "is_deleted" => false,
                    "last_name" => "Green",
                    "lead_source" => "Public Relations",
                    "title" => "CFO"
                ]
            ],
            "status" => true,
            "total" => 1
        ])]);

        $salesforce = new SalesforceApi();
        $contacts = $salesforce->module('contacts')->all();

        $this->assertInstanceOf(Collection::class, $contacts);
        $this->assertEquals(1, $contacts->count());
        $this->assertEquals('0035e00000BVJ4hAAH', $contacts->first()->id);
    }

    /** @test */
    public function it_can_fetch_single_contact_from_salesforce()
    {
        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/0035e00000BVJ4hAAH/' => Http::response([
            "account_id" => "0015e00000F2CgJAAV",
            "description" => null,
            "email" => "agreen@uog.com",
            "first_name" => "Avi",
            "id" => "0035e00000BVJ4hAAH",
            "is_deleted" => false,
            "last_name" => "Green",
            "lead_source" => "Public Relations",
            "title" => "CFO"
        ])]);

        $salesforce = new SalesforceApi();
        $contact = $salesforce->module('contacts')->getById("0035e00000BVJ4hAAH");

        $this->assertEquals('agreen@uog.com', $contact->email);
    }

    /** @test */
    public function it_can_create_contact_record()
    {
        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/' => Http::response([
            "errors" => [],
            "id" => "00U5e00000AIBecEAH",
            "success" => true
        ])]);

        $salesforce = new SalesforceApi();

        $response = $salesforce->module('contacts')->create([
            "email" => "agreen@uog.com",
            "first_name" => "Avi",
            "last_name" => "Green",
            "lead_source" => "Public Relations",
            "title" => "CFO"
        ]);

        $this->assertEquals('00U5e00000AIBecEAH', $response->id);
    }

    /** @test */
    public function it_can_update_contact_record()
    {
        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/00U5e00000AIBecEAH/' => Http::response([
            "errors" => [],
            "id" => "00U5e00000AIBecEAH",
            "success" => true
        ])]);

        $salesforce = new SalesforceApi();

        $response = $salesforce->module('contacts')->update("00U5e00000AIBecEAH", [
            "lead_source" => "Public Relations",
            "title" => "Updated Title"
        ]);

        $this->assertEquals('00U5e00000AIBecEAH', $response->id);
    }

    /** @test */
    public function it_can_delete_contact_record()
    {
        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/00U5e00000AIBecEAH/' => Http::response([
            "errors" => [],
            "id" => "00U5e00000AIBecEAH",
            "success" => true
        ])]);

        $salesforce = new SalesforceApi();

        $response = $salesforce->module('contacts')->delete("00U5e00000AIBecEAH");

        $this->assertEquals('00U5e00000AIBecEAH', $response->id);
    }
}
