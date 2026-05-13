<?php

namespace App\Command;

use App\Entity\MusMatch;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentTeam;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Creates large test tournaments',
)]
class CreateTestDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        if (!$user) {
            $io->error('No user found in DB. Please register one first.');
            return Command::FAILURE;
        }

        $io->note('Creating 32-team tournament...');
        $this->createTournament($user, 32);
        
        $io->note('Creating 64-team tournament...');
        $this->createTournament($user, 64);

        $io->success('Test tournaments created successfully!');
        return Command::SUCCESS;
    }

    private function createTournament(User $user, int $teamCount): void
    {
        $tournament = new Tournament();
        $tournament->setName("Gran Torneo de $teamCount Parejas");
        $tournament->setStatus('active');
        $tournament->setType('eliminatory');
        $tournament->setCreatedBy($user);
        $tournament->setStartDate(new \DateTimeImmutable());
        $tournament->setEndDate(new \DateTimeImmutable('+2 days'));
        $tournament->setRuleGames(3);
        $tournament->setRulePoints(40);
        $tournament->setRuleKings(8);
        $tournament->setPrivate(false);

        $this->entityManager->persist($tournament);

        $teams = [];
        for ($i = 1; $i <= $teamCount; $i++) {
            $team = new Team();
            $team->setName("Pareja " . str_pad($i, 2, '0', STR_PAD_LEFT) . " (T$teamCount)");
            $this->entityManager->persist($team);

            $tt = new TournamentTeam();
            $tt->setTournament($tournament);
            $tt->setTeam($team);
            $tt->setIsConfirmed(true);
            $this->entityManager->persist($tt);
            $teams[] = $tt;
        }

        $this->entityManager->flush();

        // Logic from TournamentController to generate bracket
        $roundsNeeded = (int)ceil(log($teamCount, 2));
        
        $stageNames = [
            1 => 'Final',
            2 => 'Semifinales',
            4 => 'Cuartos de Final',
            8 => 'Octavos de Final',
            16 => 'Dieciseisavos de Final',
            32 => 'Treintaidosavos de Final'
        ];

        for ($r = 1; $r <= $roundsNeeded; $r++) {
            $matchesInRound = pow(2, $r - 1);
            $stageLabel = $stageNames[$matchesInRound] ?? 'Ronda ' . ($roundsNeeded - $r + 1);
            
            for ($p = 0; $p < $matchesInRound; $p++) {
                $match = new MusMatch();
                $match->setTournament($tournament);
                $match->setStage($stageLabel);
                $match->setBracketRound($r);
                $match->setBracketPosition($p);
                
                if ($r === $roundsNeeded) {
                    $teamIndex1 = $p * 2;
                    $teamIndex2 = $p * 2 + 1;
                    
                    if (isset($teams[$teamIndex1])) {
                        $match->setTeam1($teams[$teamIndex1]->getTeam());
                    }
                    if (isset($teams[$teamIndex2])) {
                        $match->setTeam2($teams[$teamIndex2]->getTeam());
                    }
                }

                $this->entityManager->persist($match);
            }
        }

        $this->entityManager->flush();
    }
}
