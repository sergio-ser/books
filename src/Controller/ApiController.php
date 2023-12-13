<?php

namespace App\Controller;

use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;

class ApiController extends AbstractFOSRestController
{

    /**
     * @Rest\Get("/api/books")
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns a simple message",
     *     @Model(type=Response::class)
     * )
     * @OA\Tag(name="Resource")
     */
    public function getBooks(): Response
    {
        return $this->json(['message' => 'GET action']);
    }

    /**
     * @Rest\Post("/api/secured/books/create")
     * 
     * @OA\Response(
     *     response=203,
     *     description="Returns a simple message",
     *     @Model(type=Response::class)
     * )
     * @OA\Tag(name="Resource")
     */
    public function createBooks(): Response
    {        
        return $this->json(['message' => 'CREATE action']);
    }
}