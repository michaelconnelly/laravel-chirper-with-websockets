<?php

namespace Tests\Feature;

use App\Models\Chirp;
use App\Models\User;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ChirpTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_chirps_page_is_not_accessible_to_unregistered()
    {
        $this->get(route('chirps.index'))
            ->assertRedirect(route('login'));
    }

    public function test_chirps_page_renders_for_registered_user()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('chirps.index'))
            ->assertOk();
    }

    public function test_chirp_can_be_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('chirps.store'), [
                'message' => 'Test Chirp Message'
            ])
            ->assertRedirect(route('chirps.index'));

        $this->assertCount(1, $user->chirps);

        $this->assertDatabaseCount(Chirp::class, 1);

        $this->assertDatabaseHas(Chirp::class, [
            'user_id' => $user->id,
            'message' => 'Test Chirp Message'
        ]);
    }

    public function test_chirps_index_view_receives_chirps()
    {
        $user = User::factory()->create();

        $user->chirps()->create([
            'message' => 'Test chirp message'
        ]);

        $this->actingAs($user)
            ->get(route('chirps.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chirps/Index')
                ->has('chirps', 1)
            );
    }

    public function test_chirps_are_displayed()
    {
        $user = User::factory()->create();

        $user->chirps()->create([
            'message' => 'Test chirp message'
        ]);

        $this->actingAs($user)
            ->get(route('chirps.index'))
            ->assertSee('Test chirp message');
    }
}
