<?php

namespace App\Controller\Admin;

use App\Enum\OrderStatus;
use App\Repository\CustomizationRequestRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        OrderRepository $orders,
        ProductRepository $products,
        UserRepository $users,
        CustomizationRequestRepository $customizations,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'pendingOrders' => $orders->findPendingForModeration(),
            'pendingCustomizations' => $customizations->findPendingForAdmin(),
            'stats' => [
                'orders_pending' => $orders->countByStatus(OrderStatus::Pending),
                'orders_in_progress' => $orders->countByStatus(OrderStatus::InProgress),
                'products' => $products->count([]),
                'clients' => $users->count([]),
            ],
        ]);
    }
}
