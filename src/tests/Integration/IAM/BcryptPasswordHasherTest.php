<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Infrastructure\Security\BcryptPasswordHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(BcryptPasswordHasher::class)]
final class BcryptPasswordHasherTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = $this->app->make(PasswordHasher::class);
    }

    #[Test]
    public function itHashesAPassword(): void
    {
        $hashed = $this->hasher->hash('password123');

        $this->assertInstanceOf(HashedPassword::class, $hashed);
        $this->assertNotSame('password123', $hashed->value);
        $this->assertStringStartsWith('$2y$', $hashed->value);
    }

    #[Test]
    public function itVerifiesCorrectPassword(): void
    {
        $hashed = $this->hasher->hash('password123');

        $this->assertTrue($this->hasher->verify('password123', $hashed));
    }

    #[Test]
    public function itRejectsWrongPassword(): void
    {
        $hashed = $this->hasher->hash('password123');

        $this->assertFalse($this->hasher->verify('wrongpassword', $hashed));
    }

    #[Test]
    public function itGeneratesDifferentHashesForSamePassword(): void
    {
        $hash1 = $this->hasher->hash('password123');
        $hash2 = $this->hasher->hash('password123');

        $this->assertNotSame($hash1->value, $hash2->value);
    }
}
