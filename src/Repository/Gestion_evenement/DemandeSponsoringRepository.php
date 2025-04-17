<?php

namespace App\Repository;

use App\Entity\DemandeSponsoring;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DemandeSponsoring|null find($id, $lockMode = null, $lockVersion = null)
 * @method DemandeSponsoring|null findOneBy(array $criteria, array $orderBy = null)
 * @method DemandeSponsoring[]    findAll()
 * @method DemandeSponsoring[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DemandeSponsoringRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeSponsoring::class);
    }

    /**
     * Trouver toutes les demandes de sponsoring par sponsor
     */
    public function findBySponsorWithEvent($sponsor)
    {
        return $this->createQueryBuilder('d')
            ->join('d.evenement', 'e')
            ->addSelect('e')
            ->join('d.sponsor', 's')  // 🔥 Force le chargement du sponsor
            ->addSelect('s')  
            ->where('d.sponsor = :sponsor')
            ->setParameter('sponsor', $sponsor)
            ->getQuery()
            ->getResult();
    }
    
    

}