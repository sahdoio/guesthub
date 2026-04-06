<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;
use Modules\Billing\Infrastructure\Persistence\Eloquent\LineItemModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class InvoiceCrudTest extends TestCase
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

    private function createInvoiceModel(array $overrides = []): InvoiceModel
    {
        $invoiceUuid = $overrides['uuid'] ?? Uuid::uuid7()->toString();

        $invoice = InvoiceModel::withoutGlobalScopes()->create(array_merge([
            'uuid' => $invoiceUuid,
            'account_uuid' => $this->account->uuid,
            'reservation_id' => Uuid::uuid7()->toString(),
            'guest_id' => Uuid::uuid7()->toString(),
            'status' => 'draft',
            'subtotal_cents' => 20000,
            'tax_cents' => 2000,
            'total_cents' => 22000,
            'currency' => 'usd',
            'stripe_customer_id' => null,
            'notes' => null,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'issued_at' => null,
            'paid_at' => null,
            'voided_at' => null,
            'refunded_at' => null,
        ], $overrides));

        LineItemModel::query()->insert([
            'uuid' => Uuid::uuid7()->toString(),
            'invoice_id' => $invoice->id,
            'description' => 'Room night',
            'unit_price_cents' => 10000,
            'quantity' => 2,
            'total_cents' => 20000,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return $invoice;
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        $this->get('/billing')
            ->assertRedirect('/login');
    }

    #[Test]
    public function itBlocksGuestActors(): void
    {
        $guest = $this->createGuestActor();

        $this->actingAs($guest)
            ->get('/billing')
            ->assertRedirect('/login');
    }

    #[Test]
    public function itListsInvoices(): void
    {
        $this->createInvoiceModel();

        $response = $this->actingAs($this->actor)
            ->get('/billing');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Index')
            ->has('invoices', 1)
        );
    }

    #[Test]
    public function itShowsAnInvoice(): void
    {
        $invoice = $this->createInvoiceModel();

        $response = $this->actingAs($this->actor)
            ->get('/billing/'.$invoice->uuid);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Show')
            ->has('invoice')
        );
    }

    #[Test]
    public function itIssuesAnInvoice(): void
    {
        $invoice = $this->createInvoiceModel();

        $response = $this->actingAs($this->actor)
            ->post('/billing/'.$invoice->uuid.'/issue');

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'uuid' => $invoice->uuid,
            'status' => 'issued',
        ]);
    }

    #[Test]
    public function itVoidsAnInvoice(): void
    {
        $invoice = $this->createInvoiceModel();

        $response = $this->actingAs($this->actor)
            ->post('/billing/'.$invoice->uuid.'/void', [
                'reason' => 'Created by mistake',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'uuid' => $invoice->uuid,
            'status' => 'void',
        ]);
    }
}
