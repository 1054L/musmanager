<?php

namespace App\Security\Voter;

use App\Entity\Player;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PlayerVoter extends Voter
{
    public const VIEW = 'PLAYER_VIEW';
    public const EDIT = 'PLAYER_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Player;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Player $player */
        $player = $subject;

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        return match($attribute) {
            self::VIEW => $this->canView($player, $user),
            self::EDIT => $this->canEdit($player, $user),
            default => false,
        };
    }

    private function canView(Player $player, User $user): bool
    {
        // Can view if it's their own profile, or they created the player (admin)
        return $user === $player->getLinkedUser() || $user === $player->getCreatedBy();
    }

    private function canEdit(Player $player, User $user): bool
    {
        // Only creator or superadmin (own view/edit logic)
        return $user === $player->getCreatedBy();
    }
}
