<?php

namespace App\Controller;

use App\Entity\MusMatch;
use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mercure\HubInterface;

class MatchController extends AbstractController
{
    #[Route('/api/admin/match/{id}/score', name: 'app_match_update_score', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateScore(int $id, Request $request, EntityManagerInterface $entityManager, HubInterface $hub): JsonResponse
    {
        $match = $entityManager->getRepository(MusMatch::class)->find($id);
        if (!$match) return new JsonResponse(['error' => 'Partida no encontrada'], 404);

        $data = $request->toArray();
        $s1 = (int)($data['scoreTeam1'] ?? 0);
        $s2 = (int)($data['scoreTeam2'] ?? 0);
        $winnerId = $data['winnerId'] ?? null;

        $match->setScoreTeam1($s1);
        $match->setScoreTeam2($s2);

        if ($winnerId) {
            $winner = $entityManager->getRepository(TournamentTeam::class)->find($winnerId);
            $match->setWinner($winner);
            
            if ($match->getBracketRound() !== null) {
                $this->advanceWinnerInBracket($match, $entityManager);
            }
        }

        $this->updateStandings($match->getTournament(), $entityManager);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/match/bulk-date', name: 'app_match_bulk_date', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateBulkDate(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        $matchIds = $data['matchIds'] ?? [];
        $dateVal = $data['deadlineDate'] ?? null;
        $date = $dateVal ? new \DateTimeImmutable($dateVal) : null;

        $repo = $entityManager->getRepository(MusMatch::class);
        foreach ($matchIds as $id) {
            $match = $repo->find($id);
            if ($match) {
                $match->setDeadlineDate($date);
            }
        }
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function updateStandings(Tournament $tournament, EntityManagerInterface $entityManager): void
    {
        foreach ($tournament->getTournamentTeams() as $tt) {
            $tt->setPoints(0);
            $tt->setGamesWon(0);
            $tt->setGamesLost(0);
            $tt->setMatchesPlayed(0);
        }

        foreach ($tournament->getMatches() as $match) {
            $team1 = $match->getTeam1();
            $team2 = $match->getTeam2();
            $s1 = $match->getScoreTeam1();
            $s2 = $match->getScoreTeam2();
            $winner = $match->getWinner();

            if ($team1 && ($s1 > 0 || $s2 > 0 || $winner)) {
                $tt1 = $team1;
                $tt1->setMatchesPlayed($tt1->getMatchesPlayed() + 1);
                $tt1->setGamesWon($tt1->getGamesWon() + $s1);
                $tt1->setGamesLost($tt1->getGamesLost() + $s2);
                if ($winner && $winner->getId() === $team1->getId()) {
                    $tt1->setPoints($tt1->getPoints() + 3);
                }
            }

            if ($team2 && ($s1 > 0 || $s2 > 0 || $winner)) {
                $tt2 = $team2;
                $tt2->setMatchesPlayed($tt2->getMatchesPlayed() + 1);
                $tt2->setGamesWon($tt2->getGamesWon() + $s2);
                $tt2->setGamesLost($tt2->getGamesLost() + $s1);
                if ($winner && $winner->getId() === $team2->getId()) {
                    $tt2->setPoints($tt2->getPoints() + 3);
                }
            }
        }
    }

    private array $affectedMatches = [];

    private function advanceWinnerInBracket(MusMatch $match, EntityManagerInterface $entityManager): void
    {
        if ($match->getBracketRound() <= 1) return;

        $tournament = $match->getTournament();
        $nextRound = $match->getBracketRound() - 1;
        $nextPos = floor($match->getBracketPosition() / 2);
        $isTeam2 = ($match->getBracketPosition() % 2 === 1);

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
            $this->affectedMatches[] = $nextMatch;
        }

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
                    $this->affectedMatches[] = $thirdPlaceMatch;
                }
            }
        }
    }
}
