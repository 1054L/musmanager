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

echo "Seeding Torneo Esperanza...\n";

$admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$admin) {
    echo "Admin user not found. Please run seed_test_data.php first or create an admin user.\n";
    exit(1);
}

$tournament = new Tournament();
$tournament->setName('Torneo Esperanza');
$tournament->setType('groups');
$tournament->setStatus('active');
$tournament->setCreatedBy($admin);
$tournament->setStartDate(new \DateTimeImmutable('2026-02-09'));
$tournament->setEndDate(new \DateTimeImmutable('2026-03-27'));
$tournament->setRuleKings(4);
$tournament->setRulePoints(40);
$tournament->setRuleGames(4); // "a 4 chicos"
$tournament->setStatusDescription("LXI CAMPEONATO MUS CD ESPERANZA-XXIII MEMORIAL PACO MUTUBERRIA");
$entityManager->persist($tournament);

$groupsData = [
    'GRUPO A' => [
        'Mikel Bea - Jani',
        'Santi - Ubarrechena',
        'Peru Idigoras - Pablo Fernández',
        'Alain Hernández- Jesus',
        'Jotxe - Felix'
    ],
    'GRUPO B' => [
        'Iñigo Torres - Aramburu',
        'Luis Diaz - Archi',
        'Xeber Lazkano - Salva',
        'Jesus Ibañez - Iker Goenaga',
        'Alberto Menta - Alberto Menta'
    ],
    'GRUPO C' => [
        'Iosu Lazkano-Koldo Loinaz',
        'Ramon Arrue - J.Cortes',
        'Mutu - Fernan',
        'Juanito - Piru Garcia',
        'Gabi Simal - Larrañaga'
    ],
    'GRUPO D' => [
        'Eneko Hernández - Luis M. Garcia',
        'Javi Lacruz - Javi Calderon',
        'David - Tatxan',
        'Joseba Landa - Iñaki Aguirre',
        'Mendi - Cordoba'
    ]
];

$teamMap = [];
$statsMap = [];

foreach ($groupsData as $groupName => $teamNames) {
    foreach ($teamNames as $name) {
        $team = new Team();
        $team->setName($name);
        $team->setCreatedBy($admin);
        $entityManager->persist($team);
        
        $teamMap[$name] = $team;

        $tt = new TournamentTeam();
        $tt->setTournament($tournament);
        $tt->setTeam($team);
        $tt->setGroupName($groupName);
        $tt->setMatchesPlayed(0);
        $tt->setGamesWon(0);
        $tt->setGamesLost(0);
        $tt->setPoints(0);
        
        $entityManager->persist($tt);
        $statsMap[$name] = $tt;
    }
}

$matchesData = [
    // Jornada 1
    ['Jornada 1', 'Mikel Bea - Jani', 'Peru Idigoras - Pablo Fernández', 4, 1],
    ['Jornada 1', 'Santi - Ubarrechena', 'Alain Hernández- Jesus', 4, 1],
    ['Jornada 1', 'Iñigo Torres - Aramburu', 'Jesus Ibañez - Iker Goenaga', 2, 4],
    ['Jornada 1', 'Luis Diaz - Archi', 'Xeber Lazkano - Salva', 4, 3],
    ['Jornada 1', 'Iosu Lazkano-Koldo Loinaz', 'Ramon Arrue - J.Cortes', 3, 4],
    ['Jornada 1', 'Mutu - Fernan', 'Juanito - Piru Garcia', 2, 4],
    ['Jornada 1', 'Eneko Hernández - Luis M. Garcia', 'David - Tatxan', 4, 3],
    ['Jornada 1', 'Javi Lacruz - Javi Calderon', 'Joseba Landa - Iñaki Aguirre', 2, 4],

    // Jornada 2
    ['Jornada 2', 'Mikel Bea - Jani', 'Santi - Ubarrechena', 1, 4],
    ['Jornada 2', 'Peru Idigoras - Pablo Fernández', 'Jotxe - Felix', 4, 1],
    ['Jornada 2', 'Iñigo Torres - Aramburu', 'Luis Diaz - Archi', 4, 3],
    ['Jornada 2', 'Jesus Ibañez - Iker Goenaga', 'Alberto Menta - Alberto Menta', 4, 2],
    ['Jornada 2', 'Iosu Lazkano-Koldo Loinaz', 'Mutu - Fernan', 0, 4],
    ['Jornada 2', 'Ramon Arrue - J.Cortes', 'Gabi Simal - Larrañaga', 4, 2],
    ['Jornada 2', 'Eneko Hernández - Luis M. Garcia', 'Javi Lacruz - Javi Calderon', 3, 4],
    ['Jornada 2', 'David - Tatxan', 'Mendi - Cordoba', 2, 4],

    // Jornada 3
    ['Jornada 3', 'Mikel Bea - Jani', 'Alain Hernández- Jesus', 4, 2],
    ['Jornada 3', 'Santi - Ubarrechena', 'Jotxe - Felix', 4, 1],
    ['Jornada 3', 'Iñigo Torres - Aramburu', 'Xeber Lazkano - Salva', 4, 1],
    ['Jornada 3', 'Luis Diaz - Archi', 'Alberto Menta - Alberto Menta', 0, 4],
    ['Jornada 3', 'Iosu Lazkano-Koldo Loinaz', 'Juanito - Piru Garcia', 4, 2],
    ['Jornada 3', 'Mutu - Fernan', 'Gabi Simal - Larrañaga', 4, 0],
    ['Jornada 3', 'Eneko Hernández - Luis M. Garcia', 'Joseba Landa - Iñaki Aguirre', 4, 2],
    ['Jornada 3', 'Javi Lacruz - Javi Calderon', 'Mendi - Cordoba', 4, 2],

    // Jornada 4
    ['Jornada 4', 'Mikel Bea - Jani', 'Jotxe - Felix', 2, 4],
    ['Jornada 4', 'Peru Idigoras - Pablo Fernández', 'Alain Hernández- Jesus', 4, 3],
    ['Jornada 4', 'Iñigo Torres - Aramburu', 'Alberto Menta - Alberto Menta', 4, 1],
    ['Jornada 4', 'Jesus Ibañez - Iker Goenaga', 'Xeber Lazkano - Salva', 3, 4],
    ['Jornada 4', 'Iosu Lazkano-Koldo Loinaz', 'Gabi Simal - Larrañaga', 3, 4],
    ['Jornada 4', 'Ramon Arrue - J.Cortes', 'Juanito - Piru Garcia', 4, 1],
    ['Jornada 4', 'Eneko Hernández - Luis M. Garcia', 'Mendi - Cordoba', 4, 2],
    ['Jornada 4', 'David - Tatxan', 'Joseba Landa - Iñaki Aguirre', 2, 4],

    // Jornada 5
    ['Jornada 5', 'Peru Idigoras - Pablo Fernández', 'Santi - Ubarrechena', 2, 4],
    ['Jornada 5', 'Alain Hernández- Jesus', 'Jotxe - Felix', 4, 1],
    ['Jornada 5', 'Jesus Ibañez - Iker Goenaga', 'Luis Diaz - Archi', 4, 1], // Added missing space before Iker initially
    ['Jornada 5', 'Xeber Lazkano - Salva', 'Alberto Menta - Alberto Menta', 4, 2],
    ['Jornada 5', 'Ramon Arrue - J.Cortes', 'Mutu - Fernan', 4, 0],
    ['Jornada 5', 'Juanito - Piru Garcia', 'Gabi Simal - Larrañaga', 4, 3],
    ['Jornada 5', 'David - Tatxan', 'Javi Lacruz - Javi Calderon', 2, 4],
    ['Jornada 5', 'Joseba Landa - Iñaki Aguirre', 'Mendi - Cordoba', 0, 4],

    // Eliminatorias
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

    // Update standings (group phase matches usually indicate stats update, but here we can just update it for all to have a simple stat)
    if (strpos($stage, 'Jornada') !== false) {
        $tt1 = $statsMap[$mData[1]];
        $tt2 = $statsMap[$mData[2]];

        $tt1->setMatchesPlayed($tt1->getMatchesPlayed() + 1);
        $tt2->setMatchesPlayed($tt2->getMatchesPlayed() + 1);

        if ($score1 > $score2) {
            $tt1->setGamesWon($tt1->getGamesWon() + 1);
            $tt2->setGamesLost($tt2->getGamesLost() + 1);
            $tt1->setPoints($tt1->getPoints() + 1);
        } else if ($score2 > $score1) {
            $tt2->setGamesWon($tt2->getGamesWon() + 1);
            $tt1->setGamesLost($tt1->getGamesLost() + 1);
            $tt2->setPoints($tt2->getPoints() + 1);
        }
    }
}

$entityManager->flush();

echo "Successfully seeded Torneo Esperanza.\n";
