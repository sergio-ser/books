<?php

namespace App\Service;

use App\Message\DbInsertMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class DbInsertProducerService
{
    private $producer;

    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    public function publish(array $data)
    {
        $message = new DbInsertMessage($data);
        $this->producer->publish(serialize($message));
    }
}