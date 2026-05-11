<?php

namespace App\Entity;

use App\Repository\MusMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusMatchRepository::class)]
#[ORM\Table(name: 'mus_match')]
class MusMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(inversedBy: 'matchesAsTeam1')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team1 = null;

    #[ORM\ManyToOne(inversedBy: 'matchesAsTeam2')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team2 = null;

    #[ORM\Column]
    private ?int $scoreTeam1 = 0;

    #[ORM\Column]
    private ?int $scoreTeam2 = 0;

    #[ORM\ManyToOne]
    private ?Team $winner = null;

    #[ORM\Column(length: 100)]
    private ?string $stage = null;

    #[ORM\Column(nullable: true)]
    private ?int $bracketRound = null;

    #[ORM\Column(nullable: true)]
    private ?int $bracketPosition = null;

    /**
     * @var Collection<int, MusMatchGame>
     */
    #[ORM\OneToMany(targetEntity: MusMatchGame::class, mappedBy: 'musMatch', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $games;

    public function __construct()
    {
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, MusMatchGame>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(MusMatchGame $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setMusMatch($this);
        }
        return $this;
    }

    public function removeGame(MusMatchGame $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getMusMatch() === $this) {
                $game->setMusMatch(null);
            }
        }
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

    public function getTeam1(): ?Team
    {
        return $this->team1;
    }

    public function setTeam1(?Team $team1): static
    {
        $this->team1 = $team1;
        return $this;
    }

    public function getTeam2(): ?Team
    {
        return $this->team2;
    }

    public function setTeam2(?Team $team2): static
    {
        $this->team2 = $team2;
        return $this;
    }

    public function getScoreTeam1(): ?int
    {
        return $this->scoreTeam1;
    }

    public function setScoreTeam1(int $scoreTeam1): static
    {
        $this->scoreTeam1 = $scoreTeam1;
        return $this;
    }

    public function getScoreTeam2(): ?int
    {
        return $this->scoreTeam2;
    }

    public function setScoreTeam2(int $scoreTeam2): static
    {
        $this->scoreTeam2 = $scoreTeam2;
        return $this;
    }

    public function getWinner(): ?Team
    {
        return $this->winner;
    }

    public function setWinner(?Team $winner): static
    {
        $this->winner = $winner;
        return $this;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(string $stage): static
    {
        $this->stage = $stage;
        return $this;
    }

    public function getBracketRound(): ?int
    {
        return $this->bracketRound;
    }

    public function setBracketRound(?int $bracketRound): static
    {
        $this->bracketRound = $bracketRound;
        return $this;
    }

    public function getBracketPosition(): ?int
    {
        return $this->bracketPosition;
    }

    public function setBracketPosition(?int $bracketPosition): static
    {
        $this->bracketPosition = $bracketPosition;
        return $this;
    }
}
