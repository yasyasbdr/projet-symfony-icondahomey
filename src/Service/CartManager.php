<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\SelectedType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Encapsule la logique du panier persiste : recuperation/creation du panier
 * du client connecte et ajout d'articles. On passe TOUJOURS par le repository /
 * l'entity manager pour sauvegarder, jamais en manipulant la session directement.
 */
class CartManager
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $user->getCart();
        if ($cart === null) {
            $cart = new Cart();
            $cart->setOwner($user);
            $user->setCart($cart);
            $this->em->persist($cart);
        }

        return $cart;
    }

    /** @param array<string, mixed>|null $measurements */
    public function addProduct(
        User $user,
        Product $product,
        SelectedType $type = SelectedType::Physical,
        int $quantity = 1,
        ?array $measurements = null,
        ?string $note = null,
    ): CartItem {
        $cart = $this->getOrCreateCart($user);

        $item = new CartItem();
        $item->setProduct($product)
            ->setSelectedType($type)
            ->setQuantity($quantity)
            ->setMeasurements($measurements)
            ->setCustomizationNote($note);

        $cart->addItem($item);
        $cart->touch();

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function clear(Cart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $cart->removeItem($item);
            $this->em->remove($item);
        }
        $cart->touch();
        $this->em->flush();
    }
}
