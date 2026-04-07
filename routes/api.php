<?php

use Core\Router;

/** @var Router $router */

// ── Webhook Meta (public — validated internally) ─────────────────
$router->get('/webhook',  ['App\Controllers\WebhookController', 'verify']);
$router->post('/webhook', ['App\Controllers\WebhookController', 'receive']);

// ── Public API ────────────────────────────────────────────────────
$router->group(['prefix' => 'api/v1'], function (Router $router) {

    // Status (no auth required)
    $router->get('/status', function () {
        json_response(true, 'API online', ['version' => '1.0', 'timestamp' => now()]);
    });

    // Authentication (no auth required)
    $router->post('/auth/login', ['App\Controllers\Api\AuthApiController', 'login']);

    // ── Protected endpoints (require API key) ─────────────────────
    $router->group(['middleware' => ['api']], function (Router $router) {

        // Auth
        $router->get('/auth/me', ['App\Controllers\Api\AuthApiController', 'me']);

        // Chats — static routes before dynamic
        $router->get('/chats',                  ['App\Controllers\Api\ChatApiController', 'index']);
        $router->get('/chats/{id}',             ['App\Controllers\Api\ChatApiController', 'show']);
        $router->get('/chats/{id}/messages',    ['App\Controllers\Api\ChatApiController', 'messages']);
        $router->post('/chats/{id}/messages',   ['App\Controllers\Api\ChatApiController', 'sendMessage']);
        $router->post('/chats/{id}/assign',     ['App\Controllers\Api\ChatApiController', 'assign']);
        $router->post('/chats/{id}/finish',     ['App\Controllers\Api\ChatApiController', 'finish']);

        // Contacts
        $router->get('/contacts',       ['App\Controllers\Api\ChatApiController', 'contacts']);
        $router->get('/contacts/{id}',  ['App\Controllers\Api\ChatApiController', 'contact']);
    });
});
