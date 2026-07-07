<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;

/**
 * Intégration du paiement Stripe (Checkout Session hébergée).
 * Volontairement optionnelle : si aucune clé secrète n'est configurée (ou si le
 * SDK stripe/stripe-php n'est pas installé), le service se désactive et le
 * CheckoutController bascule sur un paiement simulé. Ainsi la démo fonctionne
 * toujours, et le vrai tunnel s'active dès qu'une clé de test est fournie.
 */
class StripeCheckout
{
    public function __construct(private readonly string $secretKey = '')
    {
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== '' && class_exists(\Stripe\Stripe::class);
    }

    /** Crée une session de paiement Stripe et retourne l'URL de redirection. */
    public function createSessionUrl(Cart $cart, string $successUrl, string $cancelUrl, string $customerEmail): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        \Stripe\Stripe::setApiKey($this->secretKey);

        $lineItems = [];
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => (string) $product?->getName()],
                    'unit_amount' => (int) round(((float) $product?->getBasePrice()) * 100),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $customerEmail,
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);

        return $session->url;
    }

    /** Crée une session Stripe pour payer une commande existante ("à payer"). */
    public function createSessionUrlForOrder(Order $order, string $successUrl, string $cancelUrl, string $customerEmail): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        \Stripe\Stripe::setApiKey($this->secretKey);

        $lineItems = [];
        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => (string) $item->getProductName()],
                    'unit_amount' => (int) round(((float) $item->getUnitPrice()) * 100),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $customerEmail,
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);

        return $session->url;
    }

    public function retrievePaymentIntentId(string $sessionId): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }
        \Stripe\Stripe::setApiKey($this->secretKey);
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        return $session->payment_intent ? (string) $session->payment_intent : null;
    }
}
