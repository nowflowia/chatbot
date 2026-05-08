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
    // IA (OpenAI / Anthropic)
    $router->post('/settings/ai/test', ['App\Controllers\SettingsController', 'testAi']);
    $router->post('/settings/ai', ['App\Controllers\SettingsController', 'storeAi']);

    // IA Config (Persona + Knowledge Base + Sites)
    $router->get('/ai-config',                  ['App\Controllers\AiConfigController', 'index']);
    $router->post('/ai-config/persona',         ['App\Controllers\AiConfigController', 'savePersona']);
    $router->post('/ai-config/qa',              ['App\Controllers\AiConfigController', 'storeQa']);
    $router->post('/ai-config/qa/{id}/toggle',  ['App\Controllers\AiConfigController', 'toggleQa']);
    $router->post('/ai-config/qa/{id}/delete',  ['App\Controllers\AiConfigController', 'destroyQa']);
    $router->post('/ai-config/docs',            ['App\Controllers\AiConfigController', 'uploadDoc']);
    $router->post('/ai-config/docs/{id}/delete',['App\Controllers\AiConfigController', 'destroyDoc']);
    $router->post('/ai-config/sites',           ['App\Controllers\AiConfigController', 'storeSite']);
    $router->post('/ai-config/sites/{id}/delete',['App\Controllers\AiConfigController', 'destroySite']);
    $router->post('/ai-config/test',            ['App\Controllers\AiConfigController', 'testChat']);

    // License diagnostics
    $router->get('/license',         ['App\Controllers\LicenseController', 'index']);
    $router->post('/license/refresh',['App\Controllers\LicenseController', 'refresh']);
    $router->post('/license/probe',  ['App\Controllers\LicenseController', 'probe']);

    // System update
    $router->get('/system-update', ['App\Controllers\SystemUpdateController', 'index']);
    $router->post('/system-update/status', ['App\Controllers\SystemUpdateController', 'status']);
    $router->post('/system-update/pull',   ['App\Controllers\SystemUpdateController', 'pull']);
    $router->get('/system-update/backup-files', ['App\Controllers\SystemUpdateController', 'backupFiles']);
    $router->get('/system-update/backup-db',    ['App\Controllers\SystemUpdateController', 'backupDb']);

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

    // ── Marketing ─────────────────────────────────────────────────
    $router->get('/marketing',                                   ['App\Controllers\MarketingController', 'index'])->middleware('feature:marketing');
    $router->post('/marketing/lists',                            ['App\Controllers\MarketingController', 'storeList'])->middleware('feature:marketing');
    $router->post('/marketing/lists/{id}/delete',                ['App\Controllers\MarketingController', 'destroyList'])->middleware('feature:marketing');
    $router->post('/marketing/lists/{id}/contacts',              ['App\Controllers\MarketingController', 'addContacts'])->middleware('feature:marketing');
    $router->get('/marketing/lists/{id}/contacts',               ['App\Controllers\MarketingController', 'listContacts'])->middleware('feature:marketing');
    $router->post('/marketing/campaigns',                        ['App\Controllers\MarketingController', 'storeCampaign'])->middleware('feature:marketing');
    $router->post('/marketing/campaigns/{id}/send',              ['App\Controllers\MarketingController', 'sendCampaign'])->middleware('feature:marketing');
    $router->post('/marketing/campaigns/{id}/delete',            ['App\Controllers\MarketingController', 'destroyCampaign'])->middleware('feature:marketing');
    $router->post('/marketing/contacts/search',                  ['App\Controllers\MarketingController', 'searchContact'])->middleware('feature:marketing');

    // ── CRM Admin ─────────────────────────────────────────────────
    $router->get('/crm-admin',                                 ['App\Controllers\CrmAdminController', 'index']);
    $router->get('/crm-admin/contacts/template',               ['App\Controllers\CrmAdminController', 'contactsTemplate']);
    $router->post('/crm-admin/contacts/import',                ['App\Controllers\CrmAdminController', 'importContacts']);

    // ── WhatsApp Admin ─────────────────────────────────────────────
    $router->get('/whatsapp',                                ['App\Controllers\WhatsAppAdminController', 'index']);
    $router->post('/whatsapp/templates',                     ['App\Controllers\WhatsAppAdminController', 'store']);
    $router->post('/whatsapp/templates/{id}/delete',         ['App\Controllers\WhatsAppAdminController', 'destroy']);
    $router->post('/whatsapp/templates/{id}/submit',         ['App\Controllers\WhatsAppAdminController', 'submit']);
    $router->post('/whatsapp/sync',                          ['App\Controllers\WhatsAppAdminController', 'syncFromMeta']);

    // ── Conversations ──────────────────────────────────────────────
    $router->get('/conversations/active',                    ['App\Controllers\ConversationController', 'active']);
    $router->post('/conversations/send',                     ['App\Controllers\ConversationController', 'send']);
    $router->post('/conversations/search-contact',           ['App\Controllers\ConversationController', 'searchContact']);
});
