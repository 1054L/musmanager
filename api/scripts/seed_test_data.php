<?php

use App\Kernel;
use App\Entity\Tournament;
use App\Entity\Team;
use App\Entity\TournamentTeam;
use App\Entity\MusMatch;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "Seeding test data...\n";

// 1. Get the admin user
$admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$admin) {
    echo "Admin user not found. Creating one...\n";
    $admin = new User();
    $admin->setEmail('admin@test.com');
    $admin->setPassword('password'); // Not hashed for simplicity in seed, but ideally should be
    $entityManager->persist($admin);
}

// 2. Teams
$teams = [];
$teamNames = ['Los Invencibles', 'Reyes del Mus', 'Pareja de Ases', 'Cuatro de Copas', 'Sota de Bastos', 'La Real', 'Mus Manager Team', 'Los Amigos'];
foreach ($teamNames as $name) {
    $team = new Team();
    $team->setName($name);
    $team->setCreatedBy($admin);
    $entityManager->persist($team);
    $teams[] = $team;
}

// 3. Tournaments
$tournamentData = [
    ['name' => 'Torneo Eliminatoria Pro', 'type' => 'eliminatory', 'status' => 'active'],
    ['name' => 'Liga de Verano Mus Manager', 'type' => 'league', 'status' => 'active'],
    ['name' => 'Copa de Grupos Canal v4', 'type' => 'groups', 'status' => 'active'],
    ['name' => 'Grand Slam de Mus (Borrador)', 'type' => 'eliminatory', 'status' => 'draft']
];

foreach ($tournamentData as $tData) {
    $tournament = new Tournament();
    $tournament->setName($tData['name']);
    $tournament->setType($tData['type']);
    $tournament->setStatus($tData['status']);
    $tournament->setCreatedBy($admin);
    $tournament->setStartDate(new \DateTimeImmutable());
    $tournament->setEndDate(new \DateTimeImmutable('+7 days'));
    $tournament->setStatusDescription("Test data for system verification. Format: {$tData['type']}");
    $entityManager->persist($tournament);

    if ($tData['status'] !== 'draft') {
        // Enroll all 8 teams
        foreach ($teams as $team) {
            $tt = new TournamentTeam();
            $tt->setTournament($tournament);
            $tt->setTeam($team);
            $tt->setPoints(rand(0, 10));
            $tt->setMatchesPlayed(rand(1, 5));
            $tt->setGamesWon(rand(0, 15));
            $tt->setGamesLost(rand(0, 15));
            $entityManager->persist($tt);
        }

        // Create some matches
        for ($i = 0; $i < 4; $i++) {
            $match = new MusMatch();
            $match->setTournament($tournament);
            $match->setTeam1($teams[$i * 2]);
            $match->setTeam2($teams[$i * 2 + 1]);
            $match->setStage($tData['type'] === 'eliminatory' ? 'Cuartos de Final' : 'Jornada 1');
            
            if (rand(0, 1)) {
                $match->setScoreTeam1(rand(0, 40));
                $match->setScoreTeam2(rand(0, 40));
                $match->setWinner($match->getScoreTeam1() > $match->getScoreTeam2() ? $match->getTeam1() : $match->getTeam2());
            } else {
                // In progress
                $match->setScoreTeam1(0);
                $match->setScoreTeam2(0);
            }
            $entityManager->persist($match);
        }
    }
}

$entityManager->flush();
echo "Successfully seeded test data.\n";
