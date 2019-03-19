<?php

use App\Controller\UserController;
use Psr\Container\ContainerInterface;
use Slim\App;

return function (App $app, ContainerInterface $container): void {
    /** @var UserController */
    $userController = $container->get(UserController::class);

    $app->get('/users/{id}', [$userController, 'getOne']);
    $app->put('/users/{id}', [$userController, 'update']);
    $app->post('/users/{id}/avatar', [$userController, 'changeAvatar']);
    $app->delete('/users/{id}', [$userController, 'delete']);
    $app->get('/users', [$userController, 'getAll']);
    $app->post('/users', [$userController, 'create']);
};
