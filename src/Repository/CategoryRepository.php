<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findForDataTable(int $start, int $length, ?string $search = null): array
{
    $qb = $this->createQueryBuilder('c');
    
    // Filtre de recherche
    if ($search) {
        $qb->where('c.title LIKE :search OR c.description LIKE :search')
           ->setParameter('search', '%'.$search.'%');
    }
    
    // Compte total avant filtrage
    $totalCount = $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->getQuery()
        ->getSingleScalarResult();
    
    // Requête paginée
    $qb->orderBy('c.createdAt', 'DESC')
       ->setFirstResult($start)
       ->setMaxResults($length);
    
    $results = $qb->getQuery()->getResult();
    
    // Compte filtré
    $filteredCount = $search 
        ? count($results) 
        : $totalCount;
    
    return [
        'data' => $results,
        'totalCount' => (int)$totalCount,
        'filteredCount' => $filteredCount,
    ];
}

    //    /**
    //     * @return Category[] Returns an array of Category objects
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

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
