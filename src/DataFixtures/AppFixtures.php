<?php

namespace App\DataFixtures;


use Faker\Factory;
use App\Entity\Books;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $batchSize = 1000;

        for ($i = 0; $i < 10000; $i++) {
            $entity = new Books;
            $entity->setTitle($faker->sentence(4));
            $entity->setAuthor($faker->name);
            $entity->setDescription($faker->paragraph);
            $entity->setPrice($faker->randomFloat(2, 1, 100));

            $manager->persist($entity);

            if (($i % $batchSize) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
    }
}
