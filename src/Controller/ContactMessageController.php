<?php

namespace App\Controller;

use App\Repository\ContactMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_ADMIN')]
final class ContactMessageController extends AbstractController
{
    #[Route('', name: 'app_message', methods: ['GET'])]
    public function index(
        ContactMessageRepository $messageRepository
    ): Response {
        $messages = $messageRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render('message/index.html.twig', [
            'messages' => $messages,
        ]);
    }
}
