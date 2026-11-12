<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\ContactMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        NewsletterSubscriberRepository $subscriberRepository,
        ContactMessageRepository $messageRepository
    ): Response {

        // Only Admin accounts can access the Admin Dashboard.
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException(
                'Only administrators can access the dashboard.'
            );
        }

        $totalRevenue = $orderRepository
            ->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('dashboard/index.html.twig', [
            'isAdmin' => true,
            'activePage' => 'dashboard',

            // User statistics
            'totalUsers' => $userRepository->count([]),
            'totalStaff' => $userRepository->countByRole('ROLE_STAFF'),

            // Product statistics
            'totalProducts' => $productRepository->count([]),
            'activeProducts' => $productRepository->count([
                'isActive' => true,
            ]),

            // Order statistics
            'totalOrders' => $orderRepository->count([]),
            'totalRevenue' => $totalRevenue,

            // Other records
            'totalSubscribers' => $subscriberRepository->count([]),
            'totalMessages' => $messageRepository->count([]),
        ]);
    }
}
