<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\User;
use App\Form\AddToCartType;
use App\Repository\ProductRepository;
use App\Service\CartManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/creation/{slug}', name: 'app_product_show', methods: ['GET', 'POST'])]
    public function show(string $slug, Request $request, ProductRepository $products, CartManager $cartManager): Response
    {
        $product = $products->findOneBySlugWithRelations($slug);
        if ($product === null) {
            throw $this->createNotFoundException('Creation introuvable.');
        }

        $item = new CartItem();
        $item->setProduct($product);
        $form = $this->createForm(AddToCartType::class, $item, ['product' => $product]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User|null $user */
            $user = $this->getUser();
            if ($user === null) {
                $this->addFlash('info', 'Connectez-vous pour ajouter au panier.');

                return $this->redirectToRoute('app_login');
            }

            $measurements = [];
            foreach (['poitrine', 'taille', 'hanches', 'hauteur'] as $m) {
                if ($form->has('m_'.$m) && $form->get('m_'.$m)->getData() !== null) {
                    $measurements[$m] = $form->get('m_'.$m)->getData();
                }
            }

            $cartManager->addProduct(
                $user,
                $product,
                $item->getSelectedType(),
                $item->getQuantity(),
                $measurements ?: null,
                $item->getCustomizationNote(),
            );

            $this->addFlash('success', 'Ajoute au panier.');

            return $this->redirectToRoute('app_product_show', ['slug' => $slug]);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
}
