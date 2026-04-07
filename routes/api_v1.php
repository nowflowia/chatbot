<?php

use Core\Router;

/** @var Router $router */

// ── REST API v1 — served from public_api/ ─────────────────────────
// Base URL: https://api.yourdomain.com/v1/
// Auth: Bearer <api_key>  or  X-API-Key: <api_key>
// ──────────────────────────────────────────────────────────────────

$router->group(['prefix' => 'v1'], function (Router $router) {

    // ── Public (no auth) ─────────────────────────────────────────
    $router->get('/status', function () {
        json_response(true, 'API online', ['version' => '1.0', 'timestamp' => now()]);
    });

    $router->post('/auth/login', ['App\Controllers\Api\AuthApiController', 'login']);

    // ── Protected (require API key) ───────────────────────────────
    $router->group(['middleware' => ['api']], function (Router $router) {

        // Auth
        $router->get('/auth/me', ['App\Controllers\Api\AuthApiController', 'me']);

        // Chats
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
