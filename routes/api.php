<?php

use Core\Router;

/** @var Router $router */

// Webhook Meta (public - validated internally)
$router->get('/webhook', ['App\Controllers\WebhookController', 'verify']);
$router->post('/webhook', ['App\Controllers\WebhookController', 'receive']);

// API routes (bearer token)
$router->group(['prefix' => 'api/v1'], function (Router $router) {
    $router->get('/status', function () {
        json_response(true, 'API online', ['version' => '1.0.0', 'timestamp' => now()]);
    });
});
