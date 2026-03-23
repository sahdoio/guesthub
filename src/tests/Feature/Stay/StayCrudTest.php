<?php

declare(strict_types=1);

namespace Tests\Feature\Stay;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class StayCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    private ActorModel $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();
        $this->actor = $this->createOwnerActor();
    }

    #[Test]
    public function it_lists_stays_for_owner(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/stays');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stays/Index')
            ->has('stays', 1)
            ->where('stays.0.name', 'Test Stay')
            ->where('stays.0.type', 'room')
            ->where('stays.0.category', 'hotel_room')
        );
    }

    #[Test]
    public function it_shows_create_stay_form(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/stays/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stays/Create')
        );
    }

    #[Test]
    public function it_creates_a_stay(): void
    {
        $response = $this->actingAs($this->actor)
            ->post('/stays', [
                'name' => 'Deluxe Suite',
                'type' => 'room',
                'category' => 'hotel_room',
                'price_per_night' => 450.00,
                'capacity' => 4,
                'description' => 'A luxurious suite',
            ]);

        $response->assertRedirect('/stays');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stays', [
            'name' => 'Deluxe Suite',
            'type' => 'room',
            'category' => 'hotel_room',
            'account_id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_validates_stay_name_is_required(): void
    {
        $response = $this->actingAs($this->actor)
            ->post('/stays', [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_shows_a_stay(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/stays/test-stay');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stays/Show')
            ->where('stay.name', 'Test Stay')
            ->where('stay.type', 'room')
            ->where('stay.category', 'hotel_room')
        );
    }

    #[Test]
    public function it_shows_edit_form(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/stays/test-stay/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stays/Edit')
            ->where('stay.name', 'Test Stay')
        );
    }

    #[Test]
    public function it_updates_a_stay(): void
    {
        $response = $this->actingAs($this->actor)
            ->put('/stays/test-stay', [
                'name' => 'Updated Stay Name',
                'description' => 'Updated description',
                'type' => 'room',
                'category' => 'hotel_room',
                'price_per_night' => 300.00,
                'capacity' => 3,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stays', [
            'name' => 'Updated Stay Name',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function it_returns_404_for_unknown_slug(): void
    {
        $this->actingAs($this->actor)
            ->get('/stays/nonexistent-stay')
            ->assertStatus(404);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->get('/stays')
            ->assertRedirect('/login');
    }

    #[Test]
    public function it_blocks_guest_actors(): void
    {
        $guest = $this->createGuestActor();

        $this->actingAs($guest)
            ->get('/stays')
            ->assertRedirect('/login');
    }
}
