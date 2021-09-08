<?php

namespace Tests\Feature\Http\Api\V1;

use App\Jobs\SyncSalesforceContactJob;
use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_only_allows_authenticated_users()
    {
        Auth::logout();

        $request = $this->getJson(route('api.v1.contact.index'));
        $request->assertStatus(401);
    }

    /** @test */
    public function it_list_paginated_contacts()
    {
        Contact::withoutEvents(fn() => Contact::factory()->times(6)->create());

        // This is just to ensure that pagination is configurable
        $this->app['config']->set('system.pagination_amount', 5);


        $request = $this->getJson(route('api.v1.contact.index'));


        $request->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'first_name',
                        'last_name',
                        'full_name',
                        'email',
                        'phone_number',
                        'lead_source',
                    ]
                ]
            ])
            ->assertJson(
                fn (AssertableJson $json) =>
                $json
                    ->has('data', 5)
                    ->has(
                        'data.0',
                        fn ($json) =>
                        $json->missing('salesforce_id')
                            ->etc()
                    )->etc()

            );
    }

    /** @test */
    public function it_can_create_a_new_contact()
    {
        Queue::fake();
        Http::fake([config('salesforce.base_api_url') .'*']);

        $this->assertEquals(0, Contact::count());
        $response = $this->postJson(route('api.v1.contact.store'), [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->email,
            'phone_number' => $this->faker->phoneNumber,
            'lead_source' => $this->faker->word,
        ]);

        Queue::assertPushed(function(SyncSalesforceContactJob $job) {
            return $job->type === 'created';
        });

        $response->assertStatus(201);

        $this->assertEquals(1, Contact::count());
    }

    /** @test */
    public function it_can_update_a_contact()
    {
        Queue::fake();
        Http::fake([config('salesforce.base_api_url') .'*']);

        $contact = Contact::factory()->create([
            'first_name' => 'Nikola',
            'last_name' => 'Susa',
            'email' => 'nikola.susa@omure.com'
        ]);



        $this->assertEquals('Nikola Susa', $contact->full_name);
        $this->assertEquals('nikola.susa@omure.com', $contact->email);


        $response = $this->putJson(route('api.v1.contact.update', $contact), [
            'first_name' => 'Viktor',
            'last_name' => 'Ryshkov',
            'email' => 'viktor.ryshkov@omure.com',
        ]);

        Queue::assertPushed(function(SyncSalesforceContactJob $job) {
            return $job->type === 'updated';
        });

        $response->assertOk();

        $contact->refresh();
        $this->assertEquals('Viktor Ryshkov', $contact->full_name);
        $this->assertEquals('viktor.ryshkov@omure.com', $contact->email);
    }

    /** @test */
    public function it_validate_required_fields()
    {
        $this->assertEquals(0, Contact::count());

        $response = $this->postJson(route('api.v1.contact.store'), [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone_number' => '',
            'lead_source' => '',
        ]);
        $this->assertEquals(0, Contact::count());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['last_name', 'email']);
        $response->assertJsonMissingValidationErrors(['first_name', 'phone_number', 'lead_source']);

        /**
         * Updating
         */
        Contact::withoutEvents(fn() => Contact::factory()->create(['email' => 'some.known@email.com']));
        $contact = Contact::where('email', 'some.known@email.com')->first();

        $response = $this->putJson(route('api.v1.contact.update', $contact), [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone_number' => '',
            'lead_source' => '',
        ]);

        $this->assertEquals(1, Contact::count());
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['last_name', 'email']);
        $response->assertJsonMissingValidationErrors(['first_name', 'phone_number', 'lead_source']);

        /**
         * Creating with duplicate email
         */
        $response = $this->postJson(route('api.v1.contact.store'), [
            'last_name' => 'Doe',
            'email' => 'some.known@email.com'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_show_a_single_contact()
    {
        Contact::withoutEvents(fn() => Contact::factory()->create(['email' => 'some.known@email.com']));
        $contact = Contact::where('email', 'some.known@email.com')->first();

        $request = $this->getJson(route('api.v1.contact.show', $contact));

        $request->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'contact_id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'phone_number',
                    'lead_source',
                ]
            ]);

        $this->assertEquals($contact->id, $request->getData()->data->contact_id);
        $this->assertEquals('some.known@email.com', $request->getData()->data->email);
    }

    /** @test */
    public function it_can_delete_a_contact()
    {
        Queue::fake();
        Http::fake([config('salesforce.base_api_url') .'*']);

        $contact = Contact::factory()->create();

        $this->assertEquals(1, Contact::count());

        $request = $this->deleteJson(route('api.v1.contact.delete', $contact));
        $request->assertOk();

        Queue::assertPushed(function(SyncSalesforceContactJob $job) {
            return $job->type === 'deleted';
        });

        $this->assertEquals(0, Contact::count());
    }

    /** @test */
    public function it_can_sync_contacts_from_salesforce()
    {
        Queue::fake();
        Http::fake([config('salesforce.base_api_url') .'*']);

        Contact::factory()->create([
            'first_name' => 'Nikola',
            'last_name' => 'Susa',
            'email' => 'wrongemail@to_be_updated.com',
            'salesforce_id' => 'sf-id-2'
        ]);

        $this->assertEquals(1, Contact::count());

        Cache::put('salesforce_token', 'some-token', config('salesforce.token_ttl'));

        Http::fake([config('salesforce.base_api_url') . 'contacts/' => Http::response([
            "records" => [
                [
                    "description" => null,
                    "email" => "viktor.ryshkov@omure.com",
                    "first_name" => "Viktor",
                    "id" => "sf-id-1",
                    "is_deleted" => false,
                    "last_name" => "Ryshkov",
                    "lead_source" => "Omure",
                    "title" => "CEO"
                ],
                [
                    "description" => null,
                    "email" => "nikola.susa@omure.com",
                    "first_name" => "Nikola",
                    "id" => "sf-id-2",
                    "is_deleted" => false,
                    "last_name" => "Susa",
                    "lead_source" => "Omure",
                    "title" => "CTO"
                ],
            ],
            "status" => true,
            "total" => 2
        ])]);

        $request = $this->postJson(route('api.v1.contact.sync'));

        Queue::assertPushed(function(SyncSalesforceContactJob $job) {
            return $job->type === 'created';
        });
        Queue::assertPushed(function(SyncSalesforceContactJob $job) {
            return $job->type === 'updated';
        });

        $request->assertOk();

        $contacts = Contact::all();
        $this->assertEquals(2, $contacts->count());
        $this->assertEquals('nikola.susa@omure.com', $contacts->where('salesforce_id', 'sf-id-2')->first()->email);
        $this->assertEquals('viktor.ryshkov@omure.com', $contacts->where('salesforce_id', 'sf-id-1')->first()->email);
    }
}
