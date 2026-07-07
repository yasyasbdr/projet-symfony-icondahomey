<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CustomizationRequest;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatusHistory;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\SelectedType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fabrique de commandes.
 *  - createFromCart()                  : panier -> commande payée (checkout classique)
 *  - createPendingOrderFromCustomization() : devis accepté -> commande "à payer"
 *  - markOrderPaid()                   : passe une commande en payée + confirmée
 *
 * On "snapshot" toujours le nom et le prix dans OrderItem pour figer l'historique.
 */
class OrderFactory
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** Panier -> commande immédiatement payée. */
    public function createFromCart(Cart $cart, string $paymentMethod = 'Carte bancaire', ?string $stripeIntentId = null): Order
    {
        $order = $this->newPendingOrder($cart->getOwner());

        $total = '0.00';
        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $line = $this->addLine(
                $order,
                $product,
                (string) $product?->getName(),
                (string) $product?->getBasePrice(),
                $cartItem->getQuantity(),
                $cartItem->getSelectedType(),
                $cartItem->getMeasurements()
            );
            $total = bcadd($total, $line, 2);
        }
        $order->setTotalAmount($total);
        $this->em->persist($order);

        $this->markOrderPaid($order, $paymentMethod, $stripeIntentId);

        return $order;
    }

    /** Devis de personnalisation accepté -> commande "à payer" (sans paiement). */
    public function createPendingOrderFromCustomization(CustomizationRequest $cr): Order
    {
        $order = $this->newPendingOrder($cr->getCustomer());
        $product = $cr->getProduct();

        $line = $this->addLine(
            $order,
            $product,
            (string) $product?->getName().' (personnalisé)',
            (string) $cr->getProposedPrice(),
            1,
            SelectedType::Physical,
            null
        );
        $order->setTotalAmount($line);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    /** Passe une commande en payée + confirmée (paiement enregistré, historique mis à jour). */
    public function markOrderPaid(Order $order, string $method, ?string $stripeIntentId = null): void
    {
        $order->setStatus(OrderStatus::Confirmed)->setProgressPercent(10);

        $history = (new OrderStatusHistory())
            ->setStatus(OrderStatus::Confirmed)
            ->setProgressPercent(10)
            ->setComment('Commande confirmée après paiement.');
        $order->addStatusHistory($history);
        $this->em->persist($history);

        $payment = (new Payment())
            ->setAmount($order->getTotalAmount())
            ->setCurrency('EUR')
            ->setStatus('paid')
            ->setMethod($method)
            ->setStripePaymentIntentId($stripeIntentId)
            ->setPaidAt(new \DateTimeImmutable());
        $order->setPayment($payment);
        $this->em->persist($payment);

        $this->em->flush();
    }

    private function newPendingOrder(?User $customer): Order
    {
        $order = new Order();
        $order->setCustomer($customer)
            ->setReference($this->generateReference())
            ->setStatus(OrderStatus::Pending)
            ->setProgressPercent(0);

        if ($customer !== null) {
            foreach ($customer->getAddresses() as $address) {
                if ($address->isDefault()) {
                    $order->setShippingAddress($address);
                    break;
                }
            }
        }

        return $order;
    }

    /** @param array<string, mixed>|null $measurements */
    private function addLine(Order $order, ?Product $product, string $name, string $unitPrice, int $qty, SelectedType $type, ?array $measurements): string
    {
        $item = (new OrderItem())
            ->setProduct($product)
            ->setProductName($name)
            ->setUnitPrice($unitPrice)
            ->setQuantity($qty)
            ->setSelectedType($type)
            ->setMeasurements($measurements);
        $order->addItem($item);
        $this->em->persist($item);

        return $item->getLineTotal();
    }

    private function generateReference(): string
    {
        return 'CMD-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
    }
}
