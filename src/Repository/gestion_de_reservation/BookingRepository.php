<?php

namespace App\Repository\gestion_de_reservation;

use App\Entity\gestion_de_reservation\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }
    public function findByEventName(string $searchTerm)
        {
            return $this->createQueryBuilder('b')
                ->leftJoin('b.evenement', 'e')
                ->where('LOWER(e.nom) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();
        }
}