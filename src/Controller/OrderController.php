<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_ADMIN')]
final class OrderController extends AbstractController
{
    #[Route('', name: 'app_order', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'activePage' => 'orders',
        ]);
    }

    #[Route('/{id}/status', name: 'app_order_status', methods: ['POST'])]
    public function updateStatus(
        Order $order,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $allowedStatuses = [
            'placed',
            'processing',
            'shipped',
            'delivered',
            'cancelled',
        ];

        $status = $request->request->get('status');

        if (!$this->isCsrfTokenValid(
            'order-status-' . $order->getId(),
            $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_order');
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('error', 'Invalid order status.');
            return $this->redirectToRoute('app_order');
        }

        $order->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Order status updated successfully.');

        return $this->redirectToRoute('app_order');
    }
}
