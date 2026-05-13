<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/me', name: 'app_user_me', methods: ['GET'])]
    #[Route('/api/me', name: 'app_user_me_api', methods: ['GET'])]
    public function me(
        \Symfony\Component\HttpFoundation\Request $request,
        \App\Repository\UserRepository $userRepository,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // If not authenticated via standard Basic Auth, try manual check from custom headers
        if (!$user) {
            $email = $request->headers->get('X-Email');
            $password = $request->headers->get('X-Password');

            if ($email && $password) {
                $user = $userRepository->findOneBy(['email' => $email]);
                if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                    return new JsonResponse(['error' => 'Invalid credentials'], 401);
                }
            } else {
                return new JsonResponse(['error' => 'Unauthorized'], 401);
            }
        }

        $player = $user->getPlayer();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'nickname' => $user->getNickname(),
            'phone' => $user->getPhone(),
            'roles' => $user->getRoles(),
            'player' => $player ? [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'email' => $player->getEmail(),
            ] : null
        ]);
    }

    #[Route('/api/me/update', name: 'app_user_update_api', methods: ['POST', 'PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(
        \Symfony\Component\HttpFoundation\Request $request,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['nickname'])) {
            $user->setNickname($data['nickname']);
        }
        if (isset($data['phone'])) {
            $user->setPhone($data['phone']);
        }
        if (!empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Profile updated successfully']);
    }
}
