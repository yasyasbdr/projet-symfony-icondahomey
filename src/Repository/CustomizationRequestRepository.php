<?php

namespace App\Repository;

use App\Entity\CustomizationRequest;
use App\Enum\CustomizationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomizationRequest>
 */
class CustomizationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomizationRequest::class);
    }

    /**
     * Demandes de personnalisation a traiter par un admin (prix a fixer).
     * @return CustomizationRequest[]
     */
    public function findPendingForAdmin(): array
    {
        return $this->createQueryBuilder('cr')
            ->addSelect('u', 'p')
            ->join('cr.customer', 'u')
            ->join('cr.product', 'p')
            ->andWhere('cr.status = :status')
            ->setParameter('status', CustomizationStatus::Pending)
            ->orderBy('cr.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
