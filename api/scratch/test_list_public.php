<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $tournamentRepo = $container->get('doctrine')->getRepository(\App\Entity\Tournament::class);
    
    echo "Fetching public tournaments...\n";
    $tournaments = $tournamentRepo->findBy([
        'status' => ['active', 'pending', 'finished'],
        'private' => false
    ], ['id' => 'DESC']);
    
    echo "Found " . count($tournaments) . " tournaments.\n";
    
    foreach ($tournaments as $t) {
        echo "Processing Tournament: " . $t->getName() . " (ID: " . $t->getId() . ")\n";
        try {
            $data = [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'uuid' => $t->getUuidAccessToken(),
                'status' => $t->getStatus(),
                'type' => $t->getType(),
                'startDate' => $t->getStartDate() ? $t->getStartDate()->format(\DateTimeInterface::ATOM) : null,
                'endDate' => $t->getEndDate() ? $t->getEndDate()->format(\DateTimeInterface::ATOM) : null,
                'posterPath' => $t->getPosterPath(),
                'location' => $t->getLocation(),
                'provinceName' => $t->getProvince() ? $t->getProvince()->getName() : null,
                'townName' => $t->getTown() ? $t->getTown()->getName() : null,
                'teamsCount' => count($t->getTournamentTeams()),
            ];
            echo "  Basic data OK\n";
            
            $owner = $t->getCreatedBy();
            if ($owner) {
                echo "  Owner ID: " . $owner->getId() . "\n";
                $playerName = $owner->getPlayer() ? $owner->getPlayer()->getName() : $owner->getEmail();
                echo "  Owner Name: " . $playerName . "\n";
            }
        } catch (\Exception $e) {
            echo "  ERROR: " . $e->getMessage() . "\n";
        }
    }
    echo "Finished!\n";
};
