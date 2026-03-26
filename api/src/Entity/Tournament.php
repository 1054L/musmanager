<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'draft'; // draft, active, finished

    #[ORM\Column(length: 50)]
    private ?string $type = 'eliminatory'; // eliminatory, groups

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $uuidAccessToken = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterPath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $statusDescription = null;

    #[ORM\Column(type: 'integer', options: ['default' => 8])]
    private int $ruleKings = 8;

    #[ORM\Column(type: 'integer', options: ['default' => 40])]
    private int $rulePoints = 40;

    #[ORM\Column(type: 'integer', options: ['default' => 3])]
    private int $ruleGames = 3;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tablesCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'tournament_managers')]
    private Collection $managers;

    /**
     * @var Collection<int, TournamentTeam>
     */
    #[ORM\OneToMany(targetEntity: TournamentTeam::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $tournamentTeams;

    /**
     * @var Collection<int, MusMatch>
     */
    #[ORM\OneToMany(targetEntity: MusMatch::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $matches;

    public function __construct()
    {
        $this->matches = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->tournamentTeams = new ArrayCollection();
        $this->uuidAccessToken = Uuid::v4()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUuidAccessToken(): ?string
    {
        return $this->uuidAccessToken;
    }

    public function setUuidAccessToken(string $uuidAccessToken): static
    {
        $this->uuidAccessToken = $uuidAccessToken;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): static
    {
        $this->posterPath = $posterPath;
        return $this;
    }

    public function getStatusDescription(): ?string
    {
        return $this->statusDescription;
    }

    public function setStatusDescription(?string $statusDescription): static
    {
        $this->statusDescription = $statusDescription;
        return $this;
    }

    public function getRuleKings(): int
    {
        return $this->ruleKings;
    }

    public function setRuleKings(int $ruleKings): static
    {
        $this->ruleKings = $ruleKings;
        return $this;
    }

    public function getRulePoints(): int
    {
        return $this->rulePoints;
    }

    public function setRulePoints(int $rulePoints): static
    {
        $this->rulePoints = $rulePoints;
        return $this;
    }

    public function getRuleGames(): int
    {
        return $this->ruleGames;
    }

    public function setRuleGames(int $ruleGames): static
    {
        $this->ruleGames = $ruleGames;
        return $this;
    }

    public function getTablesCount(): ?int
    {
        return $this->tablesCount;
    }

    public function setTablesCount(?int $tablesCount): static
    {
        $this->tablesCount = $tablesCount;
        return $this;
    }

    /**
     * @return Collection<int, MusMatch>
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(MusMatch $match): static
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setTournament($this);
        }
        return $this;
    }

    public function removeMatch(MusMatch $match): static
    {
        if ($this->matches->removeElement($match)) {
            if ($match->getTournament() === $this) {
                $match->setTournament(null);
            }
        }
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(User $manager): static
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
        }
        return $this;
    }

    public function removeManager(User $manager): static
    {
        $this->managers->removeElement($manager);
        return $this;
    }

    /**
     * @return Collection<int, TournamentTeam>
     */
    public function getTournamentTeams(): Collection
    {
        return $this->tournamentTeams;
    }

    public function addTournamentTeam(TournamentTeam $tournamentTeam): static
    {
        if (!$this->tournamentTeams->contains($tournamentTeam)) {
            $this->tournamentTeams->add($tournamentTeam);
            $tournamentTeam->setTournament($this);
        }
        return $this;
    }

    public function removeTournamentTeam(TournamentTeam $tournamentTeam): static
    {
        if ($this->tournamentTeams->removeElement($tournamentTeam)) {
            // set the owning side to null (unless already changed)
            if ($tournamentTeam->getTournament() === $this) {
                $tournamentTeam->setTournament(null);
            }
        }
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }
}
