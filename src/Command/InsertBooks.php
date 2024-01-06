<?php

namespace App\Command;

use App\Entity\Books;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory;

class InsertBooks extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setName('app:insert-records')
            ->setDescription('Insert 1 million records into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $faker = Factory::create();
        $batchSize = 100;
        $totalRecords = 1000000;

        for ($i = 1; $i <= $totalRecords; ++$i) {
            $book = new Books();
            $book->setTitle($faker->sentence(4));
            $book->setAuthor($faker->name);
            $book->setDescription($faker->paragraph);
            $book->setPrice($faker->randomFloat(2, 1, 100));

            $this->entityManager->persist($book);

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                unset($book);
                gc_collect_cycles();
            }
        }

        $this->entityManager->flush();

        $output->writeln('Records inserted successfully.');

        return Command::SUCCESS;
    }
}