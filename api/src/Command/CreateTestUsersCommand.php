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

use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates several test users for development',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force recreate users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $testUsers = [
            ['email' => 'admin@test.com', 'password' => 'admin123', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'user1@test.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'user2@test.com', 'password' => 'user123', 'roles' => ['ROLE_USER']],
            ['email' => 'manager@test.com', 'password' => 'manager123', 'roles' => ['ROLE_ADMIN']],
        ];

        foreach ($testUsers as $userData) {
            $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            if ($existing) {
                if ($force) {
                    $io->note(sprintf('Removing existing user %s', $userData['email']));
                    $this->entityManager->remove($existing);
                    $this->entityManager->flush();
                } else {
                    $io->note(sprintf('User %s already exists, skipping.', $userData['email']));
                    continue;
                }
            }

            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $userData['password'])
            );

            $this->entityManager->persist($user);
            $io->success(sprintf('User %s created!', $userData['email']));
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
