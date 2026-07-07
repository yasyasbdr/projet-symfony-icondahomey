<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatusHistory;
use App\Entity\Payment;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme un panier en commande : snapshot des lignes (nom + prix), calcul
 * du total, création du paiement et de l'historique de statut. Centralise la
 * logique métier du passage de commande (appelée par le CheckoutController).
 */
class OrderFactory
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function createFromCart(Cart $cart, string $paymentMethod = 'Carte bancaire', ?string $stripeIntentId = null): Order
    {
        $customer = $cart->getOwner();

        $order = new Order();
        $order->setCustomer($customer)
            ->setReference($this->generateReference())
            ->setStatus(OrderStatus::Confirmed)
            ->setProgressPercent(10);

        // Adresse de livraison : on prend l'adresse par défaut du client si elle existe.
        foreach ($customer->getAddresses() as $address) {
            if ($address->isDefault()) {
                $order->setShippingAddress($address);
                break;
            }
        }

        $total = '0.00';
        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $item = (new OrderItem())
                ->setProduct($product)
                ->setProductName((string) $product?->getName())
                ->setUnitPrice((string) $product?->getBasePrice())
                ->setQuantity($cartItem->getQuantity())
                ->setSelectedType($cartItem->getSelectedType())
                ->setMeasurements($cartItem->getMeasurements());
            $order->addItem($item);
            $this->em->persist($item);
            $total = bcadd($total, $item->getLineTotal(), 2);
        }
        $order->setTotalAmount($total);

        // Historique : la commande vient d'être payée et confirmée.
        $history = (new OrderStatusHistory())
            ->setStatus(OrderStatus::Confirmed)
            ->setProgressPercent(10)
            ->setComment('Commande confirmée après paiement.');
        $order->addStatusHistory($history);
        $this->em->persist($history);

        // Paiement.
        $payment = (new Payment())
            ->setAmount($total)
            ->setCurrency('EUR')
            ->setStatus('paid')
            ->setMethod($paymentMethod)
            ->setStripePaymentIntentId($stripeIntentId)
            ->setPaidAt(new \DateTimeImmutable());
        $order->setPayment($payment);
        $this->em->persist($payment);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    private function generateReference(): string
    {
        return 'CMD-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
    }
}
