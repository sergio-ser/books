<?php

namespace App\Module;

use App\Entity\Books;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;


class BookManager
{
    public function __construct(private readonly ObjectManager $em, private readonly Request $request)
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    final public function create(): Books
    {
        return $this->update(new Books);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    final public function update(Books $book): Books
    {

        $book->setTitle($this->request->get('title'));
        $book->setAuthor($this->request->get('author'));
        $book->setDescription($this->request->get('description'));
        $book->setPrice($this->request->get('price'));

        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

}
