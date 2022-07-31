<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $admin = new User();
        $admin->setName('Demo Admin');
        $admin->setEmail('admin@demo.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'demo123'));
        $admin->setRoles(['ROLE_ADMIN']);

        $user = new User();
        $user->setName('Demo User');
        $user->setEmail('user@demo.com');
        $user->setPassword($this->hasher->hashPassword($user, 'demo123'));

        $category = new Category();
        $category->setSlug('demo-category');
        $category->setName('Demo Category');

        $manager->persist($admin);
        $manager->persist($user);
        $manager->persist($category);

        $manager->flush();
    }
}
