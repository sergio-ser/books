<?php

namespace App\Repository;

use App\Entity\Books;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Books>
 *
 * @method Books|null find($id, $lockMode = null, $lockVersion = null)
 * @method Books|null findOneBy(array $criteria, array $orderBy = null)
 * @method Books[]    findAll()
 * @method Books[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BooksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Books::class);
    }
    
    /**
     * getCount
     *
     * @param  mixed $q
     * @return int
     */
    final public function getCount(string $q = null, $author = null): int
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(books.id)');

        $this->commonQuery($qb, $q, $author);

        return $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * commonQuery
     *
     * @param  mixed $qb
     * @param  mixed $q
     * @return void
     */
    final public function commonQuery(QueryBuilder $qb, ?string $q, ?string $author): void
    {
        $qb->from('App:Books', 'books');

        if ($author) {
            $qb->andWhere('books.author = :author');
            $qb->setParameter('author', $author);
        }
        if ($q) {
            $qb->andWhere('books.title LIKE :q OR books.price LIKE :q OR books.author LIKE :q OR books.description LIKE :q');
            $qb->setParameter('q', '%' . $q . '%');
        }
    }

    final public function getAll(int $page, int $noRecords, string $sortField, string $sortType, string $q = null, string $author = null): array
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select("
            books.id,
            books.title,
            books.price,
            books.author,
            books.description
        ");

        $this->commonQuery($qb, $q, $author);

        $qb->orderBy('books.' . $sortField, $sortType);

        $qb->setMaxResults($noRecords);
        $qb->setFirstResult($page * $noRecords);

        return $qb->getQuery()->getArrayResult();
    }
}
