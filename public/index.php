<?php
use App\Controller\UserController;
use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/** @var Container */
$container = require dirname(__DIR__) . '/config/container.php';

// slim default container has a number of default services hardcoded
//   instead of re-defining those here we wrap slim container into our own with delegator
$slimDefaultContainer = new Slim\Container([
    'settings' => [
        'displayErrorDetails'  => true,
    ],
]);
$container->delegate($slimDefaultContainer);

$app = new Slim\App($container);

/** @var callable */
$configureRoutes = require dirname(__DIR__) . '/config/routes.php';

$configureRoutes($app, $container);

$app->run();
