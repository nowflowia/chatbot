<?php

use Core\Router;

/** @var Router $router */

// Public routes
$router->get('/', ['App\Controllers\HomeController', 'index']);

// Auth routes
$router->get('/login', ['App\Controllers\AuthController', 'showLogin'])->middleware('guest');
$router->post('/login', ['App\Controllers\AuthController', 'login'])->middleware('guest');
$router->get('/logout', ['App\Controllers\AuthController', 'logout'])->middleware('auth');
$router->get('/invite/{token}', ['App\Controllers\AuthController', 'showSetPassword']);
$router->post('/invite/{token}', ['App\Controllers\AuthController', 'setPassword']);
$router->get('/forgot-password', ['App\Controllers\AuthController', 'showForgotPassword']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'forgotPassword']);

// Admin routes (protected)
$router->group(['prefix' => 'admin', 'middleware' => ['auth']], function (Router $router) {

    // Dashboard
    $router->get('/dashboard', ['App\Controllers\DashboardController', 'index']);

    // Users
    $router->get('/users', ['App\Controllers\UserController', 'index']);
    $router->post('/users', ['App\Controllers\UserController', 'store']);
    $router->get('/users/{id}', ['App\Controllers\UserController', 'show']);
    $router->post('/users/{id}', ['App\Controllers\UserController', 'update']);
    $router->post('/users/{id}/delete', ['App\Controllers\UserController', 'destroy']);
    $router->post('/users/{id}/invite', ['App\Controllers\UserController', 'resendInvite']);
    $router->post('/users/refresh-license', ['App\Controllers\UserController', 'refreshLicense']);

    // Settings
    $router->get('/settings', ['App\Controllers\SettingsController', 'index']);
    $router->post('/settings', ['App\Controllers\SettingsController', 'store']);
    $router->post('/settings/test', ['App\Controllers\SettingsController', 'test']);
    $router->post('/settings/company', ['App\Controllers\SettingsController', 'storeCompany']);
    $router->post('/settings/template', ['App\Controllers\SettingsController', 'storeTemplate']);
    // static /mail/test before /mail
    $router->post('/settings/mail/test', ['App\Controllers\SettingsController', 'testMail']);
    $router->post('/settings/mail', ['App\Controllers\SettingsController', 'storeMail']);

    // Chat — static routes MUST come before dynamic {id} routes
    $router->get('/chat', ['App\Controllers\ChatController', 'index']);
    $router->get('/chat/list/active', ['App\Controllers\ChatController', 'getActiveChats']);
    $router->get('/chat/{id}', ['App\Controllers\ChatController', 'show']);
    $router->get('/chat/{id}/messages', ['App\Controllers\ChatController', 'getMessages']);
    $router->get('/chat/{id}/data', ['App\Controllers\ChatController', 'getSingleChat']);
    $router->post('/chat/{id}/message', ['App\Controllers\ChatController', 'sendMessage']);
    $router->post('/chat/{id}/assign', ['App\Controllers\ChatController', 'assign']);
    $router->post('/chat/{id}/finish', ['App\Controllers\ChatController', 'finish']);

    // Queue — static routes before dynamic
    $router->get('/queue', ['App\Controllers\QueueController', 'index']);
    $router->get('/queue/list', ['App\Controllers\QueueController', 'getList']);
    $router->post('/queue/{id}/assign', ['App\Controllers\QueueController', 'assign']);
    $router->post('/queue/{id}/transfer', ['App\Controllers\QueueController', 'transfer']);
    $router->post('/queue/{id}/finish', ['App\Controllers\QueueController', 'finish']);

    // Webhook Logs — static before dynamic
    $router->get('/webhook-logs', ['App\Controllers\WebhookLogController', 'index']);
    $router->post('/webhook-logs/clear', ['App\Controllers\WebhookLogController', 'clear']);
    $router->post('/webhook-logs/toggle-logging', ['App\Controllers\WebhookLogController', 'toggleLogging']);
    $router->get('/webhook-logs/{id}', ['App\Controllers\WebhookLogController', 'show']);

    // Flows
    $router->get('/flows', ['App\Controllers\FlowController', 'index']);
    $router->post('/flows', ['App\Controllers\FlowController', 'store']);
    $router->get('/flows/{id}/edit', ['App\Controllers\FlowController', 'edit']);
    $router->post('/flows/{id}', ['App\Controllers\FlowController', 'update']);
    $router->post('/flows/{id}/delete', ['App\Controllers\FlowController', 'destroy']);
    $router->post('/flows/{id}/save-builder', ['App\Controllers\FlowController', 'saveBuilder']);
    $router->get('/flows/{id}/data', ['App\Controllers\FlowController', 'getBuilderData']);
});
