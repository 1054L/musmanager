<?php

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\MusMatch;
use App\Entity\User;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

try {
    // Clean up with TRUNCATE and RESTART IDENTITY to avoid ID issues
    $connection = $entityManager->getConnection();
    $connection->executeStatement('TRUNCATE "mus_match", "team_player", "team", "player", "tournament", "app_user" RESTART IDENTITY CASCADE');

    // Create Users
$super = new User();
$super->setEmail('super@example.com');
$super->setRoles(['ROLE_SUPER_ADMIN']);
$super->setPassword(password_hash('password', PASSWORD_BCRYPT));
$entityManager->persist($super);

$admin = new User();
$admin->setEmail('admin@example.com');
$admin->setRoles(['ROLE_ADMIN']);
$admin->setPassword(password_hash('password', PASSWORD_BCRYPT));
$entityManager->persist($admin);

$userLogin = new User();
$userLogin->setEmail('user@example.com');
$userLogin->setRoles(['ROLE_USER']);
$userLogin->setPassword(password_hash('password', PASSWORD_BCRYPT));
$entityManager->persist($userLogin);

// Create Player for regular user
$pUser = new Player();
$pUser->setName('Jugador Registrado');
$pUser->setEmail('user@example.com');
$pUser->setLinkedUser($userLogin);
$entityManager->persist($pUser);

// Create Tournament owned by Admin
$tourneyAdmin = new Tournament();
$tourneyAdmin->setName('Torneo de Admin');
$tourneyAdmin->setStatus('active');
$tourneyAdmin->setCreatedBy($admin);
$tourneyAdmin->setUuidAccessToken('11111111-1111-1111-1111-111111111111');
$tourneyAdmin->setStartDate(new \DateTimeImmutable('2026-04-15 10:00:00'));
$tourneyAdmin->setPosterPath('https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=800&auto=format&fit=crop');
$entityManager->persist($tourneyAdmin);

// Create Tournament owned by Super
$tourneySuper = new Tournament();
$tourneySuper->setName('Torneo de Super');
$tourneySuper->setStatus('active');
$tourneySuper->setCreatedBy($super);
$tourneySuper->setUuidAccessToken('00000000-0000-0000-0000-000000000000');
$tourneySuper->setStartDate(new \DateTimeImmutable('2026-05-20 18:30:00'));
$tourneySuper->setPosterPath('https://images.unsplash.com/photo-1544161515-4af6b1d8ed6e?q=80&w=800&auto=format&fit=crop');
$entityManager->persist($tourneySuper);

// Create Match
$team1 = new Team();
$team1->setName('Team User');
$team1->addPlayer($pUser);
$entityManager->persist($team1);

$team2 = new Team();
$team2->setName('Team Bot');
$pBot = new Player();
$pBot->setName('Bot');
$pBot->setEmail('bot@example.com');
$entityManager->persist($pBot);
$team2->addPlayer($pBot);
$entityManager->persist($team2);

$match = new MusMatch();
$match->setTournament($tourneyAdmin);
$match->setTeam1($team1);
$match->setTeam2($team2);
$match->setScoreTeam1(40);
$match->setScoreTeam2(0);
$match->setStage('Ronda 1');
$entityManager->persist($match);

    $entityManager->flush();

    echo "Seeded successfully for RBAC testing!\n";
    echo "Superadmin: super@example.com / password\n";
    echo "Admin: admin@example.com / password\n";
    echo "User: user@example.com / password\n";
} catch (\Exception $e) {
    echo "ERROR SEEDING: " . $e->getMessage() . "\n";
    exit(1);
}
?>
