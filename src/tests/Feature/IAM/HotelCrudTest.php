<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class HotelCrudTest extends TestCase
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
    public function it_lists_hotels_for_owner(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/hotels');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hotels/Index')
            ->has('hotels', 1)
            ->where('hotels.0.name', 'Test Hotel')
        );
    }

    #[Test]
    public function it_shows_create_hotel_form(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/hotels/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hotels/Create')
        );
    }

    #[Test]
    public function it_creates_a_hotel(): void
    {
        $response = $this->actingAs($this->actor)
            ->post('/hotels', [
                'name' => 'New Grand Hotel',
                'description' => 'A lovely hotel',
                'address' => '123 Main St',
                'contact_email' => 'info@grand.com',
                'contact_phone' => '1234567890',
            ]);

        $response->assertRedirect('/hotels');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hotels', [
            'name' => 'New Grand Hotel',
            'account_id' => $this->account->id,
        ]);
    }

    #[Test]
    public function it_validates_hotel_name_is_required(): void
    {
        $response = $this->actingAs($this->actor)
            ->post('/hotels', [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_shows_a_hotel(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/hotels/test-hotel');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hotels/Show')
            ->where('hotel.name', 'Test Hotel')
        );
    }

    #[Test]
    public function it_shows_edit_form(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/hotels/test-hotel/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hotels/Edit')
            ->where('hotel.name', 'Test Hotel')
        );
    }

    #[Test]
    public function it_updates_a_hotel(): void
    {
        $response = $this->actingAs($this->actor)
            ->put('/hotels/test-hotel', [
                'name' => 'Updated Hotel Name',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hotels', [
            'name' => 'Updated Hotel Name',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function it_returns_404_for_unknown_slug(): void
    {
        $this->actingAs($this->actor)
            ->get('/hotels/nonexistent-hotel')
            ->assertStatus(404);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->get('/hotels')
            ->assertRedirect('/login');
    }

    #[Test]
    public function it_blocks_guest_actors(): void
    {
        $guest = $this->createGuestActor();

        $this->actingAs($guest)
            ->get('/hotels')
            ->assertRedirect('/login');
    }
}
