<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    // méthode qui récupère la liste des utilisateurs associés à un client spécifique; utilisée dans le UserController 
    public function findUsersByClient(Client $client): array
{
    $users = $this->createQueryBuilder('u')
            ->andWhere('u.client = :client')
            ->setParameter('client', $client)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        if (!$users) {
            throw new NotFoundHttpException('Aucun utilisateur n\'a été trouvé pour ce client.');
        }

        return $users;

    // return $this->createQueryBuilder('u')
    //     // ->where('u.client = :client')
    //     // ->setParameter('client', $client)
    //     // ->getQuery()
    //     // ->getResult();
    //     ->join('u.client', 'c')
    //     ->where('c.id = :clientId')
    //     ->setParameter('clientId', $client->getId())
    //     ->getQuery()
    //     ->getResult();

}
// public function countUsersByClient(Client $client): int
// {
//     return $this->createQueryBuilder('u')
//         ->select('COUNT(u.id)')
//         ->where('u.client = :client')
//         ->setParameter('client', $client)
//         ->getQuery()
//         ->getSingleScalarResult();
// }

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
