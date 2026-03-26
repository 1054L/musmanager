<?php

namespace App\Command;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\MusMatch;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-rbac',
    description: 'Seed database with RBAC test data',
)]
class SeedRbacCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Clean up
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM mus_match');
        $connection->executeStatement('DELETE FROM team_player');
        $connection->executeStatement('DELETE FROM team');
        $connection->executeStatement('DELETE FROM player');
        $connection->executeStatement('DELETE FROM tournament_managers');
        $connection->executeStatement('DELETE FROM tournament');
        $connection->executeStatement('DELETE FROM user');

        // Create Users
        $super = new User();
        $super->setEmail('super@example.com');
        $super->setRoles(['ROLE_SUPER_ADMIN']);
        $super->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $this->entityManager->persist($super);

        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $this->entityManager->persist($admin);

        $userLogin = new User();
        $userLogin->setEmail('user@example.com');
        $userLogin->setRoles(['ROLE_USER']);
        $userLogin->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $this->entityManager->persist($userLogin);

        // Create Player for regular user
        $pUser = new Player();
        $pUser->setName('Jugador Registrado');
        $pUser->setEmail('user@example.com');
        $pUser->setLinkedUser($userLogin);
        $this->entityManager->persist($pUser);

        // Create Tournament owned by Admin
        $tourneyAdmin = new Tournament();
        $tourneyAdmin->setName('Torneo de Admin');
        $tourneyAdmin->setStatus('active');
        $tourneyAdmin->setCreatedBy($admin);
        $tourneyAdmin->setUuidAccessToken('550e8400-e29b-41d4-a716-446655440000');
        $this->entityManager->persist($tourneyAdmin);

        // Create Tournament owned by Super (but Admin has NO access)
        $tourneySuper = new Tournament();
        $tourneySuper->setName('Torneo de Super');
        $tourneySuper->setStatus('active');
        $tourneySuper->setCreatedBy($super);
        $tourneySuper->setUuidAccessToken('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $this->entityManager->persist($tourneySuper);

        // Create Match in Admin's tournament where User participates
        $team1 = new Team();
        $team1->setName('Team User');
        $team1->addPlayer($pUser);
        $this->entityManager->persist($team1);

        $team2 = new Team();
        $team2->setName('Team Bot');
        $pBot = new Player();
        $pBot->setName('Bot');
        $pBot->setEmail('bot@example.com');
        $this->entityManager->persist($pBot);
        $team2->addPlayer($pBot);
        $this->entityManager->persist($team2);

        $match = new MusMatch();
        $match->setTournament($tourneyAdmin);
        $match->setTeam1($team1);
        $match->setTeam2($team2);
        $match->setScoreTeam1(40);
        $match->setScoreTeam2(0);
        $match->setStage('Ronda 1');
        $this->entityManager->persist($match);

        $this->entityManager->flush();

        $io->success('Seeded successfully for RBAC testing!');
        $io->info('Superadmin: super@example.com / password');
        $io->info('Admin: admin@example.com / password');
        $io->info('User: user@example.com / password');

        return Command::SUCCESS;
    }
}
