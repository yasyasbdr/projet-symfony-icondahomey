<?php

namespace App\Tests\Unit;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\PhysicalCreation;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire : verifie le calcul du total du panier (logique metier pure,
 * sans base de donnees).
 */
class CartTest extends TestCase
{
    public function testCartTotalSumsLineItems(): void
    {
        $bag = (new PhysicalCreation())->setName('Sac')->setBasePrice('54.00');
        $top = (new PhysicalCreation())->setName('Top')->setBasePrice('48.00');

        $cart = new Cart();
        $cart->addItem((new CartItem())->setProduct($bag)->setQuantity(1));
        $cart->addItem((new CartItem())->setProduct($top)->setQuantity(2));

        // 54.00 * 1 + 48.00 * 2 = 150.00
        self::assertSame('150.00', $cart->getTotal());
        self::assertSame(3, $cart->getItemCount());
    }

    public function testEmptyCartIsZero(): void
    {
        self::assertSame('0.00', (new Cart())->getTotal());
    }
}
