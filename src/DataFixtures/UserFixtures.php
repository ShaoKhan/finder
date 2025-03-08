<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Admin User
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setVerified(true);
        $admin->setIsActive(true);
        $manager->persist($admin);

        // Normale aktive Benutzer
        $activeUsers = [
            ['email' => 'user1@example.com', 'password' => 'user123'],
            ['email' => 'user2@example.com', 'password' => 'user123'],
            ['email' => 'user3@example.com', 'password' => 'user123'],
        ];

        foreach ($activeUsers as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $user->setVerified(true);
            $user->setIsActive(true);
            $manager->persist($user);
        }

        // Inaktive Benutzer
        $inactiveUsers = [
            ['email' => 'inactive1@example.com', 'password' => 'user123'],
            ['email' => 'inactive2@example.com', 'password' => 'user123'],
        ];

        foreach ($inactiveUsers as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $user->setVerified(false);
            $user->setIsActive(false);
            $manager->persist($user);
        }

        // Moderator
        $moderator = new User();
        $moderator->setEmail('moderator@example.com');
        $moderator->setRoles(['ROLE_MODERATOR', 'ROLE_USER']);
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'mod123'));
        $moderator->setVerified(true);
        $moderator->setIsActive(true);
        $manager->persist($moderator);

        $manager->flush();
    }
} 