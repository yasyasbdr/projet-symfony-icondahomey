<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche filtree du catalogue avec pagination.
     * Une seule methode couvre tous les filtres du moteur de recherche
     * (mot-cle, categorie, prix, patron disponible, personnalisable, tri).
     * Les jointures (category, images) evitent le probleme du N+1 a l'affichage.
     *
     * @param array<string, mixed> $filters
     * @return Paginator<Product>
     */
    public function search(array $filters = [], int $page = 1, int $limit = 12): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c', 'img')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.images', 'img')
            ->andWhere('p.isPublished = true');

        if (!empty($filters['keyword'])) {
            $qb->andWhere('p.name LIKE :kw OR p.description LIKE :kw')
                ->setParameter('kw', '%'.$filters['keyword'].'%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.slug = :cat')->setParameter('cat', $filters['category']);
        }

        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') {
            $qb->andWhere('p.basePrice >= :pmin')->setParameter('pmin', $filters['priceMin']);
        }

        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') {
            $qb->andWhere('p.basePrice <= :pmax')->setParameter('pmax', $filters['priceMax']);
        }

        if (!empty($filters['customizable'])) {
            $qb->andWhere('p.isCustomizable = true');
        }

        if (!empty($filters['pdfOnly'])) {
            $qb->andWhere('p INSTANCE OF :patternClass')
                ->setParameter('patternClass', $this->getEntityManager()->getClassMetadata(\App\Entity\DigitalPattern::class));
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc' => $qb->orderBy('p.basePrice', 'ASC'),
            'price_desc' => $qb->orderBy('p.basePrice', 'DESC'),
            'name' => $qb->orderBy('p.name', 'ASC'),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        return new Paginator($qb->getQuery(), fetchJoinCollection: true);
    }

    /**
     * Derniers produits publies pour la section "New In" de l'accueil.
     * @return Product[]
     */
    public function findLatestPublished(int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('img')
            ->leftJoin('p.images', 'img')
            ->andWhere('p.isPublished = true')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugWithRelations(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'img', 't')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.images', 'img')
            ->leftJoin('p.tags', 't')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
