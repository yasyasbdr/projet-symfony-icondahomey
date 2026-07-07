<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CartManager;
use App\Service\OrderFactory;
use App\Service\OrderMailer;
use App\Service\StripeCheckout;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CartManager $cartManager,
        StripeCheckout $stripe,
        OrderFactory $orderFactory,
        OrderMailer $mailer,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartManager->getOrCreateCart($user);

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('info', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart');
        }

        // Soumission du récapitulatif -> paiement.
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            // 1) Stripe configuré : on redirige vers le tunnel de paiement hébergé.
            $successUrl = $this->generateUrl('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('app_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $stripeUrl = $stripe->createSessionUrl($cart, $successUrl, $cancelUrl, (string) $user->getEmail());

            if ($stripeUrl !== null) {
                return $this->redirect($stripeUrl);
            }

            // 2) Pas de Stripe : paiement simulé, on crée directement la commande.
            $order = $orderFactory->createFromCart($cart, 'Carte bancaire (simulé)');
            $cartManager->clear($cart);
            $this->safeSendEmail($mailer, $order);

            $this->addFlash('success', 'Paiement accepté, votre commande est confirmée !');

            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'stripe_enabled' => $stripe->isConfigured(),
        ]);
    }

    /** Retour de Stripe après un paiement réussi : on finalise la commande. */
    #[Route('/succes', name: 'app_checkout_success', methods: ['GET'])]
    public function success(
        Request $request,
        CartManager $cartManager,
        StripeCheckout $stripe,
        OrderFactory $orderFactory,
        OrderMailer $mailer,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartManager->getOrCreateCart($user);

        // Le panier est vidé après création : évite les doublons si on recharge la page.
        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('info', 'Aucune commande en attente.');

            return $this->redirectToRoute('app_order_index');
        }

        $sessionId = $request->query->get('session_id');
        $intentId = $sessionId ? $stripe->retrievePaymentIntentId($sessionId) : null;

        $order = $orderFactory->createFromCart($cart, 'Stripe', $intentId);
        $cartManager->clear($cart);
        $this->safeSendEmail($mailer, $order);

        $this->addFlash('success', 'Paiement Stripe validé, merci pour votre commande !');

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    #[Route('/annule', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('info', 'Paiement annulé, votre panier est conservé.');

        return $this->redirectToRoute('app_cart');
    }

    /** L'envoi d'email ne doit jamais casser le tunnel de commande. */
    private function safeSendEmail(OrderMailer $mailer, \App\Entity\Order $order): void
    {
        try {
            $mailer->sendOrderConfirmation($order);
        } catch (\Throwable) {
            // On ignore : la commande est déjà enregistrée.
        }
    }
}
