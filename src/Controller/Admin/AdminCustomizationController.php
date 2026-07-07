<?php

namespace App\Controller\Admin;

use App\Entity\CustomizationRequest;
use App\Enum\CustomizationStatus;
use App\Repository\CustomizationRequestRepository;
use App\Service\SmsSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des demandes de personnalisation côté administration :
 * l'admin fixe le prix proposé, le client l'accepte ou le refuse ensuite.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/personnalisations')]
class AdminCustomizationController extends AbstractController
{
    #[Route('', name: 'admin_customization_index', methods: ['GET'])]
    public function index(CustomizationRequestRepository $repo): Response
    {
        return $this->render('admin/customizations/index.html.twig', [
            'requests' => $repo->findAllForAdmin(),
        ]);
    }

    #[Route('/{id}/prix', name: 'admin_customization_price', methods: ['POST'])]
    public function price(CustomizationRequest $request, Request $httpRequest, EntityManagerInterface $em, SmsSender $sms): Response
    {
        if (!$this->isCsrfTokenValid('customization_price'.$request->getId(), (string) $httpRequest->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $price = str_replace(',', '.', trim((string) $httpRequest->request->get('proposed_price')));
        if ($price === '' || !is_numeric($price)) {
            $this->addFlash('error', 'Prix invalide.');

            return $this->redirectToRoute('admin_customization_index');
        }

        $request->setProposedPrice($price);
        $request->setStatus(CustomizationStatus::Priced);
        $em->flush();

        // Notification SMS au client (via l'API externe HttpClient).
        if ($request->getCustomer()?->getPhone()) {
            $sms->send(
                $request->getCustomer()->getPhone(),
                sprintf('Icon Dahomey : votre devis de personnalisation est prêt (%s €). Connectez-vous pour l\'accepter.', $price)
            );
        }

        $this->addFlash('success', 'Prix proposé au client.');

        return $this->redirectToRoute('admin_customization_index');
    }
}
