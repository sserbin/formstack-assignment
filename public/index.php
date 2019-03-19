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

/** @var UserController */
$userController = $container->get(UserController::class);

$app->get('/users/{id}', [$userController, 'getOne']);
$app->put('/users/{id}', [$userController, 'update']);
$app->delete('/users/{id}', [$userController, 'delete']);
$app->get('/users', [$userController, 'getAll']);
$app->post('/users', [$userController, 'create']);

$app->run();
