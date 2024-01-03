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
        return $this->json(['message' => 'GET books']);
    }

    /**
     * @Rest\Get("/api/books/{id}")
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns a simple message",
     *     @Model(type=Response::class)
     * )
     * @OA\Tag(name="Resource")
     */
    public function getSingleBook(): Response
    {
        return $this->json(['message' => 'GET book']);
    }

    /**
     * @Rest\Post("/api/secured/books")
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
        return $this->json(['message' => 'CREATE book']);
    }


    /**
     * @Rest\Put("/api/secured/books/{id}")
     * 
     * @OA\Response(
     *     response=203,
     *     description="Returns a simple message",
     *     @Model(type=Response::class)
     * )
     * @OA\Tag(name="Resource")
     */
    public function updateBooks(): Response
    {        
        return $this->json(['message' => 'UPDATE action']);
    }

    /**
     * @Rest\Delete("/api/secured/books/{id}")
     * 
     * @OA\Response(
     *     response=203,
     *     description="Returns a simple message",
     *     @Model(type=Response::class)
     * )
     * @OA\Tag(name="Resource")
     */
    public function deleteBooks(): Response
    {        
        return $this->json(['message' => 'UPDATE action']);
    }
}