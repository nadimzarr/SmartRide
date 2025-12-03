<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
/**
     * Recherche dynamique des utilisateurs selon les filtres.
     *
     * @param array $filters
     *      - 'search' => texte à chercher dans nom, prenom, email
     *      - 'type'   => Type (Client/Admin)
     *      - 'status' => Statut (ACTIVE/INACTIVE)
     *
     * @return User[]
     */
    public function findByAll(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('u');

        // Filtre recherche globale sur nom, prenom et email
        if (!empty($filters['search'])) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%'.$filters['search'].'%');
        }

        // Filtre type
        if (!empty($filters['type']) && $filters['type'] !== 'All Types') {
            $qb->andWhere('u.Type = :type')
               ->setParameter('type', $filters['type']);
        }

        // Filtre statut
        if (!empty($filters['status']) && $filters['status'] !== 'All Status') {
            $qb->andWhere('u.Statut = :status')
               ->setParameter('status', $filters['status']);
        }

        // Tri par défaut (id décroissant)
        $qb->orderBy('u.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

}
