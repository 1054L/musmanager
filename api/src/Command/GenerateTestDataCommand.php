<?php

namespace App\Command;

use App\Entity\MusMatch;
use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generates test tournaments, teams and players'
)]
class GenerateTestDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Get or create admin user
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        if (!$admin) {
            $admin = $this->entityManager->getRepository(User::class)->findOneBy([]);
        }

        if (!$admin) {
            $io->error('No users found. Please run app:create-test-users first.');
            return Command::FAILURE;
        }

        // 2. Create players and teams
        $teams = [];
        $playerRepo = $this->entityManager->getRepository(Player::class);
        $teamRepo = $this->entityManager->getRepository(Team::class);
        $tournamentRepo = $this->entityManager->getRepository(Tournament::class);

        for ($i = 1; $i <= 8; $i++) {
            $teamName = "Equipo $i";
            $team = $teamRepo->findOneBy(['name' => $teamName]);
            
            if (!$team) {
                $team = new Team();
                $team->setName($teamName);
                $team->setCreatedBy($admin);
                $this->entityManager->persist($team);
            }

            $p1Email = "player$i-1@test.com";
            $p1 = $playerRepo->findOneBy(['email' => $p1Email]);
            if (!$p1) {
                $p1 = new Player();
                $p1->setName("Jugador $i-1");
                $p1->setEmail($p1Email);
                $p1->setCreatedBy($admin);
                $this->entityManager->persist($p1);
            }

            $p2Email = "player$i-2@test.com";
            $p2 = $playerRepo->findOneBy(['email' => $p2Email]);
            if (!$p2) {
                $p2 = new Player();
                $p2->setName("Jugador $i-2");
                $p2->setEmail($p2Email);
                $p2->setCreatedBy($admin);
                $this->entityManager->persist($p2);
            }

            if (!$team->getPlayers()->contains($p1)) {
                $team->addPlayer($p1);
            }
            if (!$team->getPlayers()->contains($p2)) {
                $team->addPlayer($p2);
            }

            $teams[] = $team;
        }

        // 3. Create Tournaments
        $t1Name = 'Gran Torneo de Grupos';
        $t1 = $tournamentRepo->findOneBy(['name' => $t1Name]);
        if (!$t1) {
            $t1 = new Tournament();
            $t1->setName($t1Name);
            $t1->setType('groups');
            $t1->setStatus('active');
            $t1->setCreatedBy($admin);
            $t1->setTablesCount(4);
            $t1->setLocation('Sede Central');
            $this->entityManager->persist($t1);
        }

        $t2Name = 'Eliminatoria Rápida';
        $t2 = $tournamentRepo->findOneBy(['name' => $t2Name]);
        if (!$t2) {
            $t2 = new Tournament();
            $t2->setName($t2Name);
            $t2->setType('eliminatory');
            $t2->setStatus('draft');
            $t2->setCreatedBy($admin);
            $this->entityManager->persist($t2);
        }

        // 4. Enroll teams in tournaments
        foreach ($teams as $team) {
            $existingTT1 = $this->entityManager->getRepository(TournamentTeam::class)->findOneBy([
                'tournament' => $t1,
                'team' => $team
            ]);
            if (!$existingTT1) {
                $tt1 = new TournamentTeam();
                $tt1->setTournament($t1);
                $tt1->setTeam($team);
                $this->entityManager->persist($tt1);
            }

            $existingTT2 = $this->entityManager->getRepository(TournamentTeam::class)->findOneBy([
                'tournament' => $t2,
                'team' => $team
            ]);
            if (!$existingTT2) {
                $tt2 = new TournamentTeam();
                $tt2->setTournament($t2);
                $tt2->setTeam($team);
                $this->entityManager->persist($tt2);
            }
        }

        $this->entityManager->flush();

        $io->success('Test data generated successfully!');
        return Command::SUCCESS;
    }
}
