<?php

namespace App\Repository\gestion_de_reservation;

use App\Entity\gestion_de_reservation\ConferenceLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConferenceLocation>
 */
class ConferenceLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConferenceLocation::class);
    }
} 