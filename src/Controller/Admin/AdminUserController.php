<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des comptes utilisateurs, réservée au super-admin
 * (blocage/déblocage et suppression), conformément au cahier des charges.
 */
#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/admin/clients')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/basculer-blocage', name: 'admin_user_toggle_block', methods: ['POST'])]
    public function toggleBlock(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle_block'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        // Sécurité : on ne se bloque pas soi-même.
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas bloquer votre propre compte.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->setIsBlocked(!$user->isBlocked());
        $em->flush();

        $this->addFlash('success', $user->isBlocked()
            ? 'Le compte de '.$user->getFullName().' a été bloqué.'
            : 'Le compte de '.$user->getFullName().' a été débloqué.');

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/supprimer', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_user'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('admin_user_index');
        }

        try {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Compte supprimé.');
        } catch (ForeignKeyConstraintViolationException) {
            // L'utilisateur a des commandes : on préfère le bloquer que casser l'historique.
            $this->addFlash('error', 'Impossible de supprimer : ce client a des commandes. Bloquez-le plutôt.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
