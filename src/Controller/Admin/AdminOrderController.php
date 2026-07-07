<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderStatusHistory;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/commandes')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orders): Response
    {
        return $this->render('admin/orders.html.twig', [
            'orders' => $orders->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order_show.html.twig', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    #[Route('/{id}/maj', name: 'admin_order_update', methods: ['POST'])]
    public function update(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('order_update'.$order->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $statusValue = (string) $request->request->get('status');
        $progress = $request->request->getInt('progress');
        $comment = trim((string) $request->request->get('comment'));

        $status = OrderStatus::tryFrom($statusValue) ?? $order->getStatus();
        $order->setStatus($status);
        $order->setProgressPercent($progress);

        if (in_array($status, [OrderStatus::Shipped, OrderStatus::Completed], true)) {
            $tracking = trim((string) $request->request->get('tracking_number'));
            $carrier = trim((string) $request->request->get('carrier'));
            if ($tracking !== '') {
                $order->setTrackingNumber($tracking);
            }
            if ($carrier !== '') {
                $order->setCarrier($carrier);
            }
        }

        $history = new OrderStatusHistory();
        $history->setStatus($status)
            ->setProgressPercent($progress)
            ->setComment($comment !== '' ? $comment : null);
        $order->addStatusHistory($history);
        $em->persist($history);
        $em->flush();

        $this->addFlash('success', 'Commande mise a jour.');

        return $this->redirectToRoute('admin_order_show', ['id' => $order->getId()]);
    }
}
