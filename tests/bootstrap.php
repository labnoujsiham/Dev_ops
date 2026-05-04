<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/../connexion/auth_helpers.php';
require_once __DIR__ . '/../connexion/db_config.php';