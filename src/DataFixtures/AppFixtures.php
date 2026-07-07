<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Conversation;
use App\Entity\CustomizationRequest;
use App\Entity\DigitalPattern;
use App\Entity\Message;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatusHistory;
use App\Entity\Payment;
use App\Entity\PhysicalCreation;
use App\Entity\ProductImage;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\CustomizationStatus;
use App\Enum\OrderStatus;
use App\Enum\SelectedType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private ?User $adminRef = null;

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // --- Catégories ---
        $categories = [];
        foreach (['Sacs', 'Hauts', 'Accessoires', 'Patrons'] as $name) {
            $c = (new Category())->setName($name)->setSlug($this->slugify($name))
                ->setDescription($faker->sentence(8));
            $manager->persist($c);
            $categories[$name] = $c;
        }

        // --- Tags ---
        $tags = [];
        foreach (['Fait main', 'Coton', 'Perles', 'Bohème', 'Cadeau'] as $t) {
            $tag = (new Tag())->setName($t)->setSlug($this->slugify($t));
            $manager->persist($tag);
            $tags[] = $tag;
        }

        $products = [];

        // --- Créations physiques (avec vraies photos) ---
        // [nom, catégorie, prix, personnalisable, mensurations, [images...], description]
        $physicalData = [
            ['Sac Perle Crochet', 'Sacs', '54.00', true, false, ['sac1.jpg', 'sac2.jpg'],
                "Sac à main crocheté main en fil nacré, orné d'un bijou de sac en perles. Anse tressée et fermeture soignée. Pièce unique."],
            ['Pull Cache-Cœur Crochet', 'Hauts', '75.00', true, true, ['haut1.jpg', 'haut2.jpg', 'haut3.jpg'],
                "Pull court cache-cœur en maille épaisse crème, bande transversale devant et nœud à nouer dans le dos. Manches larges retroussées."],
            ['Ensemble Kaki Crochet', 'Hauts', '89.00', true, true, ['ensemble.jpg'],
                "Ensemble deux pièces au crochet : brassière et short taille mi-haute, finitions à froufrous et détails perle. Coton kaki."],
            ['Moufles Perlées Crochet', 'Accessoires', '35.00', false, false, ['moufles.jpg'],
                "Moufles douces au crochet, poignet côtelé et rangée de perles. Chaudes et élégantes pour l'hiver."],
        ];
        foreach ($physicalData as [$name, $cat, $price, $custom, $measures, $images, $desc]) {
            $p = new PhysicalCreation();
            $p->setName($name)->setSlug($this->slugify($name))
                ->setCategory($categories[$cat])
                ->setDescription($desc)
                ->setBasePrice($price)
                ->setIsCustomizable($custom)
                ->setStock($faker->numberBetween(2, 10))
                ->setRequiresMeasurements($measures);
            foreach ($faker->randomElements($tags, 2) as $tag) {
                $p->addTag($tag);
            }
            foreach ($images as $i => $file) {
                $p->addImage((new ProductImage())->setFilename($file)->setAltText($name)
                    ->setPosition($i)->setIsMain($i === 0));
            }
            $manager->persist($p);
            $products[] = $p;
        }

        // --- Patrons PDF (depuis les croquis) ---
        $patternData = [
            ['Patron - Ensemble Cache-Cœur', '24.00', 'intermediaire', 14, 'croquis3.jpg',
                "Patron PDF détaillé du haut cache-cœur et de la jupe longue, bords lemon peel. Croquis, mesures et explications rang par rang."],
            ['Patron - Tenue Pull & Sac', '22.00', 'avance', 18, 'croquis4.jpg',
                "Patron PDF de la tenue n°3 : pull col ovale à nœud, sac en ruban satiné et moufles. Pour crocheteuses confirmées."],
        ];
        foreach ($patternData as [$name, $price, $level, $pages, $img, $desc]) {
            $p = new DigitalPattern();
            $p->setName($name)->setSlug($this->slugify($name))
                ->setCategory($categories['Patrons'])
                ->setDescription($desc)
                ->setBasePrice($price)
                ->setDifficultyLevel($level)
                ->setPageCount($pages)
                ->setPdfFilename($this->slugify($name).'.pdf');
            $p->addImage((new ProductImage())->setFilename($img)->setAltText($name)->setIsMain(true));
            $manager->persist($p);
            $products[] = $p;
        }

        // --- Comptes de référence ---
        $client = $this->makeUser($manager, 'cliente@icon-dahomey.local', 'password', ['ROLE_CLIENT'], 'Isabella', 'Marchand', '0612345678');
        $this->makeUser($manager, 'admin@icon-dahomey.local', 'password', ['ROLE_ADMIN'], 'Awa', 'Kponou', '0698765432');
        $this->makeUser($manager, 'super@icon-dahomey.local', 'password', ['ROLE_SUPER_ADMIN'], 'Super', 'Admin', null);
        for ($i = 0; $i < 4; ++$i) {
            $this->makeUser($manager, $faker->unique()->safeEmail(), 'password', ['ROLE_CLIENT'],
                $faker->firstName(), $faker->lastName(), $faker->phoneNumber());
        }

        // --- Adresse ---
        $address = (new Address())->setLabel('Domicile')
            ->setRecipientName($client->getFullName())
            ->setLine1('12 rue des Artisans')->setPostalCode('75011')
            ->setCity('Paris')->setCountry('France')->setIsDefault(true);
        $client->addAddress($address);
        $manager->persist($address);

        // --- Favoris ---
        foreach ([$products[0], $products[1], $products[4]] as $fav) {
            $client->addFavorite($fav);
        }

        // --- Panier ---
        $cart = new Cart();
        $cart->setOwner($client);
        $client->setCart($cart);
        $ci = (new CartItem())->setProduct($products[2])->setQuantity(1)->setSelectedType(SelectedType::Physical);
        $cart->addItem($ci);
        $manager->persist($cart);
        $manager->persist($ci);

        // --- Commande de démonstration ---
        $order = new Order();
        $order->setCustomer($client)
            ->setReference('CMD-2026-0001')
            ->setStatus(OrderStatus::InProgress)
            ->setProgressPercent(45)
            ->setShippingAddress($address)
            ->setCarrier('Colissimo');

        $total = '0.00';
        foreach ([[$products[0], 1], [$products[1], 2]] as [$prod, $qty]) {
            $item = (new OrderItem())
                ->setProduct($prod)
                ->setProductName($prod->getName())
                ->setUnitPrice($prod->getBasePrice())
                ->setQuantity($qty)
                ->setSelectedType(SelectedType::Physical)
                ->setMeasurements($prod instanceof PhysicalCreation && $prod->requiresMeasurements()
                    ? ['poitrine' => 88, 'taille' => 70, 'hanches' => 95, 'hauteur' => 60] : null);
            $order->addItem($item);
            $manager->persist($item);
            $total = bcadd($total, $item->getLineTotal(), 2);
        }
        $order->setTotalAmount($total);

        foreach ([[OrderStatus::Pending, 0, '-3 days'], [OrderStatus::Confirmed, 15, '-2 days'], [OrderStatus::InProgress, 45, '-1 day']] as [$st, $pct, $when]) {
            $h = (new OrderStatusHistory())->setStatus($st)->setProgressPercent($pct)
                ->setComment(null)->setCreatedAt(new \DateTimeImmutable($when));
            $order->addStatusHistory($h);
            $manager->persist($h);
        }

        $payment = (new Payment())->setAmount($total)->setCurrency('EUR')
            ->setStatus('paid')->setMethod('Carte bancaire')
            ->setStripePaymentIntentId('pi_demo_123')->setPaidAt(new \DateTimeImmutable('-3 days'));
        $order->setPayment($payment);
        $manager->persist($payment);
        $manager->persist($order);

        // --- Conversation + messages ---
        $conv = (new Conversation())->setClient($client)->setSubject('À propos de ma commande CMD-2026-0001');
        $m1 = (new Message())->setSender($client)->setContent('Bonjour, serait-il possible d\'ajouter une doublure au sac ?');
        $conv->addMessage($m1);
        $admin = $this->adminRef ?? throw new \RuntimeException('Admin non initialisé.');
        $m2 = (new Message())->setSender($admin)->setContent('Bonjour Isabella, oui c\'est possible ! Je vous prépare un devis.');
        $conv->addMessage($m2);
        $manager->persist($conv);
        $manager->persist($m1);
        $manager->persist($m2);

        // --- Demande de personnalisation ---
        $cr = (new CustomizationRequest())->setCustomer($client)->setProduct($products[0])
            ->setDescription('J\'aimerais ce sac en coton écru avec une anse plus longue.')
            ->setStatus(CustomizationStatus::Pending);
        $manager->persist($cr);

        $manager->flush();
    }

    /** @param list<string> $roles */
    private function makeUser(ObjectManager $manager, string $email, string $password, array $roles, string $first, string $last, ?string $phone): User
    {
        $user = new User();
        $user->setEmail($email)->setRoles($roles)->setFirstName($first)
            ->setLastName($last)->setPhone($phone)->setIsVerified(true);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $manager->persist($user);

        if (in_array('ROLE_ADMIN', $roles, true) && $this->adminRef === null) {
            $this->adminRef = $user;
        }

        return $user;
    }

    private function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;

        return trim($text, '-');
    }
}
