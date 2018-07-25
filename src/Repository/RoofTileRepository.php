<?php

namespace App\Repository;

use App\Entity\RoofTile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RoofTile|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoofTile|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoofTile[]    findAll()
 * @method RoofTile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoofTileRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RoofTile::class);
    }

//    /**
//     * @return RoofTile[] Returns an array of RoofTile objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RoofTile
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
