<?php

use App\Entity\Tournament;
use App\Entity\MusMatch;
use App\Entity\MusMatchGame;
use App\Entity\TournamentTeam;
use Doctrine\ORM\EntityManagerInterface;

require __DIR__ . '/vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

$tournamentUuid = '46cbdefa-b5c6-446d-826c-77e7b8275858';
$tournament = $entityManager->getRepository(Tournament::class)->findOneBy(['uuidAccessToken' => $tournamentUuid]);

if (!$tournament) {
    die("Tournament not found\n");
}

echo "Filling results for tournament: " . $tournament->getName() . "\n";

$toWin = $tournament->getRuleGames() ?: 3;
$limit = $tournament->getRulePoints() ?: 40;

// Get all matches and determine max round
$matches = $entityManager->getRepository(MusMatch::class)->findBy(['tournament' => $tournament]);
$maxRound = 0;
foreach ($matches as $m) {
    if ($m->getBracketRound() > $maxRound) {
        $maxRound = $m->getBracketRound();
    }
}

echo "Max round detected: $maxRound\n";

function advanceWinner($match, $entityManager) {
    $tournament = $match->getTournament();
    if ($tournament->getType() !== 'eliminatory' || !$match->getWinner() || $match->getBracketRound() <= 1) {
        return;
    }

    $nextRound = $match->getBracketRound() - 1;
    $nextPos = (int)floor($match->getBracketPosition() / 2);
    $isTeam2 = $match->getBracketPosition() % 2 === 1;

    $nextMatch = $entityManager->getRepository(MusMatch::class)->findOneBy([
        'tournament' => $tournament,
        'bracketRound' => $nextRound,
        'bracketPosition' => $nextPos
    ]);

    if ($nextMatch) {
        if ($isTeam2) {
            $nextMatch->setTeam2($match->getWinner());
        } else {
            $nextMatch->setTeam1($match->getWinner());
        }
    }

    // Logic for 3rd and 4th place (losers of semifinals)
    if ($match->getBracketRound() === 2 && $tournament->isHasThirdPlace()) {
        $thirdPlaceMatch = $entityManager->getRepository(MusMatch::class)->findOneBy([
            'tournament' => $tournament,
            'bracketRound' => 1,
            'bracketPosition' => 1
        ]);

        if ($thirdPlaceMatch) {
            $loser = ($match->getWinner() === $match->getTeam1()) ? $match->getTeam2() : $match->getTeam1();
            if ($loser) {
                if ($match->getBracketPosition() === 0) {
                    $thirdPlaceMatch->setTeam1($loser);
                } else {
                    $thirdPlaceMatch->setTeam2($loser);
                }
            }
        }
    }
}

// Process from maxRound down to 3 (Quarters)
for ($r = $maxRound; $r >= 3; $r--) {
    echo "Processing Round $r...\n";
    $roundMatches = $entityManager->getRepository(MusMatch::class)->findBy([
        'tournament' => $tournament,
        'bracketRound' => $r
    ]);

    foreach ($roundMatches as $match) {
        if ($match->getWinner()) continue; // Already has result
        
        // Ensure we have teams (though in high rounds they should be there)
        if (!$match->getTeam1() || !$match->getTeam2()) {
            echo "  Skipping match {$match->getId()} (missing teams)\n";
            continue;
        }

        // Random winner
        $winner = rand(1, 2);
        if ($winner === 1) {
            $match->setScoreTeam1($toWin);
            $match->setScoreTeam2(rand(0, $toWin - 1));
            $match->setWinner($match->getTeam1());
        } else {
            $match->setScoreTeam1(rand(0, $toWin - 1));
            $match->setScoreTeam2($toWin);
            $match->setWinner($match->getTeam2());
        }

        // Create games
        for ($i = 0; $i < ($match->getScoreTeam1() + $match->getScoreTeam2()); $i++) {
            $game = new MusMatchGame();
            $game->setMusMatch($match);
            $game->setGameNumber($i + 1);
            
            // Just dummy points
            if ($i < $match->getScoreTeam1()) {
                $game->setPointsTeam1($limit);
                $game->setPointsTeam2(rand(0, $limit - 5));
            } else {
                $game->setPointsTeam1(rand(0, $limit - 5));
                $game->setPointsTeam2($limit);
            }
            $entityManager->persist($game);
        }

        advanceWinner($match, $entityManager);
        echo "  Match {$match->getId()} finished: {$match->getTeam1()->getName()} vs {$match->getTeam2()->getName()} -> Winner: " . $match->getWinner()->getName() . "\n";
    }
    $entityManager->flush();
}

echo "Done! Semifinals (Round 2) should now be populated.\n";
