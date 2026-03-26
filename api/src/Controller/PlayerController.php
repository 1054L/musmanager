<?php

namespace App\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class PlayerController extends AbstractController
{
    #[Route('/players', name: 'app_player_list', methods: ['GET'])]
    public function list(PlayerRepository $playerRepository): JsonResponse
    {
        $players = $playerRepository->findByCreator($this->getUser());
        return new JsonResponse(array_map(function(Player $player) {
            return [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'email' => $player->getEmail(),
            ];
        }, $players));
    }

    #[Route('/admin/player', name: 'app_player_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['name']) || !isset($data['email'])) {
            return new JsonResponse(['error' => 'Nombre y email son obligatorios'], 400);
        }

        $player = new Player();
        $player->setName($data['name']);
        $player->setEmail($data['email']);
        $player->setCreatedBy($this->getUser());
        
        $entityManager->persist($player);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $player->getId(),
            'name' => $player->getName(),
            'email' => $player->getEmail(),
        ], 201);
    }
}
