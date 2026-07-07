<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Security\OrderVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
#[Route('/mon-compte')]
class OrderController extends AbstractController
{
    #[Route('/commandes', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orders): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/orders.html.twig', [
            'orders' => $orders->findByCustomerWithItems($user),
        ]);
    }

    #[Route('/commandes/{id}', name: 'app_order_show', methods: ['GET', 'POST'])]
    public function show(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        // Controle d'acces via le Voter personnalise : seul le proprietaire (ou un admin).
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);

        // Messagerie interne liee a la commande (une conversation par commande, simplifiee).
        if ($request->isMethod('POST') && $request->request->has('message')) {
            $content = trim((string) $request->request->get('message'));
            if ($content !== '') {
                $conversation = $order->getCustomer()?->getConversations()->first() ?: null;
                if ($conversation) {
                    $message = new Message();
                    $message->setConversation($conversation)
                        ->setSender($this->getUser())
                        ->setContent($content);
                    $em->persist($message);
                    $em->flush();
                    $this->addFlash('success', 'Message envoye.');
                }
            }

            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        return $this->render('account/order_show.html.twig', [
            'order' => $order,
            'timeline' => OrderStatus::timeline(),
        ]);
    }

    #[Route('/commandes/{id}/annuler', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(OrderVoter::CANCEL, $order);

        if ($this->isCsrfTokenValid('cancel'.$order->getId(), (string) $request->request->get('_token'))) {
            $order->setStatus(OrderStatus::Cancelled);
            $em->flush();
            $this->addFlash('success', 'Commande annulee.');
        }

        return $this->redirectToRoute('app_order_index');
    }
}
