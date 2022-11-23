<?php

namespace Tests\Feature;

use App\Events\ChirpCreated;
use App\Listeners\SendChirpCreatedNotifications;
use App\Models\Chirp;
use App\Models\User;
use App\Notifications\NewChirp;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

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
                'message' => 'Test Chirp Message',
            ])
            ->assertRedirect(route('chirps.index'));

        $this->assertCount(1, $user->chirps);

        $this->assertDatabaseCount(Chirp::class, 1);

        $this->assertDatabaseHas(Chirp::class, [
            'user_id' => $user->id,
            'message' => 'Test Chirp Message',
        ]);
    }

    public function test_chirps_index_view_receives_chirps()
    {
        $user = User::factory()->create();

        $user->chirps()->create([
            'message' => 'Test chirp message',
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
            'message' => 'Test chirp message',
        ]);

        $this->actingAs($user)
            ->get(route('chirps.index'))
            ->assertSee('Test chirp message');
    }

    public function test_chirps_can_be_updated()
    {
        $user = User::factory()->create();

        $chirp = $user->chirps()->create([
            'message' => 'Test chirp message',
        ]);

        $this->actingAs($user)
            ->put(route('chirps.update', $chirp), [
                'message' => 'Updated chirp message',
            ])
            ->assertRedirect(route('chirps.index'));

        $this->assertDatabaseCount(Chirp::class, 1);

        $this->assertDatabaseHas(Chirp::class, [
            'user_id' => $user->id,
            'message' => 'Updated chirp message',
        ]);
    }

    public function test_chirp_can_only_be_updated_by_owner()
    {
        $authorisedChirper = User::factory()->create();

        $chirp = $authorisedChirper->chirps()->create([
            'message' => 'Test chirp message',
        ]);

        $unauthorisedChirper = User::factory()->create();

        $this->actingAs($unauthorisedChirper)
            ->put(route('chirps.update', $chirp), [
                'message' => 'Updated chirp message',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount(Chirp::class, 1);

        $this->assertDatabaseHas(Chirp::class, [
            'user_id' => $authorisedChirper->id,
            'message' => 'Test chirp message',
        ]);
    }

    public function test_chirps_can_be_deleted()
    {
        $user = User::factory()->create();

        $chirp = $user->chirps()->create([
            'message' => 'Test chirp message',
        ]);

        $this->actingAs($user)
            ->delete(route('chirps.destroy', $chirp))
            ->assertRedirect(route('chirps.index'));

        $this->assertDatabaseCount(Chirp::class, 0);

        $this->assertDatabaseMissing(Chirp::class, [
            'user_id' => $user->id,
            'message' => 'Updated chirp message',
        ]);
    }

    public function test_chirp_can_only_be_deleted_by_owner()
    {
        $authorisedChirper = User::factory()->create();

        $chirp = $authorisedChirper->chirps()->create([
            'message' => 'Test chirp message',
        ]);

        $unauthorisedChirper = User::factory()->create();

        $this->actingAs($unauthorisedChirper)
            ->delete(route('chirps.destroy', $chirp))
            ->assertForbidden();

        $this->assertDatabaseCount(Chirp::class, 1);

        $this->assertDatabaseHas(Chirp::class, [
            'user_id' => $authorisedChirper->id,
            'message' => 'Test chirp message',
        ]);
    }

    public function test_event_is_dispatched_when_chirp_created()
    {
        Event::fake();

        $chirper = User::factory()->create();

        $chirp = $chirper->chirps()->create([
            'message' => 'Test chirp message',
        ]);

        Event::assertDispatched(ChirpCreated::class);
    }

    public function test_send_chirp_created_notifications_listens_for_chirp_created_event()
    {
        Event::fake();

        Event::assertListening(
            ChirpCreated::class,
            SendChirpCreatedNotifications::class
        );
    }

    public function test_new_chirp_notification_is_sent_when_chirp_created_event_is_dispatched()
    {
        Notification::fake();

        $user = User::factory()->create();

        $secondUser = User::factory()->create();

        $chirp = $user->chirps()->create([
            'message' => 'Chirp Message',
        ]);

        (new SendChirpCreatedNotifications())->handle(new ChirpCreated($chirp));

        Notification::assertSentTo($secondUser, NewChirp::class);
    }
}
