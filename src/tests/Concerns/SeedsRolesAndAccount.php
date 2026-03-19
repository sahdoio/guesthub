<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\HotelModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\TypeModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Ramsey\Uuid\Uuid;

trait SeedsRolesAndAccount
{
    private TypeModel $guestType;

    private TypeModel $superadminType;

    private TypeModel $ownerType;

    private AccountModel $account;

    private HotelModel $hotel;

    protected function seedRolesAndAccount(): void
    {
        $this->guestType = TypeModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'guest',
        ]);

        $this->superadminType = TypeModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'superadmin',
        ]);

        $this->ownerType = TypeModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'owner',
        ]);

        $this->account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->hotel = HotelModel::withoutGlobalScopes()->create([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->app->make(TenantContext::class)->set($this->account->id);
    }

    protected function createOwnerActor(array $overrides = []): ActorModel
    {
        $actor = ActorModel::create(array_merge([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Hotel Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ], $overrides));

        DB::table('actor_types')->insert([
            'actor_id' => $actor->id,
            'type_id' => $this->ownerType->id,
        ]);

        return $actor;
    }

    protected function createGuestActor(array $overrides = []): ActorModel
    {
        $guestAccount = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Guest Account',
            'slug' => 'guest-' . Uuid::uuid7()->toString(),
            'status' => 'active',
            'created_at' => now(),
        ]);

        $actor = ActorModel::create(array_merge([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => $guestAccount->id,
            'name' => 'Guest User',
            'email' => 'guest@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ], $overrides));

        DB::table('actor_types')->insert([
            'actor_id' => $actor->id,
            'type_id' => $this->guestType->id,
        ]);

        return $actor;
    }

    protected function createSuperAdminActor(array $overrides = []): ActorModel
    {
        $actor = ActorModel::create(array_merge([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => null,
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ], $overrides));

        DB::table('actor_types')->insert([
            'actor_id' => $actor->id,
            'type_id' => $this->superadminType->id,
        ]);

        return $actor;
    }
}
