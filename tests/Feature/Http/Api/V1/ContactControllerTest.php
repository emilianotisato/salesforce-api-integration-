<?php

namespace Tests\Feature\Http\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->markTestIncomplete();
    }

    /** @test */
    public function it_list_paginated_contacts()
    {
        Contact::factory()->times(6)->create();

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
        $this->assertEquals(0, Contact::count());

        $response = $this->postJson(route('api.v1.contact.store'), [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->email,
            'phone_number' => $this->faker->phoneNumber,
            'lead_source' => $this->faker->word,
        ]);

        $response->assertStatus(201);

        $this->assertEquals(1, Contact::count());
    }

    /** @test */
    public function it_can_update_a_contact()
    {
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
        $contact = Contact::factory()->create([
            'email' => 'some.known@email.com'
        ]);

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
        $contact = Contact::factory()->create([
            'email' => 'some.known@email.com'
        ]);

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
}
