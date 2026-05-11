<?php
require 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('dev', true);
$request = Request::create('/api/me', 'GET');
$request->headers->set('Authorization', 'Basic ' . base64_encode('admin@mus.com:admin')); // Assuming default admin
$response = $kernel->handle($request);

echo $response->getContent();
$kernel->terminate($request, $response);
