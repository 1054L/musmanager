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

echo "Seeding Torneo Esperanza II (Eliminatory)...\n";

$admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$admin) {
    echo "Admin user not found.\n";
    exit(1);
}

// 1. Create Tournament
$tournament = new Tournament();
$tournament->setName('Torneo Esperanza II');
$tournament->setType('eliminatory');
$tournament->setStatus('active');
$tournament->setCreatedBy($admin);
$tournament->setStartDate(new \DateTimeImmutable('2026-03-16'));
$tournament->setEndDate(new \DateTimeImmutable('2026-03-31'));
$tournament->setRuleKings(4);
$tournament->setRulePoints(40);
$tournament->setRuleGames(4);
$tournament->setStatusDescription("Eliminatoria directa basada en el PDF mus 26");
$entityManager->persist($tournament);

// 2. The 8 Teams in the Eliminatory Phase
$teamNames = [
    'Santi - Ubarrechena',
    'Iñigo Torres - Aramburu',
    'Ramon Arrue - J.Cortes',
    'Javi Lacruz - Javi Calderon',
    'Eneko Hernández - Luis M. Garcia',
    'Mikel Bea - Jani',
    'Jesus Ibañez - Iker Goenaga',
    'Juanito - Piru Garcia'
];

$teamMap = [];
foreach ($teamNames as $name) {
    // Try to find if the team exists from Torneo Esperanza
    $team = $entityManager->getRepository(Team::class)->findOneBy(['name' => $name]);
    
    if (!$team) {
        $team = new Team();
        $team->setName($name);
        $team->setCreatedBy($admin);
        $entityManager->persist($team);
    }
    
    $teamMap[$name] = $team;

    // Enroll them in the new tournament
    $tt = new TournamentTeam();
    $tt->setTournament($tournament);
    $tt->setTeam($team);
    // No group name since it's an eliminatory tournament
    $entityManager->persist($tt);
}

// 3. The Knockout Matches
$matchesData = [
    ['Cuartos de Final', 'Santi - Ubarrechena', 'Iñigo Torres - Aramburu', 4, 1],
    ['Cuartos de Final', 'Ramon Arrue - J.Cortes', 'Javi Lacruz - Javi Calderon', 4, 3],
    ['Cuartos de Final', 'Eneko Hernández - Luis M. Garcia', 'Mikel Bea - Jani', 1, 4],
    ['Cuartos de Final', 'Jesus Ibañez - Iker Goenaga', 'Juanito - Piru Garcia', 4, 2],

    ['Semifinales', 'Santi - Ubarrechena', 'Ramon Arrue - J.Cortes', 3, 4],
    ['Semifinales', 'Mikel Bea - Jani', 'Jesus Ibañez - Iker Goenaga', 4, 3],

    ['3er y 4º Puesto', 'Santi - Ubarrechena', 'Jesus Ibañez - Iker Goenaga', 0, 0],

    ['Final', 'Ramon Arrue - J.Cortes', 'Mikel Bea - Jani', 0, 0],
];

foreach ($matchesData as $mData) {
    if (!isset($teamMap[$mData[1]])) throw new \Exception("Team not found: " . $mData[1]);
    if (!isset($teamMap[$mData[2]])) throw new \Exception("Team not found: " . $mData[2]);
    $team1 = $teamMap[$mData[1]];
    $team2 = $teamMap[$mData[2]];
    $stage = $mData[0];
    $score1 = $mData[3];
    $score2 = $mData[4];

    $match = new MusMatch();
    $match->setTournament($tournament);
    $match->setTeam1($team1);
    $match->setTeam2($team2);
    $match->setStage($stage);
    $match->setScoreTeam1($score1);
    $match->setScoreTeam2($score2);

    if ($score1 > 0 || $score2 > 0) {
        $match->setWinner($score1 > $score2 ? $team1 : $team2);
    }
    $entityManager->persist($match);
}

$entityManager->flush();

echo "Successfully seeded Torneo Esperanza II.\n";
