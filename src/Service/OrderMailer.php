<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Envoi des emails transactionnels lies aux commandes. Le mailer route les
 * messages via Messenger (transport async) : l'utilisateur n'attend pas l'envoi.
 * IMPORTANT : appeler ces methodes APRES le flush() de l'ORM, pour que la
 * commande possede bien son id et ses relations en base.
 */
class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress = 'no-reply@icon-dahomey.local',
    ) {
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $customer = $order->getCustomer();
        if ($customer === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, 'Icon Dahomey'))
            ->to(new Address((string) $customer->getEmail(), $customer->getFullName()))
            ->subject('Votre commande '.$order->getReference().' est confirmee')
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }
}
