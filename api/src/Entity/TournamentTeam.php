<?php

namespace App\Entity;

use App\Repository\TournamentTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isConfirmed = false;

    /**
     * @var Collection<int, MusMatch>
     */
    #[ORM\OneToMany(targetEntity: MusMatch::class, mappedBy: 'team1')]
    private Collection $matchesAsTeam1;

    /**
     * @var Collection<int, MusMatch>
     */
    #[ORM\OneToMany(targetEntity: MusMatch::class, mappedBy: 'team2')]
    private Collection $matchesAsTeam2;

    public function __construct()
    {
        $this->matchesAsTeam1 = new ArrayCollection();
        $this->matchesAsTeam2 = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;
        return $this;
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

    /**
     * @return Collection<int, MusMatch>
     */
    public function getMatchesAsTeam1(): Collection
    {
        return $this->matchesAsTeam1;
    }

    /**
     * @return Collection<int, MusMatch>
     */
    public function getMatchesAsTeam2(): Collection
    {
        return $this->matchesAsTeam2;
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
