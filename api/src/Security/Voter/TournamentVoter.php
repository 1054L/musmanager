<?php

namespace App\Security\Voter;

use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TournamentVoter extends Voter
{
    public const VIEW = 'TOURNAMENT_VIEW';
    public const EDIT = 'TOURNAMENT_EDIT';
    public const DELETE = 'TOURNAMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Tournament;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        /** @var Tournament $tournament */
        $tournament = $subject;

        if ($attribute === self::VIEW) {
            return $this->canView($tournament, $user instanceof User ? $user : null);
        }

        if (!$user instanceof User) {
            return false;
        }

        // Superadmin and Admin have full access
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return match($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($tournament, $user),
            default => false,
        };
    }

    private function canView(Tournament $tournament, ?User $user): bool
    {
        return true;
    }

    private function canEdit(Tournament $tournament, User $user): bool
    {
        // Creator or Manager (Admin level)
        $creator = $tournament->getCreatedBy();
        if ($creator && $creator->getId() === $user->getId()) {
            return true;
        }

        foreach ($tournament->getManagers() as $manager) {
            if ($manager->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
