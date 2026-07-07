<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
class FavoriteController extends AbstractController
{
    #[Route('/mon-compte/favoris', name: 'app_favorites', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/favorites.html.twig', [
            'favorites' => $user->getFavorites(),
        ]);
    }

    #[Route('/favoris/{id}/toggle', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggle(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('favorite'.$product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($user->hasFavorite($product)) {
            $user->removeFavorite($product);
        } else {
            $user->addFavorite($product);
        }
        $em->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_favorites'));
    }
}
