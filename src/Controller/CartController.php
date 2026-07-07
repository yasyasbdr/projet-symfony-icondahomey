<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\User;
use App\Service\CartManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(CartManager $cartManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('cart/index.html.twig', [
            'cart' => $cartManager->getOrCreateCart($user),
        ]);
    }

    #[Route('/{id}/quantite', name: 'app_cart_update', methods: ['POST'])]
    public function update(CartItem $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->assertOwner($item);

        $qty = max(1, $request->request->getInt('quantity', 1));
        $item->setQuantity($qty);
        $item->getCart()?->touch();
        $em->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/{id}/supprimer', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->assertOwner($item);

        if ($this->isCsrfTokenValid('cart_remove'.$item->getId(), (string) $request->request->get('_token'))) {
            $cart = $item->getCart();
            $cart?->removeItem($item);
            $cart?->touch();
            $em->remove($item);
            $em->flush();
            $this->addFlash('success', 'Article retiré du panier.');
        }

        return $this->redirectToRoute('app_cart');
    }

    /** Vérifie que la ligne de panier appartient bien à l'utilisateur connecté. */
    private function assertOwner(CartItem $item): void
    {
        if ($item->getCart()?->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }
}
