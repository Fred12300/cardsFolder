<?php

namespace App\Repository;

use App\Entity\Folder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Folder>
 */
class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    public function getMatches(User $user, EntityManagerInterface $em)
    {
        // Récupérer l'utilisateur connecté
        $userId = $user->getId();

        // Récupérer les folders échangeables des autres utilisateurs
        $othersFolders = $em->createQuery(
            'SELECT f 
            FROM App\Entity\Folder f 
            WHERE f.owner != :userId
            AND f.isExchangeable = true'
        )->setParameter('userId', $userId)
        ->getResult();

        // Récupérer la wishlist de l'utilisateur actuel
        $usersWishes = $user->getWish()->toArray();

        $matches = [];

        // Trouver les correspondances entre la wishlist et les folders
        foreach ($usersWishes as $wish) {
            foreach ($othersFolders as $folder) {
                // Comparer les IDs
                if ($wish->getId() == $folder->getCard()->getId()) {
                    $matches[] = [
                        'wish' => $wish,
                        'folder' => $folder,
                    ];
                }
            }
        }
        return  $matches;
    }

    public function findReciprocity(EntityManagerInterface $em, User $user, User $target): array
    {
        $userId = $user->getId();
        $targetId = $target->getId();

        // Récupérer les folders échangeables de l'utilisateur connecté
        $myFolders = $em->createQuery(
            'SELECT f 
            FROM App\Entity\Folder f 
            WHERE f.owner = :userId
            AND f.exchangeable = true'
        )->setParameter('userId', $userId)
        ->getResult();

        // Récupérer la wishlist de l'utilisateur cible
        $targetWishes = $em->createQueryBuilder()
            ->select('c.id', 'c.name', 'c.type', 'c.imageUrl')
            ->from('App\Entity\User', 'u')
            ->join('u.wishes', 'c')
            ->where('u.id = :targetId')
            ->setParameter('targetId', $targetId)
            ->getQuery()
            ->getResult();

        $reverseMatches = [];

        // Trouver les correspondances entre la wishlist et les folders
        foreach ($targetWishes as $wish) {
            foreach ($myFolders as $folder) {
                if ($wish['id'] == $folder->getCardr()->getId()) {
                    $reverseMatches[] = [
                        'wish' => $wish,
                        'folder' => $folder,
                    ];
                }
            }
        }

        return $reverseMatches;
    }
    //    /**
    //     * @return Folder[] Returns an array of Folder objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Folder
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
