<?php

use Symfony\Component\Dotenv\Dotenv;

require 'vendor/autoload.php';

(new Dotenv)->load('.env');

$db = getenv('DB_NAME');
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// todo: move setup to container
$pdo = new PDO(sprintf("mysql:dbname=%s;host=%s", $db, $host), $user, $pass);

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'development',
        'development' => [
            'name' => $db,
            'connection' => $pdo,
        ],
    ],
    'version_order' => 'creation'
];
