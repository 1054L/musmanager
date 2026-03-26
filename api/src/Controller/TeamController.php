<?php

namespace App\Controller;

use App\Entity\Team;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class TeamController extends AbstractController
{
    #[Route('/teams', name: 'app_team_list', methods: ['GET'])]
    public function list(TeamRepository $teamRepository): JsonResponse
    {
        $teams = $teamRepository->findByCreator($this->getUser());
        return new JsonResponse(array_map(function(Team $team) {
            return [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'players' => array_map(fn($p) => $p->getName(), $team->getPlayers()->toArray())
            ];
        }, $teams));
    }

    #[Route('/admin/team', name: 'app_team_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, \App\Repository\PlayerRepository $playerRepository, \App\Repository\TournamentRepository $tournamentRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name'])) {
            return new JsonResponse(['error' => 'Nombre de equipo obligatorio'], 400);
        }

        $team = new Team();
        $team->setName($data['name']);
        $team->setCreatedBy($this->getUser());

        if (isset($data['playerIds']) && is_array($data['playerIds'])) {
            foreach ($data['playerIds'] as $pid) {
                $player = $playerRepository->find($pid);
                if ($player) {
                    $team->addPlayer($player);
                }
            }
        }
        
        $entityManager->persist($team);

        // Auto-enroll in tournament if tournamentId is provided
        if (isset($data['tournamentId'])) {
            $tournament = $tournamentRepository->find($data['tournamentId']);
            if ($tournament) {
                // Check if user has permission to manage this tournament
                if ($tournament->getCreatedBy() !== $this->getUser() && !$tournament->getManagers()->contains($this->getUser())) {
                    return new JsonResponse(['error' => 'No tienes permiso para inscribir equipos en este torneo'], 403);
                }

                $tournamentTeam = new \App\Entity\TournamentTeam();
                $tournamentTeam->setTournament($tournament);
                $tournamentTeam->setTeam($team);
                $entityManager->persist($tournamentTeam);
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'id' => $team->getId(),
            'name' => $team->getName()
        ], 201);
    }
}
