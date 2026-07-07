<?php

namespace App\Controller;

use App\Entity\CustomizationRequest;
use App\Entity\User;
use App\Enum\CustomizationStatus;
use App\Repository\CustomizationRequestRepository;
use App\Repository\ProductRepository;
use App\Service\OrderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
class CustomizationController extends AbstractController
{
    #[Route('/mon-compte/personnalisations', name: 'app_customization_index', methods: ['GET'])]
    public function index(CustomizationRequestRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/customizations.html.twig', [
            'requests' => $repo->findByCustomer($user),
        ]);
    }

    #[Route('/creation/{slug}/devis', name: 'app_customization_request', methods: ['POST'])]
    public function requestQuote(string $slug, Request $request, ProductRepository $products, EntityManagerInterface $em): Response
    {
        $product = $products->findOneBySlugWithRelations($slug);
        if ($product === null || !$product->isCustomizable()) {
            throw $this->createNotFoundException();
        }

        $description = trim((string) $request->request->get('description'));
        if (!$this->isCsrfTokenValid('customization_request'.$product->getId(), (string) $request->request->get('_token')) || $description === '') {
            $this->addFlash('error', 'Merci de décrire votre demande.');

            return $this->redirectToRoute('app_product_show', ['slug' => $slug]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $cr = (new CustomizationRequest())
            ->setCustomer($user)
            ->setProduct($product)
            ->setDescription($description)
            ->setStatus(CustomizationStatus::Pending);
        $em->persist($cr);
        $em->flush();

        $this->addFlash('success', 'Votre demande de devis a été envoyée ! Vous recevrez une proposition de prix.');

        return $this->redirectToRoute('app_customization_index');
    }

    #[Route('/mon-compte/personnalisations/{id}/accepter', name: 'app_customization_accept', methods: ['POST'])]
    public function accept(CustomizationRequest $cr, Request $request, EntityManagerInterface $em, OrderFactory $orderFactory): Response
    {
        $this->decide($cr, $request, CustomizationStatus::Accepted, 'accept');

        // Le devis accepté devient une commande "à payer", gérable comme les autres.
        $order = $orderFactory->createPendingOrderFromCustomization($cr);
        $cr->setOrderItem($order->getItems()->first() ?: null);
        $em->flush();

        $this->addFlash('success', 'Devis accepté ! Finalisez le paiement pour lancer la fabrication.');

        return $this->redirectToRoute('app_checkout_pay_order', ['id' => $order->getId()]);
    }

    #[Route('/mon-compte/personnalisations/{id}/refuser', name: 'app_customization_refuse', methods: ['POST'])]
    public function refuse(CustomizationRequest $cr, Request $request, EntityManagerInterface $em): Response
    {
        $this->decide($cr, $request, CustomizationStatus::Refused, 'refuse');
        $em->flush();
        $this->addFlash('info', 'Devis refusé.');

        return $this->redirectToRoute('app_customization_index');
    }

    private function decide(CustomizationRequest $cr, Request $request, CustomizationStatus $status, string $token): void
    {
        if ($cr->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid($token.$cr->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ($cr->getStatus() !== CustomizationStatus::Priced) {
            throw $this->createAccessDeniedException('Cette demande ne peut plus être modifiée.');
        }
        $cr->setStatus($status);
        $cr->setDecidedAt(new \DateTimeImmutable());
    }
}
