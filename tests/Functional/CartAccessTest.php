<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test fonctionnel de sécurité : le panier et le checkout sont protégés.
 * Un visiteur anonyme est redirigé vers la page de connexion.
 */
class CartAccessTest extends WebTestCase
{
    public function testCartRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/panier');

        self::assertResponseRedirects();
    }

    public function testCheckoutRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/checkout');

        self::assertResponseRedirects();
    }
}
