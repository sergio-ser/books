<?php

namespace App\Command;

use Faker\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class InsertBooks extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:insert-books')
            ->setDescription('Insert 1 million books in database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {



        

        // for ($i = 0; $i < 1000000; $i++) {
        //     $data = ['record_number' => $i];
        //     $this->producer->publish(json_encode($data));
        // }

        $output->writeln('1 million records added to the queue.');

        return Command::SUCCESS;

        // $faker = Factory::create();
        // $batchSize = 100;
        // $totalRecords = 1000000;

        // for ($i = 1; $i <= $totalRecords; ++$i) {
        //     $book = new Books();
        //     $book->setTitle($faker->sentence(4));
        //     $book->setAuthor($faker->name);
        //     $book->setDescription($faker->paragraph);
        //     $book->setPrice($faker->randomFloat(2, 1, 100));

            // $producerService = new Producer();
            // $producerService->publish($book);

            // $this->entityManager->persist($book);

            // if (($i % $batchSize) === 0) {
            //     $this->entityManager->flush();
            //     $this->entityManager->clear();
            //     unset($book);
            //     gc_collect_cycles();
            // }
        // }

        // $this->entityManager->flush();

    }
}