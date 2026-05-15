<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $tournamentRepo = $container->get('doctrine')->getRepository(\App\Entity\Tournament::class);
    
    echo "Fetching tournaments...\n";
    try {
        $tournaments = $tournamentRepo->findAll();
        echo "Found " . count($tournaments) . " tournaments.\n";
        
        $serializer = $container->get('serializer');
        echo "Serializing...\n";
        $json = $serializer->serialize($tournaments, 'json', ['groups' => ['tournament:read']]);
        echo "Done!\n";
        // echo $json;
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
};
