<?php
use App\Kernel;
use App\Entity\Tournament;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$tournamentRepository = $container->get('doctrine')->getRepository(Tournament::class);

// Buscar el torneo puiopuop
$tournament = $tournamentRepository->findOneBy(['name' => 'puiopuop']);

if (!$tournament) {
    echo "Torneo no encontrado\n";
    exit;
}

echo "UUID: " . $tournament->getUuidAccessToken() . "\n";
echo "Equipos en la entidad: " . count($tournament->getTournamentTeams()) . "\n";

// Simular lo que hace el controlador
$teams = array_map(function($tt) {
    return [
        'id' => $tt->getId(),
        'name' => $tt->getTeam()->getName(),
        'mesa' => $tt->getMesa()
    ];
}, $tournament->getTournamentTeams()->toArray());

echo "JSON Teams count: " . count($teams) . "\n";
echo "JSON Teams data: " . json_encode($teams, JSON_PRETTY_PRINT) . "\n";
