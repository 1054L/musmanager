<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    #[Route('/api/register', name: 'app_register_api', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        if (!isset($data['ageVerified']) || $data['ageVerified'] !== true) {
            return new JsonResponse(['error' => 'You must confirm you are 18 or older to register'], 400);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already registered'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setAgeVerified(true);
        $user->setAgeVerifiedAt(new \DateTimeImmutable());
        $user->setTermsAccepted(true);
        $user->setTermsAcceptedAt(new \DateTimeImmutable());
        
        $role = $data['role'] ?? 'user';
        if ($role === 'admin') {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }
        
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'ageVerified' => $user->isAgeVerified(),
                'ageVerifiedAt' => $user->getAgeVerifiedAt()->format(\DateTimeInterface::ATOM)
            ]
        ], 201);
    }
}
