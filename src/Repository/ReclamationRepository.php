<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }


public function findByAllFields(?string $q): array
{
    $qb = $this->createQueryBuilder('r');

    if ($q) {
        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(r.nom) LIKE :q',
                'LOWER(r.prenom) LIKE :q',
                'LOWER(r.message) LIKE :q',
                'LOWER(r.type_reclamation) LIKE :q'
            )
        )
        ->setParameter('q', '%'.strtolower($q).'%');
    }

    $qb->orderBy('r.id', 'DESC');

    return $qb->getQuery()->getResult();
}
public function findByFilters(?string $search = null, ?string $type = null): array
{
    $qb = $this->createQueryBuilder('r');

    if ($search) {
        $qb->andWhere('LOWER(r.nom) LIKE :search OR LOWER(r.prenom) LIKE :search OR LOWER(r.message) LIKE :search')
           ->setParameter('search', '%'.strtolower($search).'%');
    }

    if ($type && $type !== 'All Types') {
        $qb->andWhere('r.type_reclamation = :type')
           ->setParameter('type', $type);
    }

    $qb->orderBy('r.id', 'DESC');

    return $qb->getQuery()->getResult();
}


    //    /**
    //     * @return Reclamation[] Returns an array of Reclamation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reclamation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
