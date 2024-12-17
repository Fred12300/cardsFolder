<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findByFilter(?string $context, ?string $name, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%');

        if ($context === 'folders') {
            $qb->join('c.folders', 'f')
                ->andWhere('f.owner = :user');
                $qb->setParameter('user', $user);
        } elseif ($context === 'wishes') {
            $qb->join('c.users', 'w')
                ->andWhere('w = :user');
                $qb->setParameter('user', $user);
        }
            
        

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Card[] Returns an array of Card objects
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

    //    public function findOneBySomeField($value): ?Card
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
