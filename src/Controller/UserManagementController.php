<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\EditUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/management')]
#[IsGranted('ROLE_ADMIN')]
final class UserManagementController extends AbstractController
{
    #[Route('', name: 'app_user_management', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $showModal = false;

        if ($form->isSubmitted()) {
            $showModal = true;

            if ($form->isValid()) {
                $usernameExists = $userRepository->findOneBy([
                    'username' => $user->getUsername(),
                ]);

                $emailExists = $userRepository->findOneBy([
                    'email' => $user->getEmail(),
                ]);

                if ($usernameExists || $emailExists) {
                    $this->addFlash(
                        'error',
                        'The username or email address is already in use.'
                    );
                } else {
                    $plainPassword = $form->get('password')->getData();

                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $plainPassword)
                    );

                    $user->setRoles([
                        $form->get('role')->getData(),
                    ]);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Account created successfully.');

                    return $this->redirectToRoute('app_user_management');
                }
            }
        }

        return $this->render('user_management/index.html.twig', [
            'users' => $userRepository->findAll(),
            'form' => $form,
            'showModal' => $showModal,
            'editForm' => null,
            'editingUser' => null,
            'activePage' => 'users',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_management_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $editForm = $this->createForm(EditUserType::class, $user);

        $editForm->get('role')->setData(
            in_array('ROLE_ADMIN', $user->getRoles(), true)
                ? 'ROLE_ADMIN'
                : 'ROLE_STAFF'
        );

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $usernameExists = $userRepository->findOneBy([
                'username' => $user->getUsername(),
            ]);

            $emailExists = $userRepository->findOneBy([
                'email' => $user->getEmail(),
            ]);

            if (
                ($usernameExists && $usernameExists->getId() !== $user->getId()) ||
                ($emailExists && $emailExists->getId() !== $user->getId())
            ) {
                $this->addFlash(
                    'error',
                    'The username or email address is already in use.'
                );
            } else {
                $selectedRole = $editForm->get('role')->getData();
                $currentUser = $this->getUser();

                if (
                    $currentUser instanceof User &&
                    $user->getId() === $currentUser->getId() &&
                    $selectedRole !== 'ROLE_ADMIN'
                ) {
                    $this->addFlash(
                        'error',
                        'You cannot remove your own Administrator role.'
                    );

                    return $this->redirectToRoute('app_user_management');
                }

                $user->setRoles([$selectedRole]);

                $plainPassword = $editForm->get('password')->getData();

                if (!empty($plainPassword)) {
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $plainPassword)
                    );
                }

                $entityManager->flush();

                $this->addFlash('success', 'Account updated successfully.');

                return $this->redirectToRoute('app_user_management');
            }
        }

        return $this->render('user_management/index.html.twig', [
            'users' => $userRepository->findAll(),
            'form' => $this->createForm(UserType::class, new User()),
            'showModal' => false,
            'editForm' => $editForm,
            'editingUser' => $user,
            'activePage' => 'users',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_management_delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (
            $currentUser instanceof User &&
            $user->getId() === $currentUser->getId()
        ) {
            $this->addFlash('error', 'You cannot delete your own account.');

            return $this->redirectToRoute('app_user_management');
        }

        if (!$this->isCsrfTokenValid(
            'delete-user-' . $user->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Account deleted successfully.');

        return $this->redirectToRoute('app_user_management');
    }

    #[Route('/{id}/toggle-status', name: 'app_user_management_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (
            $currentUser instanceof User &&
            $user->getId() === $currentUser->getId()
        ) {
            $this->addFlash('error', 'You cannot deactivate your own account.');

            return $this->redirectToRoute('app_user_management');
        }

        if (!in_array('ROLE_STAFF', $user->getRoles(), true)) {
            $this->addFlash('error', 'Only Staff accounts can be deactivated.');

            return $this->redirectToRoute('app_user_management');
        }

        if (!$this->isCsrfTokenValid(
            'toggle-user-status-' . $user->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user->setIsActive(!$user->isActive());

        $entityManager->flush();

        $this->addFlash(
            'success',
            $user->isActive()
                ? 'Staff account activated successfully.'
                : 'Staff account deactivated successfully.'
        );

        return $this->redirectToRoute('app_user_management');
    }
}
