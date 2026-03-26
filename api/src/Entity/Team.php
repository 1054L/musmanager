<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class, inversedBy: 'teams')]
    private Collection $players;

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
        $this->players = new ArrayCollection();
        $this->matchesAsTeam1 = new ArrayCollection();
        $this->matchesAsTeam2 = new ArrayCollection();
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

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }
        return $this;
    }

    public function removePlayer(Player $player): static
    {
        $this->players->removeElement($player);
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
}
