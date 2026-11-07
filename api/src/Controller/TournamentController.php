<?php

namespace App\Controller;

/**
 * MANTENIMIENTO DOCKER:
 * Si hay errores de permisos en la caché o el 500 persiste tras cambios:
 * docker compose exec api php bin/console cache:clear
 * docker compose exec api chmod -R 777 var/cache var/log
 */

use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\Team;
use App\Entity\MusMatch;
use App\Entity\MusMatchGame;
use App\Entity\Province;
use App\Entity\Town;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use App\Repository\ProvinceRepository;
use App\Repository\TownRepository;
use App\Repository\TournamentTeamRepository;
use App\Repository\MusMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\LeagueGenerator;
use App\Service\StandingCalculator;

class TournamentController extends AbstractController
{
    #[Route('/api/public/tournaments', name: 'app_tournament_public_list', methods: ['GET'])]
    public function listPublic(TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournaments = $tournamentRepository->createQueryBuilder('t')
            ->where('t.private = :private')
            ->andWhere('t.status != :draft')
            ->setParameter('private', false)
            ->setParameter('draft', 'draft')
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();

        return new JsonResponse(array_map(function(Tournament $t) {
            return [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'uuid' => $t->getUuidAccessToken(),
                'status' => $t->getStatus(),
                'type' => $t->getType(),
                'startDate' => $t->getStartDate() ? $t->getStartDate()->format(\DateTimeInterface::ATOM) : null,
                'endDate' => $t->getEndDate() ? $t->getEndDate()->format(\DateTimeInterface::ATOM) : null,
                'ruleKings' => $t->getRuleKings(),
                'rulePoints' => $t->getRulePoints(),
                'ruleGames' => $t->getRuleGames(),
                'tablesCount' => $t->getTablesCount(),
                'location' => $t->getLocation(),
                'provinceId' => $t->getProvince() ? $t->getProvince()->getId() : null,
                'provinceName' => $t->getProvince() ? $t->getProvince()->getName() : null,
                'townId' => $t->getTown() ? $t->getTown()->getId() : null,
                'townName' => $t->getTown() ? $t->getTown()->getName() : null,
                'posterPath' => $t->getPosterPath(),
                'rulesPath' => $t->getRulesPath(),
                'teamsCount' => count($t->getTournamentTeams()),
                'hasMatches' => count($t->getMatches()) > 0,
                'private' => $t->isPrivate(),
                'hasThirdPlace' => $t->isHasThirdPlace(),
                'leagueMatchDays' => $t->getLeagueMatchDays(),
            ];
        }, $tournaments));
    }

    #[Route('/api/user/tournaments', name: 'app_tournament_list_managed_api', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listManaged(TournamentRepository $tournamentRepository): JsonResponse
    {
        $user = $this->getUser();
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        
        if ($isSuperAdmin) {
            $tournaments = $tournamentRepository->findBy([], ['id' => 'DESC']);
        } else {
            $tournaments = $tournamentRepository->findManagedByUser($user);
        }
        
        return new JsonResponse(array_map(function(Tournament $t) use ($isSuperAdmin) {
            $data = [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'uuid' => $t->getUuidAccessToken(),
                'status' => $t->getStatus(),
                'type' => $t->getType(),
                'startDate' => $t->getStartDate() ? $t->getStartDate()->format(\DateTimeInterface::ATOM) : null,
                'endDate' => $t->getEndDate() ? $t->getEndDate()->format(\DateTimeInterface::ATOM) : null,
                'ruleKings' => $t->getRuleKings(),
                'rulePoints' => $t->getRulePoints(),
                'ruleGames' => $t->getRuleGames(),
                'tablesCount' => $t->getTablesCount(),
                'location' => $t->getLocation(),
                'provinceId' => $t->getProvince() ? $t->getProvince()->getId() : null,
                'provinceName' => $t->getProvince() ? $t->getProvince()->getName() : null,
                'townId' => $t->getTown() ? $t->getTown()->getId() : null,
                'townName' => $t->getTown() ? $t->getTown()->getName() : null,
                'posterPath' => $t->getPosterPath(),
                'rulesPath' => $t->getRulesPath(),
                'teamsCount' => count($t->getTournamentTeams()),
                'hasMatches' => count($t->getMatches()) > 0,
                'private' => $t->isPrivate(),
                'hasThirdPlace' => $t->isHasThirdPlace(),
                'leagueMatchDays' => $t->getLeagueMatchDays(),
            ];

            if ($isSuperAdmin) {
                $owner = $t->getCreatedBy();
                $data['owner'] = $owner ? [
                    'id' => $owner->getId(),
                    'email' => $owner->getEmail(),
                    'firstName' => $owner->getFirstName(),
                    'lastName' => $owner->getLastName(),
                ] : null;
            }

            return $data;
        }, $tournaments));
    }

    #[Route('/api/admin/tournament', name: 'app_tournament_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $entityManager, ProvinceRepository $provinceRepository, TownRepository $townRepository): JsonResponse
    {
        $name = $request->request->get('name');
        if (!$name) {
            return new JsonResponse(['error' => 'El nombre es obligatorio'], 400);
        }

        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setType($request->request->get('type', 'eliminatory'));
        $tournament->setStatus($request->request->get('status', 'draft'));
        $tournament->setStatusDescription($request->request->get('statusDescription'));
        $tournament->setLocation($request->request->get('location'));
        
        if ($request->request->get('provinceId')) {
            $tournament->setProvince($provinceRepository->find($request->request->get('provinceId')));
        }
        if ($request->request->get('townId')) {
            $tournament->setTown($townRepository->find($request->request->get('townId')));
        }

        $tournament->setStartDate($request->request->get('startDate') ? new \DateTimeImmutable($request->request->get('startDate')) : null);
        $tournament->setEndDate($request->request->get('endDate') ? new \DateTimeImmutable($request->request->get('endDate')) : null);
        
        $ruleKings = $request->request->get('ruleKings', 8);
        $tournament->setRuleKings(($ruleKings === 'without' || (int)$ruleKings === 4) ? 4 : 8);
        $tournament->setRulePoints((int)$request->request->get('rulePoints', 30));
        $tournament->setRuleGames((int)$request->request->get('ruleGames', 3));
        $tournament->setTablesCount((int)$request->request->get('tablesCount', 0));
        $tournament->setPrivate(filter_var($request->request->get('private'), FILTER_VALIDATE_BOOLEAN));
        $tournament->setHasThirdPlace(filter_var($request->request->get('hasThirdPlace'), FILTER_VALIDATE_BOOLEAN));
        
        $matchDaysRaw = $request->request->get('leagueMatchDays');
        if ($matchDaysRaw) {
            $matchDays = is_string($matchDaysRaw) ? json_decode($matchDaysRaw, true) : $matchDaysRaw;
            $tournament->setLeagueMatchDays($matchDays);
        }

        $this->handleFileUploads($request, $tournament);

        $tournament->setCreatedBy($this->getUser());

        $entityManager->persist($tournament);
        $entityManager->flush();

        return new JsonResponse(['id' => $tournament->getId(), 'uuid' => $tournament->getUuidAccessToken()]);
    }

    #[Route('/api/admin/tournament/{uuid}', name: 'app_tournament_update', methods: ['PATCH', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $uuid, Request $request, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager, ProvinceRepository $provinceRepository, TownRepository $townRepository): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $contentType = $request->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        } else {
            $data = $request->request->all();
        }

        if (isset($data['name'])) $tournament->setName($data['name']);
        if (isset($data['type'])) $tournament->setType($data['type']);
        if (isset($data['status'])) $tournament->setStatus($data['status']);
        if (array_key_exists('statusDescription', $data)) $tournament->setStatusDescription($data['statusDescription']);
        if (array_key_exists('location', $data)) $tournament->setLocation($data['location']);

        if (array_key_exists('provinceId', $data)) {
            $tournament->setProvince(!empty($data['provinceId']) ? $provinceRepository->find($data['provinceId']) : null);
        }
        if (array_key_exists('townId', $data)) {
            $tournament->setTown(!empty($data['townId']) ? $townRepository->find($data['townId']) : null);
        }

        if (array_key_exists('startDate', $data)) {
            $tournament->setStartDate(!empty($data['startDate']) ? new \DateTimeImmutable($data['startDate']) : null);
        }
        if (array_key_exists('endDate', $data)) {
            $tournament->setEndDate(!empty($data['endDate']) ? new \DateTimeImmutable($data['endDate']) : null);
        }

        if (isset($data['ruleKings'])) {
            $rk = $data['ruleKings'];
            $tournament->setRuleKings(($rk === 'without' || (int)$rk === 4) ? 4 : 8);
        }
        if (isset($data['rulePoints'])) $tournament->setRulePoints((int)$data['rulePoints']);
        if (isset($data['ruleGames'])) $tournament->setRuleGames((int)$data['ruleGames']);
        if (isset($data['tablesCount'])) $tournament->setTablesCount((int)$data['tablesCount']);
        if (isset($data['private'])) $tournament->setPrivate(filter_var($data['private'], FILTER_VALIDATE_BOOLEAN));
        if (isset($data['hasThirdPlace'])) $tournament->setHasThirdPlace(filter_var($data['hasThirdPlace'], FILTER_VALIDATE_BOOLEAN));
        if (isset($data['leagueMatchDays'])) {
            $matchDays = is_string($data['leagueMatchDays']) ? json_decode($data['leagueMatchDays'], true) : $data['leagueMatchDays'];
            $tournament->setLeagueMatchDays($matchDays);
        }

        $this->handleFileUploads($request, $tournament);

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/{uuid}/enroll-team', name: 'app_tournament_enroll_team', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addTeam(string $uuid, Request $request, TournamentRepository $tournamentRepository, TeamRepository $teamRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $data = $request->toArray();
        $teamId = $data['teamId'] ?? null;
        $teamName = $data['name'] ?? null;
        
        $team = null;
        if ($teamId) {
            $team = $teamRepository->find($teamId);
        } elseif ($teamName) {
            // Buscar si ya existe una pareja con ese nombre o crearla
            $team = $teamRepository->findOneBy(['name' => $teamName]);
            if (!$team) {
                $team = new Team();
                $team->setName($teamName);
                $entityManager->persist($team);
            }
        }

        if (!$team) return new JsonResponse(['error' => 'Pareja no encontrada o nombre inválido'], 404);

        foreach ($tournament->getTournamentTeams() as $tt) {
            if ($tt->getTeam()->getId() === $team->getId()) {
                return new JsonResponse(['error' => 'Esta pareja ya está inscrita en el torneo'], 400);
            }
        }

        $isConfirmed = filter_var($data['isConfirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $tt = new TournamentTeam();
        $tt->setTournament($tournament);
        $tt->setTeam($team);
        $tt->setIsConfirmed($isConfirmed);

        $tournament->addTournamentTeam($tt); // Asegurar que la colección se actualiza

        $entityManager->persist($tt);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'team' => [
                'id' => $tt->getId(),
                'name' => $team->getName(),
                'mesa' => $tt->getMesa(),
                'isConfirmed' => $tt->isConfirmed()
            ]
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}/generate-matches', name: 'app_tournament_generate_matches', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateMatches(
        string $uuid, 
        TournamentRepository $tournamentRepository, 
        EntityManagerInterface $entityManager,
        LeagueGenerator $leagueGenerator
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        if (count($tournament->getMatches()) > 0) {
            return new JsonResponse(['error' => 'Ya existe un calendario generado para este torneo'], 400);
        }

        $teams = $tournament->getTournamentTeams()->toArray();
        if (count($teams) < 2) {
            return new JsonResponse(['error' => 'Se necesitan al menos 2 parejas para generar partidas'], 400);
        }

        if ($tournament->getType() === 'eliminatory') {
            shuffle($teams);
            $numTeams = count($teams);
            $rounds = (int)ceil(log($numTeams, 2));
            $bracketSize = (int)pow(2, $rounds);
            $firstRoundMatchesCount = (int)($bracketSize / 2);

            $bracketMatches = [];
            for ($r = $rounds; $r >= 1; $r--) {
                $matchesInRound = (int)pow(2, $r - 1);
                for ($i = 0; $i < $matchesInRound; $i++) {
                    $match = new MusMatch();
                    $match->setTournament($tournament);
                    $match->setBracketRound($r);
                    $match->setBracketPosition($i);
                    $match->setStage($r === 1 ? "Final" : "Ronda $r");
                    $entityManager->persist($match);
                    $bracketMatches[$r][$i] = $match;
                }
            }

            // Distribute teams so the first $firstRoundMatchesCount teams occupy team1 of each match,
            // and the remaining teams occupy team2. This avoids empty (null vs null) matches and distributes Byes cleanly.
            for ($i = 0; $i < $firstRoundMatchesCount; $i++) {
                $match = $bracketMatches[$rounds][$i];
                if ($i < $numTeams) {
                    $match->setTeam1($teams[$i]);
                }
                $team2Idx = $i + $firstRoundMatchesCount;
                if ($team2Idx < $numTeams) {
                    $match->setTeam2($teams[$team2Idx]);
                }

                if ($match->getTeam1() && !$match->getTeam2()) {
                    $match->setWinner($match->getTeam1());
                    $match->setScoreTeam1(0);
                    $match->setScoreTeam2(0);

                    if ($rounds > 1) {
                        $nextRound = $rounds - 1;
                        $nextPos = (int)floor($i / 2);
                        $isTeam2 = ($i % 2 === 1);
                        $nextMatch = $bracketMatches[$nextRound][$nextPos] ?? null;
                        if ($nextMatch) {
                            if ($isTeam2) {
                                $nextMatch->setTeam2($match->getWinner());
                            } else {
                                $nextMatch->setTeam1($match->getWinner());
                            }
                        }
                    }
                }
            }

            if ($tournament->isHasThirdPlace() && $rounds >= 2) {
                $match = new MusMatch();
                $match->setTournament($tournament);
                $match->setBracketRound(1);
                $match->setBracketPosition(1);
                $match->setStage("3er y 4º Puesto");
                $entityManager->persist($match);
            }
        } elseif ($tournament->getType() === 'league') {
            shuffle($teams);
            $leagueGenerator->generate($tournament);
        } else {
            return new JsonResponse(['error' => 'Tipo de torneo no soportado para generación automática'], 400);
        }

        $tournament->setStatus('active');
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/team/{teamId}', name: 'app_tournament_unenroll_team', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeTeam(int $teamId, EntityManagerInterface $entityManager): JsonResponse
    {
        $tt = $entityManager->getRepository(TournamentTeam::class)->find($teamId);
        if (!$tt) return new JsonResponse(['error' => 'Registro no encontrado'], 404);

        $entityManager->remove($tt);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/tournament/{uuid}', name: 'app_tournament_show', methods: ['GET'])]
    public function show(string $uuid, TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $user = $this->getUser();
        $isManager = false;
        if ($user) {
            $isManager = $this->isGranted('ROLE_SUPER_ADMIN')
                || ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $user->getId())
                || $tournament->getManagers()->contains($user);
        }

        $matches = [];
        foreach ($tournament->getMatches() as $match) {
            $games = [];
            foreach ($match->getGames() as $game) {
                $games[] = [
                    'points1' => $game->getPointsTeam1(),
                    'points2' => $game->getPointsTeam2()
                ];
            }

            $matches[] = [
                'id' => $match->getId(),
                'teamA' => $match->getTeam1() ? ['id' => $match->getTeam1()->getId(), 'name' => $match->getTeam1()->getTeam()->getName()] : null,
                'teamB' => $match->getTeam2() ? ['id' => $match->getTeam2()->getId(), 'name' => $match->getTeam2()->getTeam()->getName()] : null,
                'scoreA' => $match->getScoreTeam1(),
                'scoreB' => $match->getScoreTeam2(),
                'games' => $games,
                'status' => ($match->getWinner() !== null || $match->getScoreTeam1() > 0 || $match->getScoreTeam2() > 0) ? 'finished' : 'pending',
                'winnerId' => $match->getWinner() ? $match->getWinner()->getId() : null,
                'stage' => $match->getStage(),
                'bracketRound' => $match->getBracketRound(),
                'bracketPosition' => $match->getBracketPosition(),
                'matchDay' => $match->getMatchDay(),
                'mesa' => $match->getMesa(),
                'deadlineDate' => $match->getDeadlineDate() ? $match->getDeadlineDate()->format('Y-m-d') : null
            ];
        }

        return new JsonResponse([
            'id' => $tournament->getId(),
            'uuid' => $tournament->getUuidAccessToken(),
            'name' => $tournament->getName(),
            'type' => $tournament->getType(),
            'status' => $tournament->getStatus(),
            'statusDescription' => $tournament->getStatusDescription(),
            'startDate' => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate' => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'location' => $tournament->getLocation(),
            'provinceId' => $tournament->getProvince() ? $tournament->getProvince()->getId() : null,
            'provinceName' => $tournament->getProvince() ? $tournament->getProvince()->getName() : null,
            'townId' => $tournament->getTown() ? $tournament->getTown()->getId() : null,
            'townName' => $tournament->getTown() ? $tournament->getTown()->getName() : null,
            'posterPath' => $tournament->getPosterPath(),
            'rulesPath' => $tournament->getRulesPath(),
            'private' => $tournament->isPrivate(),
            'tablesCount' => $tournament->getTablesCount(),
            'leagueMatchDays' => $tournament->getLeagueMatchDays(),
            'ruleKings' => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames' => $tournament->getRuleGames(),
            'hasThirdPlace' => $tournament->isHasThirdPlace(),
            'isManager' => $isManager,
            'teamsCount' => count($tournament->getTournamentTeams()),
            'matches' => $matches,
            'teams' => array_map(function(TournamentTeam $tt) {
                return [
                    'id' => $tt->getId(),
                    'name' => $tt->getTeam()->getName(),
                    'mesa' => $tt->getMesa(),
                    'isConfirmed' => $tt->isConfirmed()
                ];
            }, $tournament->getTournamentTeams()->toArray())
        ]);
    }

    #[Route('/api/admin/tournament/team/{id}/toggle-confirm', name: 'app_tournament_team_toggle_confirm', methods: ['POST'])]
    public function toggleConfirmTeam(
        int $id,
        TournamentTeamRepository $ttRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $tt = $ttRepository->find($id);
        if (!$tt) return new JsonResponse(['error' => 'Inscripción no encontrada'], 404);

        $tt->setIsConfirmed(!$tt->isConfirmed());
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'isConfirmed' => $tt->isConfirmed()
        ]);
    }

    #[Route('/api/admin/match/{id}/update', name: 'app_match_update', methods: ['POST'])]
    public function updateMatch(
        int $id,
        Request $request,
        MusMatchRepository $matchRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub
    ): JsonResponse {
        $match = $matchRepository->find($id);
        if (!$match) return new JsonResponse(['error' => 'Partido no encontrado'], 404);

        $data = json_decode($request->getContent(), true) ?? [];
        $gamesData = $data['games'] ?? [];
        $tournament = $match->getTournament();
        $pointsToWinGame = $tournament->getRulePoints() ?: 40;

        if (array_key_exists('deadlineDate', $data)) {
            $match->setDeadlineDate(!empty($data['deadlineDate']) ? new \DateTimeImmutable($data['deadlineDate']) : null);
        }

        // Clear existing games using the collection to let orphanRemoval work
        foreach ($match->getGames() as $game) {
            $match->removeGame($game);
        }
        $entityManager->flush();

        $score1 = 0;
        $score2 = 0;

        foreach ($gamesData as $index => $gData) {
            $p1 = (int)($gData['points1'] ?? 0);
            $p2 = (int)($gData['points2'] ?? 0);

            if ($p1 > 0 || $p2 > 0) {
                $game = new MusMatchGame();
                $game->setMusMatch($match);
                $game->setPointsTeam1($p1);
                $game->setPointsTeam2($p2);
                $game->setGameNumber($index + 1);
                $entityManager->persist($game);

                if ($p1 >= $pointsToWinGame) $score1++;
                if ($p2 >= $pointsToWinGame) $score2++;
            }
        }

        $match->setScoreTeam1($score1);
        $match->setScoreTeam2($score2);

        // Set winner for classification
        if ($score1 > 0 || $score2 > 0) {
            if ($score1 > $score2) {
                $match->setWinner($match->getTeam1());
            } elseif ($score2 > $score1) {
                $match->setWinner($match->getTeam2());
            } else {
                $match->setWinner(null);
            }
        } else {
            $match->setWinner(null);
        }

        if ($match->getBracketRound() !== null) {
            $this->advanceWinnerInBracket($match, $entityManager);
        }

        $entityManager->flush();

        // Update tournament status if all matches have a winner
        $allMatches = $tournament->getMatches();
        if (count($allMatches) > 0) {
            $allFinished = true;
            foreach ($allMatches as $m) {
                if ($m->getWinner() === null) {
                    $allFinished = false;
                    break;
                }
            }
            if ($allFinished && $tournament->getStatus() === 'active') {
                $tournament->setStatus('finished');
                $entityManager->flush();
            } elseif (!$allFinished && $tournament->getStatus() === 'finished') {
                $tournament->setStatus('active');
                $entityManager->flush();
            }
        }

        // Notify via Mercure
        $update = new Update(
            "tournament/{$tournament->getUuidAccessToken()}",
            json_encode([
                'matchId' => $match->getId(),
                'score1' => $match->getScoreTeam1(),
                'score2' => $match->getScoreTeam2(),
                'winnerId' => $match->getWinner() ? $match->getWinner()->getId() : null,
                'bracketRound' => $match->getBracketRound(),
                'finished' => ($match->getWinner() !== null || $score1 > 0 || $score2 > 0),
                'games' => $gamesData
            ])
        );
        $hub->publish($update);

        return new JsonResponse([
            'success' => true,
            'scoreA' => $match->getScoreTeam1(),
            'scoreB' => $match->getScoreTeam2(),
            'winnerId' => $match->getWinner() ? $match->getWinner()->getId() : null,
            'status' => ($match->getWinner() !== null || $score1 > 0 || $score2 > 0) ? 'finished' : 'pending'
        ]);
    }

    #[Route('/api/tournament/{uuid}/classification', name: 'app_tournament_classification', methods: ['GET'])]
    public function getClassification(
        string $uuid, 
        TournamentRepository $tournamentRepository,
        StandingCalculator $standingCalculator
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        if ($tournament->getType() === 'league') {
            return new JsonResponse($standingCalculator->calculate($tournament));
        }

        $classification = [];
        foreach ($tournament->getTournamentTeams() as $tt) {
            $gn = $tt->getMesa() ?: 'General';
            $classification[$gn][] = [
                'teamName' => $tt->getTeam()->getName(),
                'points' => $tt->getPoints(),
                'won' => $tt->getGamesWon(),
                'lost' => $tt->getGamesLost(),
                'played' => $tt->getMatchesPlayed(),
            ];
        }

        foreach ($classification as $key => $values) {
            usort($classification[$key], function($a, $b) {
                if ($b['points'] === $a['points']) {
                    return ($b['won'] - $b['lost']) <=> ($a['won'] - $a['lost']);
                }
                return $b['points'] <=> $a['points'];
            });
        }

        return new JsonResponse($classification);
    }

    private function advanceWinnerInBracket(MusMatch $match, EntityManagerInterface $entityManager): void
    {
        if ($match->getBracketRound() <= 1) {
            return;
        }

        $tournament = $match->getTournament();
        $nextRound = $match->getBracketRound() - 1;
        $nextPos = (int)floor($match->getBracketPosition() / 2);
        $isTeam2 = ($match->getBracketPosition() % 2 === 1);

        $nextMatch = $entityManager->getRepository(MusMatch::class)->findOneBy([
            'tournament' => $tournament,
            'bracketRound' => $nextRound,
            'bracketPosition' => $nextPos
        ]);

        if ($nextMatch && $nextMatch->getWinner() === null && $nextMatch->getScoreTeam1() === 0 && $nextMatch->getScoreTeam2() === 0) {
            if ($isTeam2) {
                $nextMatch->setTeam2($match->getWinner());
            } else {
                $nextMatch->setTeam1($match->getWinner());
            }
        }

        if ($match->getBracketRound() === 2 && $tournament->isHasThirdPlace()) {
            $thirdPlaceMatch = $entityManager->getRepository(MusMatch::class)->findOneBy([
                'tournament' => $tournament,
                'bracketRound' => 1,
                'bracketPosition' => 1
            ]);

            if ($thirdPlaceMatch && $thirdPlaceMatch->getWinner() === null && $thirdPlaceMatch->getScoreTeam1() === 0 && $thirdPlaceMatch->getScoreTeam2() === 0) {
                $loser = null;
                if ($match->getWinner() !== null) {
                    $loser = ($match->getWinner() === $match->getTeam1()) ? $match->getTeam2() : $match->getTeam1();
                }
                if ($match->getBracketPosition() === 0) {
                    $thirdPlaceMatch->setTeam1($loser);
                } else {
                    $thirdPlaceMatch->setTeam2($loser);
                }
            }
        }
    }

    private function handleFileUploads(Request $request, Tournament $tournament): void
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/tournaments';

        $posterFile = $request->files->get('poster');
        if ($posterFile && $posterFile->isValid()) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = strtolower($posterFile->guessExtension() ?: $posterFile->getClientOriginalExtension() ?: 'jpg');
            $filename = uniqid('poster_', true) . '.' . $ext;
            $posterFile->move($uploadDir, $filename);
            $tournament->setPosterPath('/uploads/tournaments/' . $filename);
        }

        $rulesFile = $request->files->get('rulesFile');
        if ($rulesFile && $rulesFile->isValid()) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = strtolower($rulesFile->guessExtension() ?: $rulesFile->getClientOriginalExtension() ?: 'pdf');
            $filename = uniqid('rules_', true) . '.' . $ext;
            $rulesFile->move($uploadDir, $filename);
            $tournament->setRulesPath('/uploads/tournaments/' . $filename);
        }
    }
}
