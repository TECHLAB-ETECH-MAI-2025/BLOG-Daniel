<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findForDataTable(int $start, int $length, ?string $search = null): array
{
    $qb = $this->createQueryBuilder('c')
        ->leftJoin('c.article', 'a')
        ->leftJoin('c.user', 'u')
        ->addSelect('a', 'u');

    if ($search) {
        $qb->andWhere('c.content LIKE :search OR a.title LIKE :search OR u.email LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    $countQb = clone $qb;
    $countQb->select('COUNT(c.id)');

    $totalCount = (int) $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->getQuery()
        ->getSingleScalarResult();

    $filteredCount = (int) $countQb->getQuery()->getSingleScalarResult();

    $qb->orderBy('c.createdAt', 'DESC')
       ->setFirstResult($start)
       ->setMaxResults($length);

    $results = $qb->getQuery()->getResult();

    return [
        'data' => $results,
        'totalCount' => $totalCount,
        'filteredCount' => $filteredCount,
    ];
}


    //    /**
    //     * @return Comment[] Returns an array of Comment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
