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

    // Add the custom findByRole method using raw SQL
    public function findByRole(string $role): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.role LIKE :role')
        ->setParameter('role', '%"' . $role . '"%')  // يضمن البحث داخل JSON
        ->getQuery()
        ->getResult();
}

    
}

    