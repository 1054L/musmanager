<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

return function (array $context) {
    return new Kernel('dev', true);
};
