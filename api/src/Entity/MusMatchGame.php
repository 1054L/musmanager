<?php

namespace App\Entity;

use App\Repository\MusMatchGameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusMatchGameRepository::class)]
#[ORM\Table(name: 'mus_match_game')]
class MusMatchGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MusMatch $musMatch = null;

    #[ORM\Column]
    private int $gameNumber;

    #[ORM\Column]
    private int $pointsTeam1 = 0;

    #[ORM\Column]
    private int $pointsTeam2 = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMusMatch(): ?MusMatch
    {
        return $this->musMatch;
    }

    public function setMusMatch(?MusMatch $musMatch): static
    {
        $this->musMatch = $musMatch;
        return $this;
    }

    public function getGameNumber(): int
    {
        return $this->gameNumber;
    }

    public function setGameNumber(int $gameNumber): static
    {
        $this->gameNumber = $gameNumber;
        return $this;
    }

    public function getPointsTeam1(): int
    {
        return $this->pointsTeam1;
    }

    public function setPointsTeam1(int $pointsTeam1): static
    {
        $this->pointsTeam1 = $pointsTeam1;
        return $this;
    }

    public function getPointsTeam2(): int
    {
        return $this->pointsTeam2;
    }

    public function setPointsTeam2(int $pointsTeam2): static
    {
        $this->pointsTeam2 = $pointsTeam2;
        return $this;
    }
}
