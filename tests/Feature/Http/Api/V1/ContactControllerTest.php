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


        $request = $this->get(route('api.v1.contact.index'));


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
    public function it_can_create_new_contacts()
    {
        $this->assertEquals(0, Contact::count());

        $response = $this->post(route('api.v1.contact.store'), [
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
    public function it_validate_required_fields()
    {
        $this->markTestIncomplete();
    }
}
