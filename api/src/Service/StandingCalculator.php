<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\TournamentTeam;

class StandingCalculator
{
    public function calculate(Tournament $tournament): array
    {
        $tournamentTeams = $tournament->getTournamentTeams()->toArray();
        return $this->calculateForTeams($tournamentTeams);
    }

    public function calculateByGroups(Tournament $tournament): array
    {
        $groups = [];
        foreach ($tournament->getTournamentTeams() as $tt) {
            $groupName = $tt->getMesa() ?: 'Grupo A';
            $groups[$groupName][] = $tt;
        }

        ksort($groups, SORT_NATURAL);

        $groupedStandings = [];
        foreach ($groups as $groupName => $teams) {
            $groupedStandings[$groupName] = $this->calculateForTeams($teams, true);
        }

        return $groupedStandings;
    }

    /**
     * @param TournamentTeam[] $tournamentTeams
     */
    private function calculateForTeams(array $tournamentTeams, bool $onlyGroupStage = false): array
    {
        $standings = [];

        foreach ($tournamentTeams as $tt) {
            $stats = [
                'id' => $tt->getId(),
                'teamName' => $tt->getTeam()->getName(),
                'groupName' => $tt->getMesa(),
                'matchesPlayed' => 0,
                'matchesWon' => 0,
                'matchesLost' => 0,
                'vacasWon' => 0,
                'vacasLost' => 0,
                'vacasDiff' => 0,
                'tantosFavor' => 0,
                'tantosContra' => 0,
                'tantosDiff' => 0
            ];

            // Calculate from all matches (both as team1 and team2)
            $allMatches = array_merge(
                $tt->getMatchesAsTeam1()->toArray(),
                $tt->getMatchesAsTeam2()->toArray()
            );

            foreach ($allMatches as $match) {
                if ($onlyGroupStage && $match->getBracketRound() !== null) {
                    continue;
                }

                // Only count finished matches that have a winner
                if ($match->getWinner() === null) {
                    continue;
                }

                $isTeam1 = $match->getTeam1() === $tt;
                $stats['matchesPlayed']++;

                if ($match->getWinner() === $tt) {
                    $stats['matchesWon']++;
                } else {
                    $stats['matchesLost']++;
                }

                if ($isTeam1) {
                    $stats['vacasWon'] += $match->getScoreTeam1();
                    $stats['vacasLost'] += $match->getScoreTeam2();
                } else {
                    $stats['vacasWon'] += $match->getScoreTeam2();
                    $stats['vacasLost'] += $match->getScoreTeam1();
                }

                // Sum points favor and contra from MusMatchGame
                foreach ($match->getGames() as $game) {
                    if ($isTeam1) {
                        $stats['tantosFavor'] += $game->getPointsTeam1();
                        $stats['tantosContra'] += $game->getPointsTeam2();
                    } else {
                        $stats['tantosFavor'] += $game->getPointsTeam2();
                        $stats['tantosContra'] += $game->getPointsTeam1();
                    }
                }
            }

            $stats['vacasDiff'] = $stats['vacasWon'] - $stats['vacasLost'];
            $stats['tantosDiff'] = $stats['tantosFavor'] - $stats['tantosContra'];
            $standings[] = $stats;
        }

        // Sort according to EXACT Mus criteria:
        // 1. matchesWon (1 point per win)
        // 2. vacasDiff (best vaca difference)
        // 3. vacasWon (most vacas won)
        // 4. tantosFavor (most tantos scored)
        // 5. tantosDiff (best tanto difference)
        // 6. tantosContra (fewest tantos conceded - ascending)
        usort($standings, function ($a, $b) {
            if ($b['matchesWon'] !== $a['matchesWon']) return $b['matchesWon'] <=> $a['matchesWon'];
            if ($b['vacasDiff'] !== $a['vacasDiff']) return $b['vacasDiff'] <=> $a['vacasDiff'];
            if ($b['vacasWon'] !== $a['vacasWon']) return $b['vacasWon'] <=> $a['vacasWon'];
            if ($b['tantosFavor'] !== $a['tantosFavor']) return $b['tantosFavor'] <=> $a['tantosFavor'];
            if ($b['tantosDiff'] !== $a['tantosDiff']) return $b['tantosDiff'] <=> $a['tantosDiff'];
            return $a['tantosContra'] <=> $b['tantosContra']; // ASCENDING
        });

        return $standings;
    }
}
