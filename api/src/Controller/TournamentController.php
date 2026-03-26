<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\MusMatch;
use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class TournamentController extends AbstractController
{
    #[Route('/user/tournaments', name: 'app_tournament_list_managed', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listManaged(TournamentRepository $tournamentRepository): JsonResponse
    {
        $user = $this->getUser();
        $tournaments = $tournamentRepository->findManagedByUser($user);

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
                'posterPath' => $t->getPosterPath(),
                'teamsCount' => count($t->getTournamentTeams()),
            ];
        }, $tournaments));
    }

    #[Route('/admin/tournament', name: 'app_tournament_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): JsonResponse
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
        
        $posterFile = $request->files->get('poster');
        if ($posterFile) {
            $filename = $this->handleFileUpload($posterFile, $slugger);
            $tournament->setPosterPath('/uploads/posters/' . $filename);
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
            'startDate'  => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate'    => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'ruleKings'  => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames'  => $tournament->getRuleGames(),
            'tablesCount'=> $tournament->getTablesCount(),
            'location'   => $tournament->getLocation(),
            'posterPath' => $tournament->getPosterPath(),
        ]);
    }

    #[Route('/admin/tournament/{uuid}', name: 'app_tournament_update', methods: ['POST'])]
    #[IsGranted('TOURNAMENT_EDIT', subject: 'tournament')]
    public function update(string $uuid, Request $request, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger): JsonResponse
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

        $posterFile = $request->files->get('poster');
        if ($posterFile) {
            $filename = $this->handleFileUpload($posterFile, $slugger);
            $tournament->setPosterPath('/uploads/posters/' . $filename);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id'         => $tournament->getId(),
            'name'       => $tournament->getName(),
            'uuid'       => $tournament->getUuidAccessToken(),
            'status'     => $tournament->getStatus(),
            'statusDescription' => $tournament->getStatusDescription(),
            'type'       => $tournament->getType(),
            'startDate'  => $tournament->getStartDate() ? $tournament->getStartDate()->format(\DateTimeInterface::ATOM) : null,
            'endDate'    => $tournament->getEndDate() ? $tournament->getEndDate()->format(\DateTimeInterface::ATOM) : null,
            'ruleKings'  => $tournament->getRuleKings(),
            'rulePoints' => $tournament->getRulePoints(),
            'ruleGames'  => $tournament->getRuleGames(),
            'tablesCount'=> $tournament->getTablesCount(),
            'location'   => $tournament->getLocation(),
            'posterPath' => $tournament->getPosterPath(),
        ]);
    }

    private function handleFileUpload($file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move(
            $this->getParameter('kernel.project_dir').'/public/uploads/posters',
            $newFilename
        );

        return $newFilename;
    }

    #[Route('/tournament/{uuid}', name: 'app_tournament_show', methods: ['GET'])]
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
            $isManager = ($tournament->getCreatedBy() && $tournament->getCreatedBy()->getId() === $user->getId());
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
            'posterPath' => $tournament->getPosterPath(),
            'uuid' => $tournament->getUuidAccessToken(),
            'teamsCount' => count($tournament->getTournamentTeams()),
            'tournamentTeams' => array_map(function($tt) {
                return [
                    'id' => $tt->getId(),
                    'groupName' => $tt->getGroupName(),
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
                ];
            }, $tournament->getMatches()->toArray())
        ]);
    }

    #[Route('/admin/tournament/{uuid}/enroll-team', name: 'app_tournament_enroll_team', methods: ['POST'])]
    #[IsGranted('TOURNAMENT_EDIT', subject: 'tournament')]
    public function enrollTeam(string $uuid, Request $request, TournamentRepository $tournamentRepository, TeamRepository $teamRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $teamId = $request->request->get('teamId');
        $team = $teamRepository->find($teamId);
        if (!$team) return new JsonResponse(['error' => 'Equipo no encontrado'], 404);

        // Check if already enrolled
        foreach ($tournament->getTournamentTeams() as $tt) {
            if ($tt->getTeam()->getId() === $team->getId()) {
                return new JsonResponse(['error' => 'El equipo ya está inscrito'], 400);
            }
        }

        $tournamentTeam = new TournamentTeam();
        $tournamentTeam->setTournament($tournament);
        $tournamentTeam->setTeam($team);

        $entityManager->persist($tournamentTeam);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/tournament/{uuid}/generate-groups', name: 'app_tournament_generate_groups', methods: ['POST'])]
    #[IsGranted('TOURNAMENT_EDIT', subject: 'tournament')]
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

    #[Route('/admin/tournament/{uuid}/generate-matches', name: 'app_tournament_generate_matches', methods: ['POST'])]
    #[IsGranted('TOURNAMENT_EDIT', subject: 'tournament')]
    public function generateMatches(string $uuid, TournamentRepository $tournamentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $tournamentRepository->findOneBy(['uuidAccessToken' => $uuid]);
        if (!$tournament) return new JsonResponse(['error' => 'Torneo no encontrado'], 404);

        $groups = [];
        foreach ($tournament->getTournamentTeams() as $tt) {
            $gn = $tt->getGroupName() ?: 'Sin Grupo';
            $groups[$gn][] = $tt->getTeam();
        }

        foreach ($groups as $groupName => $teams) {
            $count = count($teams);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $match = new MusMatch();
                    $match->setTournament($tournament);
                    $match->setTeam1($teams[$i]);
                    $match->setTeam2($teams[$j]);
                    $match->setStage($groupName);
                    $entityManager->persist($match);
                }
            }
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/match/{id}', name: 'app_match_update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateMatch(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $match = $entityManager->getRepository(MusMatch::class)->find($id);
        if (!$match) return new JsonResponse(['error' => 'Partido no encontrado'], 404);

        $tournament = $match->getTournament();
        $this->denyAccessUnlessGranted('TOURNAMENT_EDIT', $tournament);

        $score1 = (int) $request->request->get('score1');
        $score2 = (int) $request->request->get('score2');

        $match->setScoreTeam1($score1);
        $match->setScoreTeam2($score2);

        // Update winner if score reaches limit
        $limit = $tournament->getRulePoints();
        if ($score1 >= $limit) $match->setWinner($match->getTeam1());
        elseif ($score2 >= $limit) $match->setWinner($match->getTeam2());
        else $match->setWinner(null);

        // Recalculate stats for TournamentTeam
        // Note: For a real app, this should be a service, but here I'll do it simple
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/tournament/{uuid}/classification', name: 'app_tournament_classification', methods: ['GET'])]
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
    public function listPublic(TournamentRepository $tournamentRepository): JsonResponse
    {
        $tournaments = $tournamentRepository->findBy(['status' => ['active', 'pending']], ['id' => 'DESC']);

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
                'posterPath' => $t->getPosterPath(),
                'location' => $t->getLocation(),
                'teamsCount' => count($t->getTournamentTeams()),
                'playerCount' => 24, // Keep for legacy if needed, but we'll use teamsCount
            ];
        }, $tournaments));
    }
}
