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

echo "Seeding Torneo Esperanza III (Mega Bracket)...\n";

$admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$admin) {
    echo "Admin user not found.\n";
    exit(1);
}

// 1. Create Tournament
$tournament = new Tournament();
$tournament->setName('Torneo Esperanza III Gran Cuadro');
$tournament->setType('eliminatory');
$tournament->setStatus('active');
$tournament->setCreatedBy($admin);
$tournament->setStartDate(new \DateTimeImmutable());
$tournament->setEndDate(new \DateTimeImmutable('+1 month'));
$tournament->setRuleKings(4);
$tournament->setRulePoints(40);
$tournament->setRuleGames(4);
$tournament->setStatusDescription("Prueba de bracket gigante inventado (32 parejas)");
$entityManager->persist($tournament);

// 2. The 32 Teams
$teams = [];
for ($i = 1; $i <= 32; $i++) {
    $name = "Pareja Mega " . $i;
    $team = $entityManager->getRepository(Team::class)->findOneBy(['name' => $name]);
    if (!$team) {
        $team = new Team();
        $team->setName($name);
        $team->setCreatedBy($admin);
        $entityManager->persist($team);
    }
    $teams[] = $team;

    $tt = new TournamentTeam();
    $tt->setTournament($tournament);
    $tt->setTeam($team);
    $entityManager->persist($tt);
}

// 3. Play the matches
function playRound($stage, $teamsList, $tournament, $entityManager) {
    $winners = [];
    for ($i = 0; $i < count($teamsList); $i += 2) {
        $team1 = $teamsList[$i];
        $team2 = $teamsList[$i+1];
        
        $score1 = rand(0, 4);
        $score2 = ($score1 == 4) ? rand(0, 3) : 4; 
        if ($score1 === $score2) $score1 = 4; // safety
        
        $match = new MusMatch();
        $match->setTournament($tournament);
        $match->setTeam1($team1);
        $match->setTeam2($team2);
        $match->setStage($stage);
        $match->setScoreTeam1($score1);
        $match->setScoreTeam2($score2);
        
        $winner = $score1 > $score2 ? $team1 : $team2;
        $match->setWinner($winner);
        $entityManager->persist($match);
        
        $winners[] = $winner;
    }
    return $winners;
}

$dieciseisavosWinners = playRound('Dieciseisavos de Final', $teams, $tournament, $entityManager);
$octavosWinners = playRound('Octavos de Final', $dieciseisavosWinners, $tournament, $entityManager);
$cuartosWinners = playRound('Cuartos de Final', $octavosWinners, $tournament, $entityManager);
$semiWinners = playRound('Semifinales', $cuartosWinners, $tournament, $entityManager);

// Find the losers of Semifinals for 3rd place
$semiLosers = array_filter($cuartosWinners, function($t) use ($semiWinners) {
    return $t !== $semiWinners[0] && $t !== $semiWinners[1];
});
$semiLosers = array_values($semiLosers);

if (count($semiLosers) == 2) {
    $m3 = new MusMatch();
    $m3->setTournament($tournament);
    $m3->setTeam1($semiLosers[0]);
    $m3->setTeam2($semiLosers[1]);
    $m3->setStage('3er y 4º Puesto');
    $m3->setScoreTeam1(4);
    $m3->setScoreTeam2(2);
    $m3->setWinner($semiLosers[0]);
    $entityManager->persist($m3);
}

// Final
$final = new MusMatch();
$final->setTournament($tournament);
$final->setTeam1($semiWinners[0]);
$final->setTeam2($semiWinners[1]);
$final->setStage('Final');
$final->setScoreTeam1(0);
$final->setScoreTeam2(0); // Pending
$entityManager->persist($final);

$entityManager->flush();
echo "Successfully seeded Torneo Esperanza III.\n";
