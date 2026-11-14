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
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
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

    #[Route('/api/admin/tournament/{uuid}/generate-groups', name: 'app_tournament_generate_groups', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateGroups(
        string $uuid,
        Request $request,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $contentType = $request->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
            $groupsCountRaw = $data['groupsCount'] ?? null;
        } else {
            $groupsCountRaw = $request->request->get('groupsCount');
        }

        $teams = $tournament->getTournamentTeams()->toArray();
        if (count($teams) < 2) {
            return new JsonResponse(['error' => 'Se necesitan al menos 2 parejas para sortear grupos'], 400);
        }

        $defaultGroups = count($teams) >= 16 ? 4 : 2;
        $groupsCount = max(2, min(26, (int)($groupsCountRaw ?: $defaultGroups)));

        shuffle($teams);
        $groupLetters = range('A', 'Z');
        foreach ($teams as $index => $tt) {
            $groupIndex = $index % $groupsCount;
            $tt->setMesa('Grupo ' . $groupLetters[$groupIndex]);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'groupsCount' => $groupsCount
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}/generate-matches', name: 'app_tournament_generate_matches', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateMatches(
        string $uuid,
        Request $request,
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
            $this->createEliminatoryBracket($tournament, $teams, $entityManager);
        } elseif ($tournament->getType() === 'league') {
            shuffle($teams);
            $leagueGenerator->generate($tournament);
        } elseif ($tournament->getType() === 'groups') {
            $data = [];
            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode($request->getContent(), true) ?? [];
            } else {
                $data = $request->request->all();
            }

            $defaultGroups = count($teams) >= 12 ? 4 : 2;
            $groupsCount = max(2, min(26, (int)($data['groupsCount'] ?? $defaultGroups)));
            $redrawGroups = filter_var($data['redrawGroups'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $existingGroups = array_unique(array_filter(array_map(fn(TournamentTeam $tt) => $tt->getMesa(), $teams)));
            $unassigned = array_filter($teams, fn(TournamentTeam $tt) => empty($tt->getMesa()));

            // Re-assign groups if any team is unassigned, if the requested groupsCount differs from existing, or if redrawGroups is requested
            if (count($unassigned) > 0 || count($existingGroups) !== $groupsCount || $redrawGroups) {
                shuffle($teams);
                $groupLetters = range('A', 'Z');
                foreach ($teams as $index => $tt) {
                    $groupIndex = $index % $groupsCount;
                    $tt->setMesa('Grupo ' . $groupLetters[$groupIndex]);
                }
            }

            $existingConfig = is_array($tournament->getLeagueMatchDays()) ? $tournament->getLeagueMatchDays() : [];
            $rawMatchDays = $data['matchDays'] ?? ($existingConfig['matchDays'] ?? (array_is_list($existingConfig) ? $existingConfig : []));
            $matchDays = is_array($rawMatchDays) ? array_values(array_filter(array_map('intval', $rawMatchDays))) : [];
            sort($matchDays);

            $tournament->setLeagueMatchDays([
                'groupsCount' => $groupsCount,
                'teamsPerGroup' => max(1, (int)($data['teamsPerGroup'] ?? $existingConfig['teamsPerGroup'] ?? 2)),
                'pairingMode' => $data['pairingMode'] ?? $existingConfig['pairingMode'] ?? 'seeded',
                'matchDays' => $matchDays,
            ]);

            $groups = [];
            foreach ($teams as $tt) {
                $groupName = $tt->getMesa() ?: 'Grupo A';
                $groups[$groupName][] = $tt;
            }
            ksort($groups, SORT_NATURAL);

            // Calculate max rounds across groups to pre-calculate deadlines per jornada if matchDays are configured
            $maxRounds = 0;
            foreach ($groups as $groupTeams) {
                $c = count($groupTeams);
                if ($c >= 2) {
                    $rCount = ($c % 2 !== 0) ? $c : ($c - 1);
                    if ($rCount > $maxRounds) {
                        $maxRounds = $rCount;
                    }
                }
            }

            $deadlineByRound = [];
            if (!empty($matchDays) && $maxRounds > 0) {
                $currentDeadline = $tournament->getStartDate() ?? new \DateTimeImmutable();
                for ($r = 0; $r < $maxRounds; $r++) {
                    if ($r === 0) {
                        for ($i = 0; $i < 7; $i++) {
                            $testDate = $currentDeadline->modify("+$i days");
                            $dayNum = (int)$testDate->format('N');
                            if (in_array($dayNum, $matchDays, true)) {
                                $currentDeadline = $testDate;
                                break;
                            }
                        }
                    } else {
                        $currentDayNum = (int)$currentDeadline->format('N');
                        $nextDay = null;
                        foreach ($matchDays as $md) {
                            if ($md > $currentDayNum) {
                                $nextDay = $md;
                                break;
                            }
                        }
                        if ($nextDay === null) {
                            $nextDay = $matchDays[0];
                            $daysToAdd = (7 - $currentDayNum) + $nextDay;
                        } else {
                            $daysToAdd = $nextDay - $currentDayNum;
                        }
                        $currentDeadline = $currentDeadline->modify("+$daysToAdd days");
                    }
                    $deadlineByRound[$r + 1] = $currentDeadline;
                }
            }

            foreach ($groups as $groupName => $groupTeams) {
                $gTeams = array_values($groupTeams);
                shuffle($gTeams);
                if (count($gTeams) < 2) {
                    continue;
                }
                if (count($gTeams) % 2 !== 0) {
                    $gTeams[] = null;
                }
                $gCount = count($gTeams);
                $gRounds = $gCount - 1;
                $half = (int)($gCount / 2);

                for ($round = 1; $round <= $gRounds; $round++) {
                    for ($i = 0; $i < $half; $i++) {
                        $t1 = $gTeams[$i];
                        $t2 = $gTeams[$gCount - 1 - $i];
                        if ($t1 !== null && $t2 !== null) {
                            $match = new MusMatch();
                            $match->setTournament($tournament);
                            if ($round % 2 === 0 && $i === 0) {
                                $match->setTeam1($t2);
                                $match->setTeam2($t1);
                            } else {
                                $match->setTeam1($t1);
                                $match->setTeam2($t2);
                            }
                            $match->setMatchDay($round);
                            $match->setStage($groupName);
                            $match->setMesa($groupName);
                            if (isset($deadlineByRound[$round])) {
                                $match->setDeadlineDate($deadlineByRound[$round]);
                            }
                            $entityManager->persist($match);
                        }
                    }
                    $last = array_pop($gTeams);
                    array_splice($gTeams, 1, 0, [$last]);
                }
            }
        } else {
            return new JsonResponse(['error' => 'Tipo de torneo no soportado para generación automática'], 400);
        }

        $tournament->setStatus('active');
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/{uuid}/reset-matches', name: 'app_tournament_reset_matches', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resetMatches(
        string $uuid,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        foreach ($tournament->getMatches()->toArray() as $match) {
            foreach ($match->getGames()->toArray() as $game) {
                $match->removeGame($game);
                $entityManager->remove($game);
            }
            $tournament->removeMatch($match);
            $entityManager->remove($match);
        }

        foreach ($tournament->getTournamentTeams() as $tt) {
            $tt->setMesa(null);
        }

        $tournament->setStatus('pending');
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/{uuid}/generate-knockout', name: 'app_tournament_generate_knockout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateKnockoutPhase(
        string $uuid,
        Request $request,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager,
        StandingCalculator $standingCalculator
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        if ($tournament->getType() !== 'groups') {
            return new JsonResponse(['error' => 'Este torneo no es de tipo Liguilla + Eliminatorias'], 400);
        }

        foreach ($tournament->getMatches() as $existingMatch) {
            if ($existingMatch->getBracketRound() !== null) {
                return new JsonResponse(['error' => 'El cuadro eliminatorio ya ha sido generado'], 400);
            }
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $existingConfig = is_array($tournament->getLeagueMatchDays()) ? $tournament->getLeagueMatchDays() : [];
        $teamsPerGroup = max(1, (int)($data['teamsPerGroup'] ?? $existingConfig['teamsPerGroup'] ?? 2));
        $pairingMode = $data['pairingMode'] ?? $existingConfig['pairingMode'] ?? 'seeded';

        $groupedStandings = $standingCalculator->calculateByGroups($tournament);
        if (empty($groupedStandings)) {
            return new JsonResponse(['error' => 'No hay grupos configurados en este torneo'], 400);
        }

        $tournament->setLeagueMatchDays([
            'groupsCount' => count($groupedStandings),
            'teamsPerGroup' => $teamsPerGroup,
            'pairingMode' => $pairingMode,
            'matchDays' => $existingConfig['matchDays'] ?? []
        ]);

        $ttById = [];
        foreach ($tournament->getTournamentTeams() as $tt) {
            $ttById[$tt->getId()] = $tt;
        }

        $groupNames = array_keys($groupedStandings);
        $numGroups = count($groupNames);

        $qualifiedTeams = [];
        if ($pairingMode === 'seeded' && $teamsPerGroup === 2 && $numGroups >= 2) {
            // Standard cross-group seeding:
            // Left wing gets 1st of even groups (A, C...) vs 2nd of odd groups (B, D...)
            // Right wing gets 1st of odd groups (B, D...) vs 2nd of even groups (A, C...)
            // This guarantees 1st plays 2nd of another group, and teams from the same group only meet in the Final.
            $evenGroupIndices = [];
            $oddGroupIndices = [];
            for ($g = 0; $g < $numGroups; $g++) {
                if ($g % 2 === 0) {
                    $evenGroupIndices[] = $g;
                } else {
                    $oddGroupIndices[] = $g;
                }
            }
            $firstPlaceOrder = array_merge($evenGroupIndices, $oddGroupIndices);
            $secondPlaceOrder = array_merge($oddGroupIndices, $evenGroupIndices);

            $team1List = [];
            foreach ($firstPlaceOrder as $gIdx) {
                $gName = $groupNames[$gIdx];
                if (isset($groupedStandings[$gName][0])) {
                    $id = $groupedStandings[$gName][0]['id'];
                    if (isset($ttById[$id])) {
                        $team1List[] = $ttById[$id];
                    }
                }
            }

            $team2List = [];
            foreach ($secondPlaceOrder as $gIdx) {
                $gName = $groupNames[$gIdx];
                if (isset($groupedStandings[$gName][1])) {
                    $id = $groupedStandings[$gName][1]['id'];
                    if (isset($ttById[$id])) {
                        $team2List[] = $ttById[$id];
                    }
                }
            }

            $qualifiedTeams = array_merge($team1List, $team2List);
        } else {
            // General collection by rank across groups
            for ($rank = 0; $rank < $teamsPerGroup; $rank++) {
                for ($g = 0; $g < $numGroups; $g++) {
                    // Shift group order on odd ranks so teams from the same group don't meet in match $i
                    $gIdx = ($rank % 2 === 1) ? (($g + 1) % $numGroups) : $g;
                    $gName = $groupNames[$gIdx];
                    if (isset($groupedStandings[$gName][$rank])) {
                        $id = $groupedStandings[$gName][$rank]['id'];
                        if (isset($ttById[$id])) {
                            $qualifiedTeams[] = $ttById[$id];
                        }
                    }
                }
            }

            if ($pairingMode === 'random') {
                shuffle($qualifiedTeams);
            }
        }

        if (count($qualifiedTeams) < 2) {
            return new JsonResponse(['error' => 'Se necesitan al menos 2 parejas clasificadas para generar las eliminatorias'], 400);
        }

        $this->createEliminatoryBracket($tournament, $qualifiedTeams, $entityManager);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'qualifiedCount' => count($qualifiedTeams)
        ]);
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
    public function show(string $uuid, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        // Auto-backfill matchDay for existing 'groups' tournaments whose group matches lacked matchDay
        if ($tournament->getType() === 'groups') {
            $groupMatchesByStage = [];
            $needsBackfill = false;
            foreach ($tournament->getMatches() as $m) {
                if ($m->getBracketRound() === null) {
                    $stageKey = $m->getStage() ?: ($m->getMesa() ?: 'Grupo A');
                    $groupMatchesByStage[$stageKey][] = $m;
                    if ($m->getMatchDay() === null) {
                        $needsBackfill = true;
                    }
                }
            }
            if ($needsBackfill) {
                foreach ($groupMatchesByStage as $stageKey => $gMatches) {
                    usort($gMatches, fn(MusMatch $a, MusMatch $b) => ($a->getId() ?? 0) <=> ($b->getId() ?? 0));
                    $round = 1;
                    $usedTeamIds = [];
                    foreach ($gMatches as $gm) {
                        $t1Id = $gm->getTeam1()?->getId();
                        $t2Id = $gm->getTeam2()?->getId();
                        if (($t1Id !== null && isset($usedTeamIds[$t1Id])) || ($t2Id !== null && isset($usedTeamIds[$t2Id]))) {
                            $round++;
                            $usedTeamIds = [];
                        }
                        if ($t1Id !== null) $usedTeamIds[$t1Id] = true;
                        if ($t2Id !== null) $usedTeamIds[$t2Id] = true;
                        if ($gm->getMatchDay() === null) {
                            $gm->setMatchDay($round);
                        }
                    }
                }
                $entityManager->flush();
            }
        }

        $user = $this->getUser();
        $isManager = false;
        $isOwner = false;
        if ($user) {
            $isOwner = $this->isGranted('ROLE_SUPER_ADMIN')
                || ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $user->getId());
            $isManager = $isOwner || $tournament->getManagers()->contains($user);
        }

        $ownerData = null;
        $managersData = [];
        $pendingManagersData = [];
        if ($isManager) {
            $creator = $tournament->getCreatedBy();
            if ($creator) {
                $ownerData = [
                    'id' => $creator->getId(),
                    'email' => $creator->getEmail(),
                    'firstName' => $creator->getFirstName(),
                    'lastName' => $creator->getLastName(),
                ];
            }
            foreach ($tournament->getManagers() as $mgr) {
                $managersData[] = [
                    'id' => $mgr->getId(),
                    'email' => $mgr->getEmail(),
                    'firstName' => $mgr->getFirstName(),
                    'lastName' => $mgr->getLastName(),
                ];
            }
            $pendingManagersData = $tournament->getPendingManagers();
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
            'isOwner' => $isOwner,
            'owner' => $ownerData,
            'managers' => $managersData,
            'pendingManagers' => $pendingManagersData,
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

    #[Route('/api/admin/tournament/{uuid}/managers', name: 'app_tournament_invite_managers', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function inviteManagers(
        string $uuid,
        Request $request,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) {
            return new JsonResponse(['error' => 'Torneo no encontrado'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isOwner = $currentUser && (
            $this->isGranted('ROLE_SUPER_ADMIN')
            || ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $currentUser->getId())
        );

        if (!$isOwner) {
            return new JsonResponse(['error' => 'Solo el propietario del torneo puede gestionar administradores'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $invitationsInput = $data['invitations'] ?? [];

        // Also support passing "emails" (array or comma/newline-separated string) + "locale"
        if (empty($invitationsInput) && !empty($data['emails'])) {
            $defaultLocale = $data['locale'] ?? 'es';
            $rawEmails = is_array($data['emails'])
                ? $data['emails']
                : preg_split('/[\s,;]+/', (string)$data['emails'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($rawEmails as $rawEmail) {
                $invitationsInput[] = [
                    'email' => $rawEmail,
                    'locale' => $defaultLocale,
                ];
            }
        }

        if (empty($invitationsInput)) {
            return new JsonResponse(['error' => 'Debes indicar al menos un correo electrónico'], 400);
        }

        $allowedLocales = ['es', 'eu', 'en'];
        $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? 'https://musmanager.com', '/');
        $inviterName = trim(($currentUser->getFirstName() ?? '') . ' ' . ($currentUser->getLastName() ?? ''));
        if ($inviterName === '') {
            $inviterName = $currentUser->getEmail();
        }

        $userRepo = $entityManager->getRepository(User::class);
        $pendingList = $tournament->getPendingManagers();
        $results = [];

        foreach ($invitationsInput as $item) {
            $rawEmail = is_array($item) ? ($item['email'] ?? '') : (string)$item;
            $locale = is_array($item) ? ($item['locale'] ?? 'es') : ($data['locale'] ?? 'es');
            if (!in_array($locale, $allowedLocales, true)) {
                $locale = 'es';
            }

            $email = strtolower(trim($rawEmail));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results[] = [
                    'email' => $rawEmail,
                    'status' => 'invalid_email',
                ];
                continue;
            }

            // Check if email belongs to tournament creator
            if ($tournament->getCreatedBy() && strtolower($tournament->getCreatedBy()->getEmail()) === $email) {
                $results[] = [
                    'email' => $email,
                    'status' => 'already_owner',
                ];
                continue;
            }

            // Look up existing user case-insensitively
            $existingUser = $userRepo->createQueryBuilder('u')
                ->where('LOWER(u.email) = :email')
                ->setParameter('email', $email)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingUser instanceof User) {
                // Add as active manager on the tournament
                $tournament->addManager($existingUser);

                // Ensure the user also has ROLE_ADMIN
                $roles = $existingUser->getRoles();
                if (!in_array('ROLE_ADMIN', $roles, true)) {
                    $roles[] = 'ROLE_ADMIN';
                    $existingUser->setRoles(array_values(array_unique($roles)));
                }

                // Remove from pendingManagers if present
                $pendingList = array_values(array_filter($pendingList, fn($p) => strtolower($p['email'] ?? '') !== $email));

                $actionLink = $frontendUrl . '/tournament/' . $tournament->getUuidAccessToken() . '/manage';
                $subject = $translator->trans('emails.admin_invite.existing_subject', [
                    '%tournament%' => $tournament->getName()
                ], 'messages', $locale);

                $emailSent = $this->sendAdminInviteEmail(
                    $mailer,
                    $existingUser->getEmail(),
                    $subject,
                    $tournament,
                    $inviterName,
                    $locale,
                    true,
                    $actionLink
                );

                $results[] = [
                    'email' => $existingUser->getEmail(),
                    'status' => 'added_existing_user',
                    'isExistingUser' => true,
                    'locale' => $locale,
                    'emailSent' => $emailSent,
                ];
            } else {
                // User is not registered yet -> store pending invitation & send registration invite email
                $token = bin2hex(random_bytes(16));
                $foundIdx = null;
                foreach ($pendingList as $idx => $p) {
                    if (strtolower($p['email'] ?? '') === $email) {
                        $foundIdx = $idx;
                        break;
                    }
                }
                $inviteRecord = [
                    'email' => $email,
                    'locale' => $locale,
                    'invitedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    'token' => $token,
                ];
                if ($foundIdx !== null) {
                    $pendingList[$foundIdx] = $inviteRecord;
                } else {
                    $pendingList[] = $inviteRecord;
                }

                $actionLink = $frontendUrl . '/register?email=' . urlencode($email)
                    . '&tournament=' . urlencode($tournament->getUuidAccessToken())
                    . '&invite=' . urlencode($token)
                    . '&lang=' . urlencode($locale);

                $subject = $translator->trans('emails.admin_invite.new_subject', [
                    '%tournament%' => $tournament->getName()
                ], 'messages', $locale);

                $emailSent = $this->sendAdminInviteEmail(
                    $mailer,
                    $email,
                    $subject,
                    $tournament,
                    $inviterName,
                    $locale,
                    false,
                    $actionLink
                );

                $results[] = [
                    'email' => $email,
                    'status' => 'invited_new_user',
                    'isExistingUser' => false,
                    'locale' => $locale,
                    'emailSent' => $emailSent,
                ];
            }
        }

        $tournament->setPendingManagers($pendingList);
        $entityManager->flush();

        $managersData = [];
        foreach ($tournament->getManagers() as $mgr) {
            $managersData[] = [
                'id' => $mgr->getId(),
                'email' => $mgr->getEmail(),
                'firstName' => $mgr->getFirstName(),
                'lastName' => $mgr->getLastName(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'results' => $results,
            'managers' => $managersData,
            'pendingManagers' => $tournament->getPendingManagers(),
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}/managers', name: 'app_tournament_remove_manager', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeManager(
        string $uuid,
        Request $request,
        TournamentRepository $tournamentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) {
            return new JsonResponse(['error' => 'Torneo no encontrado'], 404);
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $isOwner = $currentUser && (
            $this->isGranted('ROLE_SUPER_ADMIN')
            || ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $currentUser->getId())
        );

        if (!$isOwner) {
            return new JsonResponse(['error' => 'Solo el propietario del torneo puede eliminar administradores'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $userId = $data['userId'] ?? $request->query->get('userId');
        $email = strtolower(trim((string)($data['email'] ?? $request->query->get('email') ?? '')));

        if ($userId) {
            foreach ($tournament->getManagers() as $mgr) {
                if ($mgr->getId() === (int)$userId) {
                    $tournament->removeManager($mgr);
                    break;
                }
            }
        }

        if ($email !== '') {
            foreach ($tournament->getManagers() as $mgr) {
                if (strtolower($mgr->getEmail()) === $email) {
                    $tournament->removeManager($mgr);
                }
            }
            $pendingList = array_values(array_filter(
                $tournament->getPendingManagers(),
                fn($p) => strtolower($p['email'] ?? '') !== $email
            ));
            $tournament->setPendingManagers($pendingList);
        }

        $entityManager->flush();

        $managersData = [];
        foreach ($tournament->getManagers() as $mgr) {
            $managersData[] = [
                'id' => $mgr->getId(),
                'email' => $mgr->getEmail(),
                'firstName' => $mgr->getFirstName(),
                'lastName' => $mgr->getLastName(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'managers' => $managersData,
            'pendingManagers' => $tournament->getPendingManagers(),
        ]);
    }

    private function sendAdminInviteEmail(
        MailerInterface $mailer,
        string $recipientEmail,
        string $subject,
        Tournament $tournament,
        string $inviterName,
        string $locale,
        bool $isExistingUser,
        string $actionLink
    ): bool {
        try {
            $emailMessage = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'info@musmanager.com')
                ->to($recipientEmail)
                ->subject($subject)
                ->html($this->renderView('emails/tournament_admin_invite.html.twig', [
                    'tournament' => $tournament,
                    'inviterName' => $inviterName,
                    'recipientEmail' => $recipientEmail,
                    'locale' => $locale,
                    'isExistingUser' => $isExistingUser,
                    'actionLink' => $actionLink,
                ]));

            $mailer->send($emailMessage);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
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
        // For 'groups' tournaments, only auto-finish if the knockout bracket has already been generated
        $allMatches = $tournament->getMatches();
        if (count($allMatches) > 0) {
            $allFinished = true;
            $hasKnockoutMatches = false;
            foreach ($allMatches as $m) {
                if ($m->getBracketRound() !== null) {
                    $hasKnockoutMatches = true;
                }
                if ($m->getWinner() === null) {
                    $allFinished = false;
                }
            }
            $canAutoFinish = $tournament->getType() !== 'groups' || $hasKnockoutMatches;
            if ($allFinished && $canAutoFinish && $tournament->getStatus() === 'active') {
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

        if ($tournament->getType() === 'groups') {
            return new JsonResponse($standingCalculator->calculateByGroups($tournament));
        }

        return new JsonResponse($standingCalculator->calculateByGroups($tournament));
    }

    /**
     * @param TournamentTeam[] $teams
     */
    private function createEliminatoryBracket(Tournament $tournament, array $teams, EntityManagerInterface $entityManager): void
    {
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
