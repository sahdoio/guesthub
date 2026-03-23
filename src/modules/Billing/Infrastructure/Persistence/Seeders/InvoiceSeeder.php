<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;
use Modules\Billing\Infrastructure\Persistence\Eloquent\LineItemModel;
use Modules\Billing\Infrastructure\Persistence\Eloquent\PaymentModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\ReservationModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
use Ramsey\Uuid\Uuid;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = ReservationModel::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in', 'checked_out'])
            ->get();

        foreach ($reservations as $reservation) {
            $stay = StayModel::withoutGlobalScopes()
                ->where('uuid', $reservation->stay_uuid)
                ->first();

            if (! $stay) {
                continue;
            }

            $checkIn = new \DateTimeImmutable($reservation->check_in);
            $checkOut = new \DateTimeImmutable($reservation->check_out);
            $nights = max(1, $checkOut->diff($checkIn)->days);
            $pricePerNightCents = (int) ($stay->price_per_night * 100);
            $subtotalCents = $pricePerNightCents * $nights;
            $taxCents = (int) ($subtotalCents * 0.10);
            $totalCents = $subtotalCents + $taxCents;

            $isPaid = in_array($reservation->status, ['confirmed', 'checked_in', 'checked_out']);
            $status = $isPaid ? 'paid' : 'issued';

            $invoice = InvoiceModel::withoutGlobalScopes()->create([
                'uuid' => Uuid::uuid7()->toString(),
                'account_id' => $reservation->account_id,
                'account_uuid' => $reservation->account_uuid,
                'reservation_id' => $reservation->uuid,
                'guest_id' => $reservation->guest_id,
                'status' => $status,
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'total_cents' => $totalCents,
                'currency' => 'usd',
                'created_at' => now(),
                'issued_at' => now(),
                'paid_at' => $isPaid ? now() : null,
            ]);

            LineItemModel::create([
                'uuid' => Uuid::uuid7()->toString(),
                'invoice_id' => $invoice->id,
                'description' => "{$stay->name} — {$nights} night(s)",
                'unit_price_cents' => $pricePerNightCents,
                'quantity' => $nights,
                'total_cents' => $subtotalCents,
                'created_at' => now(),
            ]);
        }
    }
}
