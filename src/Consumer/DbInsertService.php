<?php

namespace App\Service;

use App\Message\DbInsertMessage;
use Doctrine\ORM\EntityManagerInterface;

class DbInsertService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function processMessage($message)
    {
        $data = unserialize($message->body);
        // Implement your database insertion logic using $this->entityManager
    }
}