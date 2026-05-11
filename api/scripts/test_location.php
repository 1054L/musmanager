<?php
require 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$request = Request::create('/api/location/all', 'GET');
$request->headers->set('Authorization', 'Basic ' . base64_encode('admin@test.com:admin123')); 
$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
$kernel->terminate($request, $response);
