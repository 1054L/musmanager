<?php
require 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Repository\TournamentRepository;

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var UserRepository $userRepository */
$userRepository = $container->get('doctrine')->getRepository(\App\Entity\User::class);
$user = $userRepository->findOneBy(['email' => 'iosulazcano@gmail.com']); // Use the user from logs

if (!$user) {
    echo "User not found\n";
    exit;
}

/** @var TournamentRepository $tournamentRepository */
$tournamentRepository = $container->get('doctrine')->getRepository(\App\Entity\Tournament::class);

if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
    $tournaments = $tournamentRepository->findBy([], ['id' => 'DESC']);
} else {
    $tournaments = $tournamentRepository->findManagedByUser($user);
}

echo "Found " . count($tournaments) . " tournaments\n";

foreach ($tournaments as $t) {
    echo "Tournament: " . $t->getName() . "\n";
    // Simulate the array_map return
    $data = [
        'id' => $t->getId(),
        'name' => $t->getName(),
        'uuid' => $t->getUuidAccessToken(),
        'status' => $t->getStatus(),
        'type' => $t->getType(),
        'startDate' => $t->getStartDate() ? $t->getStartDate()->format(\DateTimeInterface::ATOM) : null,
        'endDate' => $t->getEndDate() ? $t->getEndDate()->format(\DateTimeInterface::ATOM) : null,
        'ruleKings' => $t->getRuleKings(),
        'rulePoints' => $t->getRulePoints(),
        'ruleGames' => $t->getRuleGames(),
        'tablesCount' => $t->getTablesCount(),
        'location' => $t->getLocation(),
        'provinceId' => $t->getProvince() ? $t->getProvince()->getId() : null,
        'provinceName' => $t->getProvince() ? $t->getProvince()->getName() : null,
        'townId' => $t->getTown() ? $t->getTown()->getId() : null,
        'townName' => $t->getTown() ? $t->getTown()->getName() : null,
        'posterPath' => $t->getPosterPath(),
        'rulesPath' => $t->getRulesPath(),
        'teamsCount' => count($t->getTournamentTeams()),
        'hasMatches' => count($t->getMatches()) > 0,
        'private' => $t->isPrivate(),
    ];
    echo " - Data serialized OK\n";
}

echo "All OK\n";
