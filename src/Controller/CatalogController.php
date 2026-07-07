<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogController extends AbstractController
{
    #[Route('/catalogue', name: 'app_catalog', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products, CategoryRepository $categories): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;

        $filters = [
            'keyword' => $request->query->get('q'),
            'category' => $request->query->get('category'),
            'priceMin' => $request->query->get('price_min'),
            'priceMax' => $request->query->get('price_max'),
            'customizable' => $request->query->getBoolean('customizable'),
            'pdfOnly' => $request->query->getBoolean('pdf'),
            'sort' => $request->query->get('sort', 'newest'),
        ];

        $paginator = $products->search($filters, $page, $limit);
        $total = count($paginator);

        return $this->render('catalog/index.html.twig', [
            'products' => $paginator,
            'categories' => $categories->findBy(['parent' => null]),
            'filters' => $filters,
            'page' => $page,
            'pages' => (int) ceil($total / $limit),
            'total' => $total,
        ]);
    }
}
