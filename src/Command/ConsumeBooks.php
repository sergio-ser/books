<?php

namespace App\Command;

use App\Entity\Books;
use Psr\Log\LoggerInterface;
use App\Utils\Constants\Utils;
use PhpAmqpLib\Message\AMQPMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeBooksCommand extends Command
{
    private AMQPStreamConnection $rabbitMqConnection;
    private EntityManagerInterface $entityManager;
    protected LoggerInterface $logger;

    public function __construct(AMQPStreamConnection $rabbitMqConnection, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->rabbitMqConnection = $rabbitMqConnection;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setName('app:consume-books')
            ->setDescription('Add books to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $channel = $this->rabbitMqConnection->channel();

        $channel->exchange_declare(Utils::DB_INSERT_EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare(Utils::DB_INSERT_QUEUE, false, true, false, false);
        $channel->queue_bind(Utils::DB_INSERT_QUEUE, Utils::DB_INSERT_EXCHANGE, Utils::ROUTING_KEY);

        $batchSize = 1000;
        $messageCount = 0;

        $entityManager = $this->entityManager;

        $channel->basic_consume(
            Utils::DB_INSERT_QUEUE,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use (&$batch, &$messageCount, $batchSize, $entityManager) {
                try {
                    $payload = json_decode($message->body, true);
        
                    $entityName = $payload['entity'];
                    $data = $payload['data'];
        
                    $batch[] = $data;
        
                    if (++$messageCount % $batchSize === 0) {
                        $this->insertBatch($entityManager, $entityName, $batch);                        
                        $batch = [];
                        echo "$messageCount records processed.\n";
                    }
        
                    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                } catch (\Exception $e) {
                    $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());
                }
            }
        );

        $output->writeln('Waiting for messages. To exit, press CTRL+C');

        while ($channel->is_consuming()) {
            $channel->wait();
        }
        
        $channel->close();
        $this->rabbitMqConnection->close();

        return Command::SUCCESS;

    }

    private function insertBatch(EntityManagerInterface $entityManager, string $entityName, array $batch): void
    {

        $connection = $entityManager->getConnection();

        try {
            $connection->beginTransaction();

            $columns = implode(',', array_keys($batch[0]));
            $placeholders = rtrim(str_repeat('?,', count($batch[0])), ',');

            $valuesPlaceholders = implode(',', array_fill(0, count($batch), "($placeholders)"));

            $values = [];
            foreach ($batch as $data) {
                $values = array_merge($values, array_values($data));
            }

            $query = "INSERT INTO $entityName ($columns) VALUES $valuesPlaceholders";

            $statement = $connection->prepare($query);
            $statement->execute($values);

            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());
            
            $connection->rollBack();
        }
    }

}
