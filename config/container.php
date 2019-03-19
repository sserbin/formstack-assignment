<?php
use Symfony\Component\Dotenv\Dotenv;
use League\Container\Container;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    (new Dotenv)->load('.env');
} catch (Error $e) {
    // ignore if file not exists or DotEnv is not enabled (i.e. production env)
}

$container = new Container;

$container->add(PDO::class, function (): PDO {
    $db = getenv('DB_NAME');
    if (!$db) {
        throw new RuntimeException('DB_NAME env var not found');
    }
    $host = getenv('DB_HOST');
    if (!$host) {
        throw new RuntimeException('DB_HOST env var not found');
    }
    $user = getenv('DB_USER');
    if (!$user) {
        throw new RuntimeException('DB_USER env var not found');
    }
    $pass = getenv('DB_PASS');
    if (!$pass) {
        throw new RuntimeException('DB_PASS env var not found');
    }

    try {
        return new PDO(sprintf("mysql:dbname=%s;host=%s", $db, $host), $user, $pass);
    } catch (PDOException $e) {
        throw new RuntimeException('failed to set a pdo connection', 0, $e);
    }
});

$container->add('db.name', getenv('DB_NAME'));

return $container;
