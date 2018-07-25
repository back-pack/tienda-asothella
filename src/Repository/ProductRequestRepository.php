<?php

namespace App\Repository;

use App\Entity\ProductRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ProductRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductRequest[]    findAll()
 * @method ProductRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRequestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProductRequest::class);
    }

//    /**
//     * @return ProductRequest[] Returns an array of ProductRequest objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductRequest
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
