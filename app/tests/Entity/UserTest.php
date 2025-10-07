<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserCreation(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertNull($this->user->getId());
    }

    public function testUsernameGetterAndSetter(): void
    {
        $username = 'testuser';
        $this->user->setUsername($username);

        $this->assertSame($username, $this->user->getUsername());
        $this->assertSame($username, $this->user->getUserIdentifier());
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);

        $this->assertSame($email, $this->user->getEmail());
    }

    public function testPasswordGetterAndSetter(): void
    {
        $password = 'hashed_password_123';
        $this->user->setPassword($password);

        $this->assertSame($password, $this->user->getPassword());
    }

    public function testRolesGetterAndSetter(): void
    {
        // Par défaut, ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        // Ajout de ROLE_ADMIN
        $this->user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $roles = $this->user->getRoles();

        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRolesAlwaysContainsRoleUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();

        // ROLE_USER doit toujours être présent
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testEraseCredentials(): void
    {
        // Cette méthode ne doit rien faire pour l'instant
        $password = 'test_password';
        $this->user->setPassword($password);

        $this->user->eraseCredentials();

        // Le mot de passe ne doit pas être effacé
        $this->assertSame($password, $this->user->getPassword());
    }

    public function testUserIdentifierReturnsUsername(): void
    {
        $username = 'johndoe';
        $this->user->setUsername($username);

        $this->assertSame($username, $this->user->getUserIdentifier());
    }
}
