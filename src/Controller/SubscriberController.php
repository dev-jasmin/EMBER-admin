<?php

namespace App\Controller;

use App\Repository\NewsletterSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscribers')]
#[IsGranted('ROLE_ADMIN')]
final class SubscriberController extends AbstractController
{
    #[Route('', name: 'app_subscriber', methods: ['GET'])]
    public function index(
        NewsletterSubscriberRepository $subscriberRepository
    ): Response {
        $subscribers = $subscriberRepository->findBy(
            [],
            ['subscribedAt' => 'DESC']
        );

        return $this->render('subscriber/index.html.twig', [
            'subscribers' => $subscribers,
            'activePage' => 'subscribers',
        ]);
    }
}
