<?php

namespace App\Controller\Api;

use App\Entity\PhysicalCreation;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Mini API JSON en lecture seule du catalogue.
 * On construit explicitement la charge utile pour maitriser l'exposition des
 * donnees et eviter toute reference circulaire lors de la serialisation.
 */
#[Route('/api/products')]
class ApiProductController extends AbstractController
{
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

        $items = array_map(fn (Product $p) => $this->serializeProduct($p), iterator_to_array($paginator));

        return $this->json([
            'page' => $page,
            'total' => count($paginator),
            'items' => $items,
        ]);
    }

    #[Route('/{slug}', name: 'api_product_show', methods: ['GET'])]
    public function show(string $slug, ProductRepository $products): JsonResponse
    {
        $product = $products->findOneBySlugWithRelations($slug);
        if ($product === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeProduct($product, detailed: true));
    }

    /** @return array<string, mixed> */
    private function serializeProduct(Product $product, bool $detailed = false): array
    {
        $data = [
            'id' => $product->getId(),
            'type' => $product->getType(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'price' => $product->getBasePrice(),
            'customizable' => $product->isCustomizable(),
            'category' => $product->getCategory()?->getName(),
            'image' => $product->getMainImage()?->getFilename(),
        ];

        if ($detailed) {
            $data['description'] = $product->getDescription();
            $data['tags'] = array_map(fn ($t) => $t->getName(), $product->getTags()->toArray());
            if ($product instanceof PhysicalCreation) {
                $data['stock'] = $product->getStock();
                $data['requiresMeasurements'] = $product->requiresMeasurements();
            }
        }

        return $data;
    }
}
