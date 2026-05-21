<?php
use App\Kernel;
use App\Entity\Tournament;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

$tournaments = $entityManager->getRepository(Tournament::class)->findAll();

foreach ($tournaments as $t) {
    echo "Torneo: " . $t->getName() . " (ID: " . $t->getId() . ")\n";
    echo "Parejas inscritas: " . count($t->getTournamentTeams()) . "\n";
    foreach ($t->getTournamentTeams() as $tt) {
        echo " - " . $tt->getTeam()->getName() . " (ID TT: " . $tt->getId() . ")\n";
    }
    echo "--------------------------\n";
}
