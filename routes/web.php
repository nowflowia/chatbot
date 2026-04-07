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
    $router->post('/users/refresh-license', ['App\Controllers\UserController', 'refreshLicense']); // static before {id}
    $router->get('/users/{id}', ['App\Controllers\UserController', 'show']);
    $router->post('/users/{id}', ['App\Controllers\UserController', 'update']);
    $router->post('/users/{id}/delete', ['App\Controllers\UserController', 'destroy']);
    $router->post('/users/{id}/invite', ['App\Controllers\UserController', 'resendInvite']);

    // Settings
    $router->get('/settings', ['App\Controllers\SettingsController', 'index']);
    $router->post('/settings', ['App\Controllers\SettingsController', 'store']);
    $router->post('/settings/test', ['App\Controllers\SettingsController', 'test']);
    $router->post('/settings/company', ['App\Controllers\SettingsController', 'storeCompany']);
    $router->post('/settings/template', ['App\Controllers\SettingsController', 'storeTemplate']);
    // static /mail/test before /mail
    $router->post('/settings/mail/test', ['App\Controllers\SettingsController', 'testMail']);
    $router->post('/settings/mail', ['App\Controllers\SettingsController', 'storeMail']);

    // System update
    $router->get('/system-update', ['App\Controllers\SystemUpdateController', 'index']);
    $router->post('/system-update/status', ['App\Controllers\SystemUpdateController', 'status']);
    $router->post('/system-update/pull',   ['App\Controllers\SystemUpdateController', 'pull']);

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

    // API Keys (settings tab)
    $router->post('/settings/api-keys',             ['App\Controllers\ApiKeyController', 'store']);
    $router->post('/settings/api-keys/{id}/toggle', ['App\Controllers\ApiKeyController', 'toggle']);
    $router->post('/settings/api-keys/{id}/delete', ['App\Controllers\ApiKeyController', 'destroy']);

    // API Documentation
    $router->get('/api-docs', ['App\Controllers\ApiDocsController', 'index']);

    // ── CRM (feature: crm) ───────────────────────────────────────────
    // Settings (static) — MUST come before dynamic routes
    $router->get('/crm/settings', ['App\Controllers\CrmSettingsController', 'index'])->middleware('feature:crm');
    $router->post('/crm/settings/pipelines', ['App\Controllers\CrmSettingsController', 'storePipeline'])->middleware('feature:crm');
    $router->post('/crm/settings/stages/reorder', ['App\Controllers\CrmSettingsController', 'reorderStages'])->middleware('feature:crm');
    $router->post('/crm/settings/stages', ['App\Controllers\CrmSettingsController', 'storeStage'])->middleware('feature:crm');
    $router->post('/crm/settings/pipelines/{id}/delete', ['App\Controllers\CrmSettingsController', 'destroyPipeline'])->middleware('feature:crm');
    $router->post('/crm/settings/pipelines/{id}', ['App\Controllers\CrmSettingsController', 'updatePipeline'])->middleware('feature:crm');
    $router->post('/crm/settings/stages/{id}/delete', ['App\Controllers\CrmSettingsController', 'destroyStage'])->middleware('feature:crm');
    $router->post('/crm/settings/stages/{id}', ['App\Controllers\CrmSettingsController', 'updateStage'])->middleware('feature:crm');

    // Companies
    $router->get('/crm/companies', ['App\Controllers\CrmCompanyController', 'index'])->middleware('feature:crm');
    $router->post('/crm/companies', ['App\Controllers\CrmCompanyController', 'store'])->middleware('feature:crm');
    $router->get('/crm/companies/{id}', ['App\Controllers\CrmCompanyController', 'show'])->middleware('feature:crm');
    $router->post('/crm/companies/{id}/delete', ['App\Controllers\CrmCompanyController', 'destroy'])->middleware('feature:crm');
    $router->post('/crm/companies/{id}', ['App\Controllers\CrmCompanyController', 'update'])->middleware('feature:crm');

    // Contacts
    $router->get('/crm/contacts', ['App\Controllers\CrmContactController', 'index'])->middleware('feature:crm');
    $router->post('/crm/contacts', ['App\Controllers\CrmContactController', 'store'])->middleware('feature:crm');
    $router->post('/crm/contacts/{id}/delete', ['App\Controllers\CrmContactController', 'destroy'])->middleware('feature:crm');
    $router->post('/crm/contacts/{id}', ['App\Controllers\CrmContactController', 'update'])->middleware('feature:crm');

    // Tasks
    $router->post('/crm/tasks', ['App\Controllers\CrmTaskController', 'store'])->middleware('feature:crm');
    $router->post('/crm/tasks/{id}/done', ['App\Controllers\CrmTaskController', 'done'])->middleware('feature:crm');
    $router->post('/crm/tasks/{id}/delete', ['App\Controllers\CrmTaskController', 'destroy'])->middleware('feature:crm');
    $router->post('/crm/tasks/{id}', ['App\Controllers\CrmTaskController', 'update'])->middleware('feature:crm');

    // Deals — static before dynamic
    $router->post('/crm/deals', ['App\Controllers\CrmDealController', 'store'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/move', ['App\Controllers\CrmDealController', 'moveStage'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/win', ['App\Controllers\CrmDealController', 'win'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/lose', ['App\Controllers\CrmDealController', 'lose'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/notes', ['App\Controllers\CrmDealController', 'addNote'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/files', ['App\Controllers\CrmDealController', 'uploadFile'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/files/{fileId}/delete', ['App\Controllers\CrmDealController', 'deleteFile'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}/delete', ['App\Controllers\CrmDealController', 'destroy'])->middleware('feature:crm');
    $router->post('/crm/deals/{id}', ['App\Controllers\CrmDealController', 'update'])->middleware('feature:crm');
    $router->get('/crm/deals/{id}', ['App\Controllers\CrmDealController', 'show'])->middleware('feature:crm');

    // CRM board & main
    $router->get('/crm/board/{pipelineId}', ['App\Controllers\CrmController', 'board'])->middleware('feature:crm');
    $router->get('/crm', ['App\Controllers\CrmController', 'index'])->middleware('feature:crm');
});
