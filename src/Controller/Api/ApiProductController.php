<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * API JSON en lecture seule du catalogue.
 *
 * Utilise le composant Serializer de Symfony avec une gestion fine des
 * GROUPES DE NORMALISATION :
 *   - "product:read"   -> champs de liste (id, nom, slug, prix, type, catégorie, image)
 *   - "product:detail" -> ajoute la description et les champs propres au sous-type
 *                         (stock/mensurations pour une création, niveau/pages pour un patron)
 *
 * Les groupes sont déclarés via l'attribut #[Groups] sur les entités Product,
 * PhysicalCreation et DigitalPattern.
 */
#[Route('/api/v1/products')]
class ApiProductController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    #[Route('', name: 'api_products', methods: ['GET'])]
    public function list(Request $request, ProductRepository $products): JsonResponse
    {
        $filters = [
            'keyword' => $request->query->get('q'),
            'category' => $request->query->get('category'),
            'sort' => $request->query->get('sort', 'newest'),
        ];
        $page = max(1, $request->query->getInt('page', 1));
        $paginator = $products->search($filters, $page, 12);

        // Contexte de sérialisation : uniquement le groupe "product:read".
        $json = $this->serializer->serialize(
            iterator_to_array($paginator),
            'json',
            ['groups' => ['product:read']]
        );

        return new JsonResponse(
            sprintf('{"page":%d,"total":%d,"items":%s}', $page, count($paginator), $json),
            Response::HTTP_OK,
            ['X-Total-Count' => count($paginator)],
            true
        );
    }

    #[Route('/{slug}', name: 'api_product_show', methods: ['GET'])]
    public function show(string $slug, ProductRepository $products): JsonResponse
    {
        $product = $products->findOneBySlugWithRelations($slug);
        if ($product === null) {
            return new JsonResponse(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Vue détaillée : on cumule les deux groupes de normalisation.
        $json = $this->serializer->serialize(
            $product,
            'json',
            ['groups' => ['product:read', 'product:detail']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
