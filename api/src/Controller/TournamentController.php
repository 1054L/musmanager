<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\Team;
use App\Entity\MusMatch;
use App\Entity\MusMatchGame;
use App\Entity\Province;
use App\Entity\Town;
use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use App\Repository\ProvinceRepository;
use App\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TournamentController extends AbstractController
{
    #[Route('/user/tournaments', name: 'app_tournament_list_managed', methods: ['GET'])]
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
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinceRepository $provinceRepository, TownRepository $townRepository): JsonResponse
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
        
        if ($provinceId = $request->request->get('provinceId')) {
            $tournament->setProvince($provinceRepository->find($provinceId));
        }
        if ($townId = $request->request->get('townId')) {
            $tournament->setTown($townRepository->find($townId));
        }
        $tournament->setCreatedBy($this->getUser());
        
        if ($startDate = $request->request->get('startDate')) {
            $tournament->setStartDate(new \DateTimeImmutable($startDate));
        }

        if ($endDate = $request->request->get('endDate')) {
            $tournament->setEndDate(new \DateTimeImmutable($endDate));
        }

        $tournament->setRuleKings((int) $request->request->get('ruleKings', 8));
        $tournament->setRulePoints((int) $request->request->get('rulePoints', 40));
        $tournament->setRuleGames((int) $request->request->get('ruleGames', 3));
        
        if ($request->request->has('tablesCount')) {
            $tournament->setTablesCount($request->request->get('tablesCount') !== null ? (int) $request->request->get('tablesCount') : null);
        }

        $tournament->setPrivate(filter_var($request->request->get('private'), FILTER_VALIDATE_BOOLEAN));
        $tournament->setHasThirdPlace(filter_var($request->request->get('hasThirdPlace'), FILTER_VALIDATE_BOOLEAN));
        
        $posterFile = $request->files->get('poster');
        if ($posterFile) {
            $filename = $this->handleFileUpload($posterFile, $slugger, 'posters');
            $tournament->setPosterPath('/uploads/posters/' . $filename);
        }

        $rulesFile = $request->files->get('rulesFile');
        if ($rulesFile) {
            $filename = $this->handleFileUpload($rulesFile, $slugger, 'rules');
            $tournament->setRulesPath('/uploads/rules/' . $filename);
        }

        $entityManager->persist($tournament);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $tournament->getId(),
            'name' => $tournament->getName(),
            'uuid' => $tournament->getUuidAccessToken(),
            'status'     => $tournament->getStatus(),
            'statusDescription' => $tournament->getStatusDescription(),
            'type'       => $tournament->getType(),
            'private'    => $tournament->isPrivate(),
            'hasThirdPlace' => $tournament->isHasThirdPlace(),
            'startDate'  => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate'    => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'ruleKings'  => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames'  => $tournament->getRuleGames(),
            'tablesCount'=> $tournament->getTablesCount(),
            'location'   => $tournament->getLocation(),
            'provinceId' => $tournament->getProvince() ? $tournament->getProvince()->getId() : null,
            'provinceName' => $tournament->getProvince() ? $tournament->getProvince()->getName() : null,
            'townId' => $tournament->getTown() ? $tournament->getTown()->getId() : null,
            'townName' => $tournament->getTown() ? $tournament->getTown()->getName() : null,
            'posterPath' => $tournament->getPosterPath(),
            'rulesPath'  => $tournament->getRulesPath(),
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}', name: 'app_tournament_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $uuid, Request $request, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinceRepository $provinceRepository, TownRepository $townRepository): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);

        if (!$tournament) {
            return new JsonResponse(['error' => 'Torneo no encontrado'], 404);
        }

        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tournament);

        $requestData = $request->request;

        if ($requestData->has('name')) {
            $tournament->setName($requestData->get('name'));
        }
        if ($requestData->has('type')) {
            $tournament->setType($requestData->get('type'));
        }
        if ($requestData->has('status')) {
            $tournament->setStatus($requestData->get('status'));
        }
        if ($requestData->has('statusDescription')) {
            $tournament->setStatusDescription($requestData->get('statusDescription'));
        }
        if ($requestData->has('location')) {
            $tournament->setLocation($requestData->get('location'));
        }
        if ($requestData->has('provinceId')) {
            $tournament->setProvince($requestData->get('provinceId') ? $provinceRepository->find($requestData->get('provinceId')) : null);
        }
        if ($requestData->has('townId')) {
            $tournament->setTown($requestData->get('townId') ? $townRepository->find($requestData->get('townId')) : null);
        }
        if ($requestData->has('startDate')) {
            $val = $requestData->get('startDate');
            $tournament->setStartDate($val ? new \DateTimeImmutable($val) : null);
        }
        if ($requestData->has('endDate')) {
            $val = $requestData->get('endDate');
            $tournament->setEndDate($val ? new \DateTimeImmutable($val) : null);
        }

        if ($requestData->has('ruleKings')) {
            $tournament->setRuleKings((int) $requestData->get('ruleKings'));
        }
        if ($requestData->has('rulePoints')) {
            $tournament->setRulePoints((int) $requestData->get('rulePoints'));
        }
        if ($requestData->has('ruleGames')) {
            $tournament->setRuleGames((int) $requestData->get('ruleGames'));
        }
        if ($requestData->has('tablesCount')) {
            $tournament->setTablesCount($requestData->get('tablesCount') !== "" ? (int) $requestData->get('tablesCount') : null);
        }
        if ($requestData->has('private')) {
            $tournament->setPrivate(filter_var($requestData->get('private'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($requestData->has('hasThirdPlace')) {
            $tournament->setHasThirdPlace(filter_var($requestData->get('hasThirdPlace'), FILTER_VALIDATE_BOOLEAN));
        }

        $posterFile = $request->files->get('poster');
        if ($posterFile) {
            $filename = $this->handleFileUpload($posterFile, $slugger, 'posters');
            $tournament->setPosterPath('/uploads/posters/' . $filename);
        }

        $rulesFile = $request->files->get('rulesFile');
        if ($rulesFile) {
            $filename = $this->handleFileUpload($rulesFile, $slugger, 'rules');
            $tournament->setRulesPath('/uploads/rules/' . $filename);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id'         => $tournament->getId(),
            'name'       => $tournament->getName(),
            'uuid'       => $tournament->getUuidAccessToken(),
            'status'     => $tournament->getStatus(),
            'statusDescription' => $tournament->getStatusDescription(),
            'type'       => $tournament->getType(),
            'private'    => $tournament->isPrivate(),
            'hasThirdPlace' => $tournament->isHasThirdPlace(),
            'startDate'  => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate'    => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'ruleKings'  => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames'  => $tournament->getRuleGames(),
            'tablesCount'=> $tournament->getTablesCount(),
            'location'   => $tournament->getLocation(),
            'provinceId' => $tournament->getProvince() ? $tournament->getProvince()->getId() : null,
            'provinceName' => $tournament->getProvince() ? $tournament->getProvince()->getName() : null,
            'townId' => $tournament->getTown() ? $tournament->getTown()->getId() : null,
            'townName' => $tournament->getTown() ? $tournament->getTown()->getName() : null,
            'posterPath' => $tournament->getPosterPath(),
            'rulesPath'  => $tournament->getRulesPath(),
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}', name: 'app_tournament_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $uuid, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);

        if (!$tournament) {
            return new JsonResponse(['error' => 'Torneo no encontrado'], 404);
        }

        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tournament);

        $entityManager->remove($tournament);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function handleFileUpload($file, SluggerInterface $slugger, string $subdir): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move(
            $this->getParameter('kernel.project_dir').'/public/uploads/' . $subdir,
            $newFilename
        );

        return $newFilename;
    }

    #[Route('/tournament/{uuid}', name: 'app_tournament_show', methods: ['GET'])]
    #[Route('/api/tournament/{uuid}', name: 'app_tournament_show_api', methods: ['GET'])]
    public function show(string $uuid, TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);

        if (!$tournament && is_numeric($uuid)) {
            $tournament = $tournamentRepository->find($uuid);
        }

        if (!$tournament) {
            return new JsonResponse(['error' => 'Torneo no encontrado'], 404);
        }

        $this->denyAccessUnlessGranted('TOURNAMENT_VIEW', $tournament);

        $user = $this->getUser();
        $isManager = false;
        if ($user) {
            $isManager = ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $user->getId()) 
                         || $this->isGranted('ROLE_ADMIN') 
                         || $this->isGranted('ROLE_SUPER_ADMIN');
            if (!$isManager) {
                foreach ($tournament->getManagers() as $manager) {
                    if ($manager->getId() === $user->getId()) {
                        $isManager = true;
                        break;
                    }
                }
            }
        }

        // Basic data return for example
        return new JsonResponse([
            'id' => $tournament->getId(),
            'name' => $tournament->getName(),
            'isManager' => $isManager,
            'status' => $tournament->getStatus(),
            'statusDescription' => $tournament->getStatusDescription(),
            'type' => $tournament->getType(),
            'startDate' => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate' => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'ruleKings' => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames' => $tournament->getRuleGames(),
            'tablesCount' => $tournament->getTablesCount(),
            'location' => $tournament->getLocation(),
            'provinceId' => $tournament->getProvince() ? $tournament->getProvince()->getId() : null,
            'provinceName' => $tournament->getProvince() ? $tournament->getProvince()->getName() : null,
            'townId' => $tournament->getTown() ? $tournament->getTown()->getId() : null,
            'townName' => $tournament->getTown() ? $tournament->getTown()->getName() : null,
            'posterPath' => $tournament->getPosterPath(),
            'rulesPath' => $tournament->getRulesPath(),
            'private' => $tournament->isPrivate(),
            'hasThirdPlace' => $tournament->isHasThirdPlace(),
            'uuid' => $tournament->getUuidAccessToken(),
            'teamsCount' => count($tournament->getTournamentTeams()),
            'tournamentTeams' => array_map(function($tt) {
                return [
                    'id' => $tt->getId(),
                    'groupName' => $tt->getGroupName(),
                    'isConfirmed' => $tt->isConfirmed(),
                    'team' => [
                        'id' => $tt->getTeam()->getId(),
                        'name' => $tt->getTeam()->getName(),
                    ]
                ];
            }, $tournament->getTournamentTeams()->toArray()),
            'matches' => array_map(function($match) {
                return [
                    'id' => $match->getId(),
                    'stage' => $match->getStage(),
                    'team1' => $match->getTeam1() ? $match->getTeam1()->getName() : 'TBD',
                    'team2' => $match->getTeam2() ? $match->getTeam2()->getName() : 'TBD',
                    'score1' => $match->getScoreTeam1(),
                    'score2' => $match->getScoreTeam2(),
                    'bracketRound' => $match->getBracketRound(),
                    'bracketPosition' => $match->getBracketPosition(),
                    'games' => array_map(function($game) {
                        return [
                            'points1' => $game->getPointsTeam1(),
                            'points2' => $game->getPointsTeam2(),
                        ];
                    }, $match->getGames()->toArray())
                ];
            }, $tournament->getMatches()->toArray())
        ]);
    }

    #[Route('/api/admin/tournament/{uuid}/enroll-team', name: 'app_tournament_enroll_team', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enrollTeam(string $uuid, Request $request, TournamentRepository $tournamentRepository, TeamRepository $teamRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $teamName = $data['name'] ?? null;
        $isConfirmed = (bool) ($data['isConfirmed'] ?? false);

        if (!$teamName) {
            return new JsonResponse(['error' => 'El nombre de la pareja es obligatorio'], 400);
        }

        // Create new Team
        $team = new Team();
        $team->setName(substr($teamName, 0, 255));
        $entityManager->persist($team);

        $tournamentTeam = new TournamentTeam();
        $tournamentTeam->setTournament($tournament);
        $tournamentTeam->setTeam($team);
        $tournamentTeam->setIsConfirmed($isConfirmed);

        $entityManager->persist($tournamentTeam);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'team' => [
                'id' => $tournamentTeam->getId(),
                'groupName' => $tournamentTeam->getGroupName(),
                'isConfirmed' => $tournamentTeam->isConfirmed(),
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                ]
            ]
        ]);
    }

    #[Route('/api/admin/tournament/team/{id}/toggle-confirm', name: 'app_tournament_team_toggle_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleConfirmTeam(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $tt = $entityManager->getRepository(TournamentTeam::class)->find($id);
        if (!$tt) return new JsonResponse(['error' => 'Registro no encontrado'], 404);
        
        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tt->getTournament());
        
        $tt->setIsConfirmed(!$tt->isConfirmed());
        $entityManager->flush();
        
        return new JsonResponse(['success' => true, 'isConfirmed' => $tt->isConfirmed()]);
    }

    #[Route('/api/admin/tournament/team/{id}', name: 'app_tournament_unenroll_team', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function unenrollTeam(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $tt = $entityManager->getRepository(TournamentTeam::class)->find($id);
        if (!$tt) return new JsonResponse(['error' => 'Registro no encontrado'], 404);
        
        $tournament = $tt->getTournament();
        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tournament);
        
        if ($tournament->getStatus() !== 'draft' && $tournament->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'No se puede desapuntar una pareja si el torneo no está en borrador o pendiente'], 400);
        }

        $entityManager->remove($tt);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/{uuid}/generate-groups', name: 'app_tournament_generate_groups', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateGroups(string $uuid, Request $request, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $groupsCount = (int) $request->request->get('groupsCount', 2);
        $teams = $tournament->getTournamentTeams()->toArray();
        shuffle($teams);

        $groupLetters = range('A', 'Z');
        foreach ($teams as $index => $tt) {
            $groupIndex = $index % $groupsCount;
            $tt->setGroupName('Grupo ' . $groupLetters[$groupIndex]);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/tournament/{uuid}/generate-matches', name: 'app_tournament_generate_matches', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateMatches(string $uuid, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
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
            // SHUFFLE TEAMS FOR INITIAL DRAW
            shuffle($teams);
            
            $teamsCount = count($teams);
            $roundsNeeded = (int)ceil(log($teamsCount, 2));
            $initialPower = pow(2, $roundsNeeded);
            
            // Stage names mapping
            $stageNames = [
                1 => 'Final',
                2 => 'Semifinales',
                4 => 'Cuartos de Final',
                8 => 'Octavos de Final',
                16 => 'Dieciseisavos de Final',
                32 => 'Treintaidosavos de Final'
            ];

            // Create ALL matches for the bracket structure
            // We create them from Final (1) to Initial ($roundsNeeded) so Byes can advance
            for ($r = 1; $r <= $roundsNeeded; $r++) {
                $matchesInRound = pow(2, $r - 1);
                $stageLabel = $stageNames[$matchesInRound] ?? 'Ronda ' . ($roundsNeeded - $r + 1);
                
                for ($p = 0; $p < $matchesInRound; $p++) {
                    $match = new MusMatch();
                    $match->setTournament($tournament);
                    $match->setStage($stageLabel);
                    $match->setBracketRound($r);
                    $match->setBracketPosition($p);
                    
                    // Assign teams only for the first round ($r === $roundsNeeded)
                    if ($r === $roundsNeeded) {
                        $teamIndex1 = $p * 2;
                        $teamIndex2 = $p * 2 + 1;
                        
                        if (isset($teams[$teamIndex1])) {
                            $match->setTeam1($teams[$teamIndex1]->getTeam());
                        }
                        if (isset($teams[$teamIndex2])) {
                            $match->setTeam2($teams[$teamIndex2]->getTeam());
                        }

                        // Handle Bye (if team2 is null, team1 wins immediately)
                        if ($match->getTeam1() && !$match->getTeam2()) {
                            $match->setWinner($match->getTeam1());
                            $match->setScoreTeam1(1); 
                            $match->setScoreTeam2(0);
                            
                            // Advance Bye winner if round > 1
                            if ($r > 1) {
                                $this->advanceWinnerInBracket($match, $entityManager);
                            }
                        }
                    }

                    $entityManager->persist($match);
                }
            }

            // Generate 3rd place match if enabled and there are enough rounds (at least semifinals)
            if ($roundsNeeded >= 2 && $tournament->isHasThirdPlace()) {
                $thirdPlaceMatch = new MusMatch();
                $thirdPlaceMatch->setTournament($tournament);
                $thirdPlaceMatch->setStage('3º y 4º puesto');
                // We place it in Round 1 (same as Final) but position 1 (Final is 0)
                $thirdPlaceMatch->setBracketRound(1);
                $thirdPlaceMatch->setBracketPosition(1);
                $entityManager->persist($thirdPlaceMatch);
            }
        } else {
            // ROUND ROBIN / GROUPS LOGIC
            $groups = [];
            foreach ($teams as $tt) {
                $gn = $tt->getGroupName() ?: 'Sin Grupo';
                $groups[$gn][] = $tt->getTeam();
            }

            foreach ($groups as $groupName => $groupTeams) {
                $count = count($groupTeams);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $match = new MusMatch();
                        $match->setTournament($tournament);
                        $match->setTeam1($groupTeams[$i]);
                        $match->setTeam2($groupTeams[$j]);
                        $match->setStage($groupName);
                        $entityManager->persist($match);
                    }
                }
            }
        }

        $tournament->setStatus('active');
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/api/admin/match/{id}', name: 'app_match_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateMatch(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $match = $entityManager->getRepository(MusMatch::class)->find($id);
        if (!$match) return new JsonResponse(['error' => 'Partido no encontrado'], 404);

        $tournament = $match->getTournament();
        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tournament);

        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $gamesData = $data['games'] ?? [];

        // Clear existing games
        foreach ($match->getGames() as $game) {
            $entityManager->remove($game);
        }
        $match->getGames()->clear();

        $gamesWon1 = 0;
        $gamesWon2 = 0;
        $limit = $tournament->getRulePoints();

        if (empty($gamesData) && (isset($data['score1']) || isset($data['score2']))) {
            $gamesWon1 = (int)($data['score1'] ?? 0);
            $gamesWon2 = (int)($data['score2'] ?? 0);
        } else {
            foreach ($gamesData as $index => $g) {
                $points1 = (int) ($g['points1'] ?? 0);
                $points2 = (int) ($g['points2'] ?? 0);
                
                if ($points1 === 0 && $points2 === 0) continue;

                $game = new MusMatchGame();
                $game->setMusMatch($match);
                $game->setGameNumber($index + 1);
                $game->setPointsTeam1($points1);
                $game->setPointsTeam2($points2);
                
                if ($points1 >= $limit) $gamesWon1++;
                elseif ($points2 >= $limit) $gamesWon2++;
                
                $entityManager->persist($game);
                $match->addGame($game);
            }
        }

        $match->setScoreTeam1($gamesWon1);
        $match->setScoreTeam2($gamesWon2);

        // Winner logic: First to reach ruleGames (e.g. 3) wins the match
        $toWin = $tournament->getRuleGames();
        if ($gamesWon1 >= $toWin) $match->setWinner($match->getTeam1());
        elseif ($gamesWon2 >= $toWin) $match->setWinner($match->getTeam2());
        else $match->setWinner(null);

        $this->recalculateTournamentStats($tournament, $entityManager);
        
        // AUTOMATIC ADVANCEMENT FOR ELIMINATORY
        $this->advanceWinnerInBracket($match, $entityManager);

        $entityManager->flush();

        // CHECK IF ALL MATCHES ARE FINISHED -> AUTO-FINISH TOURNAMENT
        $allFinished = true;
        foreach ($tournament->getMatches() as $m) {
            if (!$m->getWinner()) {
                $allFinished = false;
                break;
            }
        }
        if ($allFinished && count($tournament->getMatches()) > 0 && $tournament->getStatus() === 'active') {
            $tournament->setStatus('finished');
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function recalculateTournamentStats(Tournament $tournament, EntityManagerInterface $entityManager): void
    {
        // Reset all stats for tournament teams
        foreach ($tournament->getTournamentTeams() as $tt) {
            $tt->setPoints(0);
            $tt->setGamesWon(0);
            $tt->setGamesLost(0);
            $tt->setMatchesPlayed(0);
        }

        // Aggregate stats from all matches
        foreach ($tournament->getMatches() as $match) {
            $team1 = $match->getTeam1();
            $team2 = $match->getTeam2();
            $s1 = $match->getScoreTeam1();
            $s2 = $match->getScoreTeam2();
            $winner = $match->getWinner();

            // Skip if no score yet (unless winner set manually, but we use scores)
            if ($s1 === 0 && $s2 === 0 && !$winner) continue;

            if (!$team1 || !$team2) continue;

            $tt1 = null; $tt2 = null;
            foreach ($tournament->getTournamentTeams() as $tt) {
                if ($team1 && $tt->getTeam()->getId() === $team1->getId()) $tt1 = $tt;
                if ($team2 && $tt->getTeam()->getId() === $team2->getId()) $tt2 = $tt;
            }

            if ($tt1) {
                $tt1->setMatchesPlayed($tt1->getMatchesPlayed() + 1);
                $tt1->setGamesWon($tt1->getGamesWon() + $s1);
                $tt1->setGamesLost($tt1->getGamesLost() + $s2);
                if ($winner && $winner->getId() === $team1->getId()) {
                    $tt1->setPoints($tt1->getPoints() + 3);
                }
            }
            if ($tt2) {
                $tt2->setMatchesPlayed($tt2->getMatchesPlayed() + 1);
                $tt2->setGamesWon($tt2->getGamesWon() + $s2);
                $tt2->setGamesLost($tt2->getGamesLost() + $s1);
                if ($winner && $winner->getId() === $team2->getId()) {
                    $tt2->setPoints($tt2->getPoints() + 3);
                }
            }
        }
    }

    #[Route('/api/tournament/{uuid}/classification', name: 'app_tournament_classification', methods: ['GET'])]
    public function getClassification(string $uuid, TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $classification = [];
        foreach ($tournament->getTournamentTeams() as $tt) {
            $gn = $tt->getGroupName() ?: 'General';
            $classification[$gn][] = [
                'teamName' => $tt->getTeam()->getName(),
                'points' => $tt->getPoints(),
                'won' => $tt->getGamesWon(),
                'lost' => $tt->getGamesLost(),
                'played' => $tt->getMatchesPlayed(),
            ];
        }

        // Sort each group by points
        foreach ($classification as $gn => &$teams) {
            usort($teams, fn($a, $b) => $b['points'] <=> $a['points']);
        }

        return new JsonResponse($classification);
    }

    #[Route('/public/tournaments', name: 'app_tournament_list_public', methods: ['GET'])]
    #[Route('/api/public/tournaments', name: 'app_tournament_list_public_api', methods: ['GET'])]
    public function listPublic(TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournaments = $tournamentRepository->findBy([
            'status' => ['active', 'pending', 'finished'],
            'private' => false
        ], ['id' => 'DESC']);
        $user = $this->getUser();
        $isSuperAdmin = $user && in_array('ROLE_SUPER_ADMIN', $user->getRoles());

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
                'posterPath' => $t->getPosterPath(),
                'rulesPath' => $t->getRulesPath(),
                'location' => $t->getLocation(),
                'provinceId' => $t->getProvince() ? $t->getProvince()->getId() : null,
                'provinceName' => $t->getProvince() ? $t->getProvince()->getName() : null,
                'townId' => $t->getTown() ? $t->getTown()->getId() : null,
                'townName' => $t->getTown() ? $t->getTown()->getName() : null,
                'teamsCount' => count($t->getTournamentTeams()),
                'playerCount' => 24,
                'private' => $t->isPrivate(),
                'hasThirdPlace' => $t->isHasThirdPlace(),
            ];

            if ($isSuperAdmin) {
                $owner = $t->getCreatedBy();
                $data['owner'] = [
                    'id' => $owner ? $owner->getId() : null,
                    'name' => $owner ? ($owner->getPlayer() ? $owner->getPlayer()->getName() : $owner->getEmail()) : 'System'
                ];
            }

            return $data;
        }, $tournaments));
    }

    private function advanceWinnerInBracket(MusMatch $match, EntityManagerInterface $entityManager): void
    {
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
}
