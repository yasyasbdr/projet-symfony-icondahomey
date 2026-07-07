<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages légales et formulaire de contact (service client).
 */
class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_mentions', methods: ['GET'])]
    public function mentions(): Response
    {
        return $this->render('legal/mentions.html.twig');
    }

    #[Route('/conditions-generales-de-vente', name: 'app_legal_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_legal_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            // Démo : on confirme la réception. En production, on enverrait un email
            // (Symfony Mailer) ou on stockerait le message en base.
            $this->addFlash('success', 'Merci ! Votre message a bien été envoyé, nous vous répondrons rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('legal/contact.html.twig');
    }
}
