<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\RoleModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Ramsey\Uuid\Uuid;

trait SeedsRolesAndAccount
{
    private RoleModel $adminRole;

    private RoleModel $guestRole;

    private RoleModel $superadminRole;

    private AccountModel $account;

    protected function seedRolesAndAccount(): void
    {
        $this->adminRole = RoleModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'admin',
        ]);

        $this->guestRole = RoleModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'guest',
        ]);

        $this->superadminRole = RoleModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'superadmin',
        ]);

        $this->account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'created_at' => now(),
        ]);

        $this->app->make(TenantContext::class)->set($this->account->id);
    }

    protected function createAdminActor(array $overrides = []): ActorModel
    {
        $actor = ActorModel::create(array_merge([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ], $overrides));

        DB::table('actor_roles')->insert([
            'actor_id' => $actor->id,
            'role_id' => $this->adminRole->id,
        ]);

        return $actor;
    }

    protected function createGuestActor(array $overrides = []): ActorModel
    {
        $actor = ActorModel::create(array_merge([
            'uuid' => Uuid::uuid7()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Guest User',
            'email' => 'guest@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ], $overrides));

        DB::table('actor_roles')->insert([
            'actor_id' => $actor->id,
            'role_id' => $this->guestRole->id,
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

        DB::table('actor_roles')->insert([
            'actor_id' => $actor->id,
            'role_id' => $this->superadminRole->id,
        ]);

        return $actor;
    }
}
