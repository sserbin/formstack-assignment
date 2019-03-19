<?php
use Psr\Container\ContainerInterface;

/** @var ContainerInterface */
$container = require 'config/container.php';

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
            'name' => $container->get('db.name'),
            'connection' => $container->get(PDO::class),
        ],
    ],
    'version_order' => 'creation'
];
