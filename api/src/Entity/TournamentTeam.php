<?php

namespace App\Entity;

use App\Repository\TournamentTeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentTeamRepository::class)]
#[ORM\Table(name: 'tournament_team')]
class TournamentTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tournamentTeams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $groupName = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $gamesWon = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $gamesLost = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $matchesPlayed = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;
        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(?string $groupName): static
    {
        $this->groupName = $groupName;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function getGamesWon(): int
    {
        return $this->gamesWon;
    }

    public function setGamesWon(int $gamesWon): static
    {
        $this->gamesWon = $gamesWon;
        return $this;
    }

    public function getGamesLost(): int
    {
        return $this->gamesLost;
    }

    public function setGamesLost(int $gamesLost): static
    {
        $this->gamesLost = $gamesLost;
        return $this;
    }

    public function getMatchesPlayed(): int
    {
        return $this->matchesPlayed;
    }

    public function setMatchesPlayed(int $matchesPlayed): static
    {
        $this->matchesPlayed = $matchesPlayed;
        return $this;
    }
}
