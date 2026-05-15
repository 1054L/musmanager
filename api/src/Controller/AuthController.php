<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        \Symfony\Contracts\Translation\TranslatorInterface $translator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $locale = $data['locale'] ?? 'es'; // Default to Spanish if not provided

        if (!$email) {
            return new JsonResponse(['message' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
            $entityManager->flush();

            $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'https://musmanager.com';
            $resetLink = $frontendUrl . '/reset-password?token=' . $token;

            $subject = $translator->trans('emails.reset_password.subject', [], 'messages', $locale);

            $emailMessage = (new Email())
                ->from($_ENV['MAILER_FROM'] ?? 'info@musmanager.com')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($this->renderView('emails/reset_password.html.twig', [
                    'resetLink' => $resetLink,
                    'user' => $user,
                    'locale' => $locale
                ]));

            try {
                $mailer->send($emailMessage);
            } catch (\Exception $e) {
                if ($_ENV['APP_ENV'] === 'dev') {
                    return new JsonResponse([
                        'message' => 'LOCAL DEV: No se pudo enviar el email. Token: ' . $token,
                        'token' => $token
                    ], Response::HTTP_OK);
                }
            }
        }

        $successMsg = $translator->trans('Si ese correo existe, recibirás un enlace para restablecer tu contraseña', [], 'messages', $locale);

        return new JsonResponse([
            'message' => $successMsg
        ], Response::HTTP_OK);
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return new JsonResponse(['message' => 'Token and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Token inválido'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['message' => 'El token ha expirado'], Response::HTTP_BAD_REQUEST);
        }

        // Reset password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Clear token
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        
        $entityManager->flush();

        return new JsonResponse(['message' => 'Contraseña actualizada con éxito'], Response::HTTP_OK);
    }
}
