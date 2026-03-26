<?php

use App\Kernel;
use App\Entity\Tournament;
use App\Entity\Team;
use App\Entity\TournamentTeam;
use App\Entity\MusMatch;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Uid\Uuid;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

echo "Seeding Gran Liga de Invierno...\n";

$admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$admin) {
    echo "Admin user not found. Please run seed_test_data.php first.\n";
    exit(1);
}

// Create Tournament
$tournament = new Tournament();
$tournament->setName('Gran Liga de Invierno 2026');
$tournament->setType('league');
$tournament->setStatus('active');
$tournament->setCreatedBy($admin);
$tournament->setStartDate(new \DateTimeImmutable('2026-03-10'));
$tournament->setRuleKings(8);
$tournament->setRulePoints(40);
$tournament->setRuleGames(3);
$tournament->setStatusDescription("La liga más grande jamás vista. 12 parejas compitiendo por la gloria.");
$tournamentUuid = Uuid::v4()->toString();
$tournament->setUuidAccessToken($tournamentUuid);

$entityManager->persist($tournament);

// Create 12 Teams
$teamNames = [
    'Los Ases del Mus', 'Pareja Real', 'Los Cuatro Reyes', 'Mus y Amigos',
    'Estrategas del Norte', 'Invencibles de la Costa', 'Guardianes del Tapete',
    'Póker de Reyes', 'Los Faroleros', 'Maestros del Envido',
    'Siete de Copas', 'Sota de Bastos'
];

$teams = [];
foreach ($teamNames as $name) {
    $team = new Team();
    $team->setName($name);
    $team->setCreatedBy($admin);
    $entityManager->persist($team);
    
    $tt = new TournamentTeam();
    $tt->setTournament($tournament);
    $tt->setTeam($team);
    $tt->setMatchesPlayed(0);
    $tt->setGamesWon(0);
    $tt->setGamesLost(0);
    $tt->setPoints(0);
    $entityManager->persist($tt);
    
    $teams[] = [
        'entity' => $team,
        'tt' => $tt
    ];
}

// Generate some matches for 8 Jornadas
for ($j = 1; $j <= 8; $j++) {
    $stage = "Jornada $j";
    // Each jornada has 6 matches for 12 teams
    for ($m = 0; $m < 6; $m++) {
        $t1Idx = ($m + $j) % 12;
        $t2Idx = (11 - $m + $j) % 12;
        if ($t1Idx === $t2Idx) continue;
        
        $team1 = $teams[$t1Idx]['entity'];
        $team2 = $teams[$t2Idx]['entity'];
        $tt1 = $teams[$t1Idx]['tt'];
        $tt2 = $teams[$t2Idx]['tt'];
        
        $match = new MusMatch();
        $match->setTournament($tournament);
        $match->setStage($stage);
        $match->setTeam1($team1);
        $match->setTeam2($team2);
        
        // Only fill scores for the first 6 jornadas
        if ($j <= 6) {
            $s1 = rand(0, 3);
            $s2 = rand(0, 3);
            if ($s1 === $s2) $s1++; // No draws in Mus best of 3
            
            $match->setScoreTeam1($s1);
            $match->setScoreTeam2($s2);
            $match->setWinner($s1 > $s2 ? $team1 : $team2);
            
            // Update stats
            $tt1->setMatchesPlayed($tt1->getMatchesPlayed() + 1);
            $tt2->setMatchesPlayed($tt2->getMatchesPlayed() + 1);
            if ($s1 > $s2) {
                $tt1->setGamesWon($tt1->getGamesWon() + 1);
                $tt2->setGamesLost($tt2->getGamesLost() + 1);
                $tt1->setPoints($tt1->getPoints() + 3);
            } else {
                $tt2->setGamesWon($tt2->getGamesWon() + 1);
                $tt1->setGamesLost($tt1->getGamesLost() + 1);
                $tt2->setPoints($tt2->getPoints() + 3);
            }
        }
        
        $entityManager->persist($match);
    }
}

$entityManager->flush();

echo "Successfully seeded Gran Liga de Invierno.\n";
echo "Tournament UUID: " . $tournamentUuid . "\n";
echo "URL path: /tournament/" . $tournamentUuid . "\n";
