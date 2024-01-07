<?php

namespace App\Command;

use Faker\Factory;
use App\Utils\Constants\Utils;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddBooksCommand extends Command
{
    private $rabbitMqConnection;

    public function __construct(AMQPStreamConnection $rabbitMqConnection)
    {
        parent::__construct();
        $this->rabbitMqConnection = $rabbitMqConnection;
    }

    protected function configure()
    {
        $this->setName('app:add-books')
            ->setDescription('Add books to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Adding books to the queue...');
        
        $channel = $this->rabbitMqConnection->channel();
        $faker = Factory::create();

        $channel->exchange_declare(Utils::DB_INSERT_EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare(Utils::DB_INSERT_QUEUE, false, true, false, false);
        $channel->queue_bind(Utils::DB_INSERT_QUEUE, Utils::DB_INSERT_EXCHANGE, Utils::ROUTING_KEY);

        for ($i = 1; $i <= 100000; ++$i) {
            $book = [
                'entity' => 'books',
                'data' => [
                    'title' => $faker->sentence(4),
                    'author' => $faker->name,
                    'description' => $faker->paragraph,
                    'price' => $faker->randomFloat(2, 1, 100),
                ]
            ];
        
            $message = new AMQPMessage(json_encode($book));
            $channel->basic_publish($message, Utils::DB_INSERT_EXCHANGE, Utils::ROUTING_KEY);
            
            if ($i % 1000 === 0) {
                $output->writeln("$i records added.");
                usleep(5000);
            }
        }

        $output->writeln('Books added to the queue.');

        return Command::SUCCESS;
    }
}