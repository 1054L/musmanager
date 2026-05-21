<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\MusMatch;
use Doctrine\ORM\EntityManagerInterface;

class LeagueGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function generate(Tournament $tournament): void
    {
        $teams = $tournament->getTournamentTeams()->toArray();
        $count = count($teams);

        if ($count < 2) {
            return;
        }

        // If odd, add a null team for 'Byes'
        $isOdd = $count % 2 !== 0;
        if ($isOdd) {
            $teams[] = null;
            $count++;
        }

        $rounds = $count - 1;
        $matchesPerRound = $count / 2;

        // Deadline calculation setup
        $startDate = $tournament->getStartDate() ?? new \DateTimeImmutable();
        $matchDays = $tournament->getLeagueMatchDays(); // [1, 3, 5] etc.
        $currentDeadline = $startDate;

        for ($r = 0; $r < $rounds; $r++) {
            $matchDay = $r + 1;
            
            // Calculate deadline for this round
            if (!empty($matchDays)) {
                // Sort match days just in case
                sort($matchDays);
                
                // If it's the first round, find the first occurrence of any matchDay on or after startDate
                // If subsequent, find next occurrence of the next day in rotation
                if ($r === 0) {
                    $found = false;
                    for ($i = 0; $i < 7; $i++) {
                        $testDate = $currentDeadline->modify("+$i days");
                        $dayNum = (int)$testDate->format('N'); // 1 (Mon) to 7 (Sun)
                        if (in_array($dayNum, $matchDays)) {
                            $currentDeadline = $testDate;
                            $found = true;
                            break;
                        }
                    }
                } else {
                    // Find the next day in matchDays after the current one
                    $currentDayNum = (int)$currentDeadline->format('N');
                    $nextDay = null;
                    foreach ($matchDays as $md) {
                        if ($md > $currentDayNum) {
                            $nextDay = $md;
                            break;
                        }
                    }

                    if ($nextDay === null) {
                        // Go to the first day of the next week
                        $nextDay = $matchDays[0];
                        $daysToAdd = (7 - $currentDayNum) + $nextDay;
                    } else {
                        $daysToAdd = $nextDay - $currentDayNum;
                    }
                    $currentDeadline = $currentDeadline->modify("+$daysToAdd days");
                }
            } else {
                // Default: weekly
                if ($r > 0) {
                    $currentDeadline = $currentDeadline->modify('+7 days');
                }
            }

            for ($m = 0; $m < $matchesPerRound; $m++) {
                $homeIdx = ($r + $m) % ($count - 1);
                $awayIdx = ($count - 1 - $m + $r) % ($count - 1);

                if ($m === 0) {
                    $awayIdx = $count - 1;
                }

                $team1 = $teams[$homeIdx];
                $team2 = $teams[$awayIdx];

                // Skip if it's a Bye
                if ($team1 === null || $team2 === null) {
                    continue;
                }

                $match = new MusMatch();
                $match->setTournament($tournament);
                $match->setTeam1($team1);
                $match->setTeam2($team2);
                $match->setMatchDay($matchDay);
                $match->setStage("Jornada $matchDay");
                $match->setDeadlineDate($currentDeadline);
                
                // Assign tables (reusing tables 1, 2, 3...)
                $match->setMesa("Mesa " . ($m + 1));

                $this->entityManager->persist($match);
            }
        }
    }
}
