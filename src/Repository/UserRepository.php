<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findMatches(): array
    {
        $user = $this->getUser();
        $userId = $user->getId();

        // Récupérer les folders des autres utilisateurs
        $query = $this->entityManager->createQuery(
            'SELECT f 
             FROM App\Entity\Folder f 
             WHERE f.owner != :userId
             AND f.exchangeable = true'
        )->setParameter('userId', $userId);

        $othersFolders = $query->getResult();

        // Récupérer la wishList de l'utilisateur
        $usersWishes = $this->entityManager->createQueryBuilder()
            ->select('c.id', 'c.name', 'c.type', 'c.imageUrl')
            ->from('App\Entity\User', 'u')
            ->join('u.wishes', 'c')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        $matches = [];

        // Comparer les deux listes
            foreach ($usersWishes as $wish) {
                foreach ($othersFolders as $folder) {
                    if ($wish['id'] == $folder->getCardInFolder()) {
                        $matches[] = [
                            'wish' => $wish,

                        ];
                    }
                }
            }

        return $matches;
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
            AND f.isExchangeable = true'
        )->setParameter('userId', $userId)
        ->getResult();

        // Récupérer la wishlist de l'utilisateur cible
        $targetWishes = $em->createQueryBuilder()
            ->select('c.id', 'c.name', 'c.type', 'c.image')
            ->from('App\Entity\User', 'u')
            ->join('u.wish', 'c')
            ->where('u.id = :targetId')
            ->setParameter('targetId', $targetId)
            ->getQuery()
            ->getResult();

        $reverseMatches = [];

        // Trouver les correspondances entre la wishlist et les folders
        foreach ($targetWishes as $wish) {
            foreach ($myFolders as $folder) {
                if ($wish['id'] == $folder->getCard()->getId()) {
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
}
