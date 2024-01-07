<?php

namespace App\Controller;

use Exception;
use App\Entity\Books;
use App\Module\BookManager;
use App\Utils\Constants\Utils;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Constraints as Assert;


class BooksController extends ApiController
{
    /**
     * @Rest\Get("/api/books")
     * 
     * Get books.
     *
     * Please use this service to get books.
     * 
     * @OA\Parameter(name="author", in="query", description="Author name filter", @OA\Schema(type="string"))
     * @OA\Parameter(name="q", in="query", description="Search term", @OA\Schema(type="string"))
     * @OA\Parameter(name="sort", in="query", example="id", description="Sort options: fieldName or -fieldName", @OA\Schema(type="string"))
     * @OA\Parameter(name="offset", in="query", example=1, description="Offset for pagination", @OA\Schema(type="integer"))
     * @OA\Parameter(name="limit", in="query", example=20, description="Limit for pagination", @OA\Schema(type="integer"))
     * 
     * @OA\Response(response=200, description="Returns books details", @Model(type=Books::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function index(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        try {

            $this->setListingConfigurations($request, $page, $noRecords, $sortField, $sortType);

            $noTotal = $em->getRepository(Books::class)->getCount($request->get('q'));
            $books = $em->getRepository(Books::class)->getAll($page, $noRecords, $sortField, $sortType, $request->get('q'), $request->get('author'));

            $headerLink = $this->setHeaderLink($request, $page, $noRecords, $noTotal);

            return $this->json($books, Response::HTTP_OK, [$headerLink]);

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Get("/api/books/download")
     * 
     * Download books.
     *
     * Please use this service to download books.
     * 
     * @OA\Parameter(name="cursor", in="query", example=1, description="Cursor for pagination", @OA\Schema(type="integer"))
     * @OA\Parameter(name="limit", in="query", example=20, description="Limit for pagination", @OA\Schema(type="integer"))
     * 
     * @OA\Response(response=200, description="Returns books file")
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function download(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        try {

            $cursor = $request->get('cursor') ?? null;
            $limit = $request->get('limit') ?? 1000; 
            
            $csvFilePath = 'books_export.csv';
            $handle = fopen($csvFilePath, 'w');
            fputcsv($handle, ['Title', 'Price']);

            do {
                $books = $em->getRepository(Books::class)->getBooksByCursor($cursor, $limit);

                foreach ($books as $book) {
                    fputcsv($handle, [$book['title'], $book['price']]);
                }

                $lastBook = end($books);
                $cursor = $lastBook ? $lastBook['id'] : null;

                $em->clear();

            } while (!empty($books));

            $response = new Response(file_get_contents($csvFilePath));
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="books_export.csv"');

            return $response;

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Get("/api/books/{id}")
     * 
     * Get a book.
     *
     * Please use this service to get a book.
     * 
     * @OA\Parameter(name="id", in="path", required=true, description="Book ID", @OA\Schema(type="integer")),
     * 
     * @OA\Response(response=200, description="Returns books details", @Model(type=Books::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function show(mixed $id): Response
    {
        $em = $this->doctrine->getManager();

        try {

            $book = $em->getRepository(Books::class)->findOneBy(['id' => $id]);
            if (!$book) {
                return $this->json(['error' =>Utils::BOOK_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            return $this->json($book->toArray(), Response::HTTP_OK);

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Post("/api/secured/books")
     * 
     * Create book.
     *
     * Please use this service to create a book.
     *
     * @OA\RequestBody(
     *     description="Book data",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(property="title", type="string", example="Earum ab consectetur nam", description="Book title"),
     *          @OA\Property(property="author", type="string", example="Yasmeen Green", description="Author name"),
     *          @OA\Property(property="description", type="string", example="Deleniti non labore et voluptatem quia ut autem. Eum at deleniti quia. Ullam eum eos praesentium doloremque sunt dolorum et. Fuga laboriosam rerum odit ut.", description="Book description"),
     *          @OA\Property(property="price", type="float", example=112.5, description="Book price")
     *     )
     * )
     *
     * @OA\Response(response=201, description="Returns books details", @Model(type=Books::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function create(Request $request): Response
    {   
        $em = $this->doctrine->getManager();
        $validator = $this->validator;
        $data = $request->request->all();
        $em->getConnection()->beginTransaction();

        try {

            $validations = $validator->validate($data, $this->getConstraints());
            if (count($validations) > 0) {
                $errors = [];
                foreach ($validations as $validation) {
                    $errors[$validation->getPropertyPath()] = $validation->getMessage();
                }

                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $bookManager = new BookManager($em, $request);
            $book = $bookManager->create();
            $em->getConnection()->commit();

            return $this->json($book->toArray(), Response::HTTP_CREATED);

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            $em->getConnection()->rollBack();

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @Rest\Put("/api/secured/books/{id}")
     * 
     * Update book.
     *
     * Please use this service to update a book.
     * 
     * @OA\Parameter(name="id", in="path", required=true, description="Book ID", @OA\Schema(type="integer")),
     * 
     * @OA\RequestBody(
     *     description="Book data",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(property="title", type="string", example="Earum ab consectetur nam", description="Book title"),
     *          @OA\Property(property="author", type="string", example="Yasmeen Green", description="Author name"),
     *          @OA\Property(property="description", type="string", example="Deleniti non labore et voluptatem quia ut autem. Eum at deleniti quia. Ullam eum eos praesentium doloremque sunt dolorum et. Fuga laboriosam rerum odit ut.", description="Book description"),
     *          @OA\Property(property="price", type="float", example=112.5, description="Book price")
     *     )
     * )
     *
     * @OA\Response(response=200, description="Returns books details", @Model(type=Books::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function update(Request $request, mixed $id): Response
    {        
        $em = $this->doctrine->getManager();
        $validator = $this->validator;
        $data = $request->request->all();
        $em->getConnection()->beginTransaction();

        try {

            $validations = $validator->validate($data, $this->getConstraints());
            if (count($validations) > 0) {
                $errors = [];
                foreach ($validations as $validation) {
                    $errors[$validation->getPropertyPath()] = $validation->getMessage();
                }

                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $book = $em->getRepository(Books::class)->findOneBy(['id' => $id]);
            if (!$book) {
                return $this->json(['error' =>Utils::BOOK_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            $bookManager = new BookManager($em, $request);
            $book = $bookManager->update($book);
            $em->getConnection()->commit();

            return $this->json($book->toArray(), Response::HTTP_OK);

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            $em->getConnection()->rollBack();

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Delete("/api/secured/books/{id}")
     * 
     * Delete book.
     *
     * Please use this service to delete a book.
     * 
     * @OA\Parameter(name="id", in="path", required=true, description="Book ID", @OA\Schema(type="integer")),
     *
     * @OA\Response(response=204, description="No Content")
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books")
     */
    public function delete(mixed $id): Response
    {        
        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            $book = $em->getRepository(Books::class)->findOneBy(['id' => $id]);
            if (!$book) {
                return $this->json(['error' =>Utils::BOOK_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            $em->remove($book);
            $em->flush();
            $em->getConnection()->commit();

            return $this->json(null, Response::HTTP_NO_CONTENT);

        } catch (Exception $e) {
            $this->logger->error(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());

            $em->getConnection()->rollBack();

            return $this->json(['error' => Utils::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * getConstraints
     *
     * @return Assert\Collection
     */
    private function getConstraints(): Assert\Collection
    {
        $constraints = new Assert\Collection([
            'title' => new Assert\NotBlank(['message' => 'Title cannot be blank']),
            'author' => new Assert\NotBlank(['message' => 'Author cannot be blank']),
            'description' => new Assert\Optional(),
            'price' => [
                new Assert\NotBlank(['message' => 'Price cannot be blank']),
                new Assert\Type(['type' => 'float', 'message' => 'Price must be a valid float']),
            ]
        ]);

        return $constraints;
    }
}