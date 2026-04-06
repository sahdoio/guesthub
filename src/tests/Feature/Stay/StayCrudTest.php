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
    public function itListsStaysForOwner(): void
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
    public function itShowsCreateStayForm(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/stays/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Stays/Create')
        );
    }

    #[Test]
    public function itCreatesAStay(): void
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
            'account_uuid' => $this->account->uuid,
        ]);
    }

    #[Test]
    public function itValidatesStayNameIsRequired(): void
    {
        $response = $this->actingAs($this->actor)
            ->post('/stays', [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function itShowsAStay(): void
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
    public function itShowsEditForm(): void
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
    public function itUpdatesAStay(): void
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
    public function itReturns404ForUnknownSlug(): void
    {
        $this->actingAs($this->actor)
            ->get('/stays/nonexistent-stay')
            ->assertStatus(404);
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        $this->get('/stays')
            ->assertRedirect('/login');
    }

    #[Test]
    public function itBlocksGuestActors(): void
    {
        $guest = $this->createGuestActor();

        $this->actingAs($guest)
            ->get('/stays')
            ->assertRedirect('/login');
    }
}
