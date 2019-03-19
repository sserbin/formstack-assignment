<?php
use App\Controller\Transformer\UserTransformer;
use App\Service\UserMapper;
use App\Service\UserMapperInterface;
use App\Controller\UserController;
use League\Container\ReflectionContainer;
use Symfony\Component\Dotenv\Dotenv;
use League\Container\Container;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    (new Dotenv)->load(dirname(__DIR__) . '/.env');
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
        return new PDO(
            sprintf("mysql:dbname=%s;host=%s", $db, $host),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        throw new RuntimeException('failed to set a pdo connection', 0, $e);
    }
}, $shared = true);

$container->add('db.name', getenv('DB_NAME'));

$container->add(UserMapperInterface::class, UserMapper::class)->addArgument(PDO::class);
$container->add(UserMapper::class)->addArgument(PDO::class);
$container->add(UserTransformer::class)->addArgument((string)getenv('BASE_URL') . '/avatar');
$container->add(UserController::class)
    ->addArgument(UserMapper::class)
    ->addArgument(UserTransformer::class)
    ->addArgument(getenv('AVATAR_STORAGE_DIR'));

return $container;
