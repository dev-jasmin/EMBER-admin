<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-first-admin',
    description: 'Create the first EMBER administrator account'
)]
final class CreateFirstAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Prevent creating another first administrator.
        $users = $this->entityManager
            ->getRepository(User::class)
            ->findAll();

        foreach ($users as $existingUser) {
            if (in_array('ROLE_ADMIN', $existingUser->getRoles(), true)) {
                $io->error('An administrator account already exists.');
                return Command::FAILURE;
            }
        }

        $io->title('EMBER - First Administrator Setup');

        // Ask for username.
        $username = trim($io->ask('Enter administrator username'));

        if ($username === '' || strlen($username) > 50) {
            $io->error('Username must contain 1 to 50 characters.');
            return Command::FAILURE;
        }

        // Check whether username already exists.
        $existingUsername = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if ($existingUsername) {
            $io->error('That username is already taken.');
            return Command::FAILURE;
        }

        // Ask for email.
        $email = trim($io->ask('Enter administrator email address'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Please enter a valid email address.');
            return Command::FAILURE;
        }

        // Check whether email already exists.
        $existingEmail = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingEmail) {
            $io->error('That email address is already registered.');
            return Command::FAILURE;
        }

        // Ask for password without displaying it.
        $password = $io->askHidden('Enter administrator password');

        if (!is_string($password) || strlen($password) < 12) {
            $io->error('Password must contain at least 12 characters.');
            return Command::FAILURE;
        }

        $confirmPassword = $io->askHidden('Confirm administrator password');

        if ($password !== $confirmPassword) {
            $io->error('Passwords do not match.');
            return Command::FAILURE;
        }

        // Create administrator account.
        $admin = new User();

        $admin
            ->setUsername($username)
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN']);

        // Hash password before saving.
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $password
        );

        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success([
            'Administrator account created successfully!',
            'Username: ' . $username,
            'Email: ' . $email,
            'Role: ROLE_ADMIN',
            'Password securely hashed.'
        ]);

        return Command::SUCCESS;
    }
}
