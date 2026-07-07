<?php

namespace App\Repository;

use App\Entity\CustomizationRequest;
use App\Entity\User;
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
     * Demandes de personnalisation à traiter par un admin (prix à fixer).
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

    /** Toutes les demandes (pour la vue d'administration). @return CustomizationRequest[] */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('cr')
            ->addSelect('u', 'p')
            ->join('cr.customer', 'u')
            ->join('cr.product', 'p')
            ->orderBy('cr.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Demandes d'un client donné. @return CustomizationRequest[] */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('cr')
            ->addSelect('p')
            ->join('cr.product', 'p')
            ->andWhere('cr.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('cr.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
