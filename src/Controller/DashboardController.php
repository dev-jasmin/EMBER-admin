<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'totalUsers' => $userRepository->count([]),
            'totalStaff' => $userRepository->countByRole('ROLE_STAFF'),
            'totalProducts' => $productRepository->count([]),
        ]);
    }
}
