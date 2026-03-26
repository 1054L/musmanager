<?php
require 'vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv('.env');
$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$t = $em->getRepository(App\Entity\Tournament::class)->findOneBy(['name' => 'Gran Liga de Invierno 2026']);
if ($t) {
    echo $t->getUuidAccessToken() . PHP_EOL;
} else {
    echo "Tournament not found\n";
}
