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
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_list_paginated_contacts()
    {
        Contact::factory()->times(15)->create();

        $request = $this->get(route('api.v1.contact.index'));

        $request->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'lead_source',
                        'salesforce_id'
                    ]
                ]
            ]);
    }
}
