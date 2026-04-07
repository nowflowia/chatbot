<?php \Core\View::section('title') ?>API — Documentação<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Documentação da API<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$BASE = rtrim($baseUrl, '/');

function renderEndpoint(array $ep): void {
    $id      = $ep['id'];
    $method  = strtoupper($ep['method']);
    $path    = $ep['path'];
    $summary = $ep['summary'];
    $auth    = $ep['auth'] ?? true;
    $params  = $ep['params'] ?? [];
    $body    = $ep['body'] ?? [];
    $resp    = $ep['response'] ?? '{}';

    $methodClass = 'method-' . strtolower($method);

    // Build default path for try-it
    $tryPath = preg_replace('/\{(\w+)\}/', ':$1', $path);
    $hasPathParam = str_contains($path, '{');
    $hasBody = in_array($method, ['POST','PUT','PATCH']);

    // Build default body JSON
    $defaultBody = [];
    foreach ($body as $b) { if ($b['required']) $defaultBody[$b['name']] = ''; }
    $defaultBodyJson = $defaultBody ? json_encode($defaultBody, JSON_PRETTY_PRINT) : '{}';

    $qsParams = array_filter($params, fn($p) => !($p['path'] ?? false) && !$hasBody);
    ?>
<div class="endpoint-card" id="<?= e($id) ?>">
  <div class="endpoint-header" id="hdr-<?= e($id) ?>" onclick="toggleEndpoint('<?= e($id) ?>')">
    <span class="method-badge <?= $methodClass ?>"><?= $method ?></span>
    <span class="endpoint-path"><?= e($path) ?></span>
    <span class="endpoint-summary"><?= e($summary) ?></span>
    <?php if ($auth): ?>
    <span class="badge bg-warning-subtle text-warning ms-2" style="font-size:.65rem;font-weight:600;">
      <i class="bi bi-lock-fill"></i> Auth
    </span>
    <?php endif; ?>
    <i class="bi bi-chevron-down ms-2 text-muted" style="font-size:.75rem;margin-left:auto!important;flex-shrink:0;transition:transform .2s" id="chev-<?= e($id) ?>"></i>
  </div>
  <div class="endpoint-body" id="body-<?= e($id) ?>">

    <?php if ($params): ?>
    <h6 class="fw-bold mb-2 small text-uppercase text-muted">Parâmetros</h6>
    <table class="param-table mb-4">
      <thead><tr><th>Nome</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr></thead>
      <tbody>
      <?php foreach ($params as $p): ?>
      <tr>
        <td><code><?= e($p['name']) ?></code></td>
        <td><span class="param-type"><?= e($p['type']) ?></span></td>
        <td><?= ($p['required'] ?? false) ? '<span class="param-required">SIM</span>' : '<span class="text-muted">não</span>' ?></td>
        <td class="small text-muted"><?= e($p['desc']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <?php if ($body): ?>
    <h6 class="fw-bold mb-2 small text-uppercase text-muted">Body (JSON)</h6>
    <table class="param-table mb-4">
      <thead><tr><th>Campo</th><th>Tipo</th><th>Obrigatório</th><th>Descrição</th></tr></thead>
      <tbody>
      <?php foreach ($body as $b): ?>
      <tr>
        <td><code><?= e($b['name']) ?></code></td>
        <td><span class="param-type"><?= e($b['type']) ?></span></td>
        <td><?= ($b['required'] ?? false) ? '<span class="param-required">SIM</span>' : '<span class="text-muted">não</span>' ?></td>
        <td class="small text-muted"><?= e($b['desc']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <h6 class="fw-bold mb-2 small text-uppercase text-muted">Exemplo de resposta</h6>
    <div class="code-block mb-4"><?= htmlspecialchars($resp) ?></div>

    <!-- Try it out -->
    <details>
      <summary class="btn btn-sm btn-outline-primary" style="cursor:pointer;list-style:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="bi bi-play-fill"></i> Testar
      </summary>
      <div class="try-panel mt-2">
        <?php if ($hasPathParam): ?>
        <label>URL (substitua os parâmetros de rota)</label>
        <input type="text" id="try-path-<?= e($id) ?>" value="<?= e($path) ?>" placeholder="<?= e($path) ?>">
        <?php else: ?>
        <input type="hidden" id="try-path-<?= e($id) ?>" value="<?= e($path) ?>">
        <?php endif; ?>

        <?php if (!$hasBody && $params): ?>
        <label>Query string (ex: status=waiting&page=1)</label>
        <input type="text" id="try-qs-<?= e($id) ?>" placeholder="param1=valor&param2=valor">
        <?php else: ?>
        <input type="hidden" id="try-qs-<?= e($id) ?>" value="">
        <?php endif; ?>

        <?php if ($hasBody): ?>
        <label>Body (JSON)</label>
        <textarea id="try-body-<?= e($id) ?>"><?= htmlspecialchars($defaultBodyJson) ?></textarea>
        <?php endif; ?>

        <button class="btn btn-primary btn-sm" onclick="executeRequest('<?= e($id) ?>','<?= $method ?>','<?= e($path) ?>')">
          <i class="bi bi-send me-1"></i>Executar
        </button>
        <div id="try-resp-<?= e($id) ?>" class="try-response"></div>
      </div>
    </details>

  </div>
</div>
<?php
}
?>

<style>
/* ── Swagger-like layout ─────────────────────────────── */
.api-layout { display: flex; gap: 0; min-height: calc(100vh - 120px); }
.api-sidebar {
  width: 260px; flex-shrink: 0; background: #0f172a; border-radius: 12px;
  padding: 16px 0; position: sticky; top: 80px; align-self: flex-start; max-height: calc(100vh - 100px); overflow-y: auto;
}
.api-sidebar a {
  display: block; padding: 7px 20px; color: #94a3b8; font-size: .8rem; text-decoration: none;
  border-left: 3px solid transparent; transition: all .15s;
}
.api-sidebar a:hover, .api-sidebar a.active { color: #f8fafc; border-left-color: #6366f1; background: rgba(255,255,255,.05); }
.api-sidebar .nav-group-label {
  padding: 14px 20px 4px; font-size: .65rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: #475569;
}
.api-content { flex: 1; padding: 0 0 0 24px; min-width: 0; }

/* Endpoint card */
.endpoint-card { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
.endpoint-header {
  display: flex; align-items: center; gap: 12px; padding: 14px 18px;
  cursor: pointer; background: #fff; transition: background .15s;
}
.endpoint-header:hover { background: #f8fafc; }
.endpoint-header.open { background: #f8fafc; }
.endpoint-body { display: none; border-top: 1px solid #e2e8f0; padding: 20px; background: #fff; }
.endpoint-body.show { display: block; }

/* Method badges */
.method-badge {
  font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 4px;
  min-width: 56px; text-align: center; font-family: monospace; text-transform: uppercase;
}
.method-get    { background: #dbeafe; color: #1d4ed8; }
.method-post   { background: #dcfce7; color: #166534; }
.method-put    { background: #fef9c3; color: #854d0e; }
.method-delete { background: #fee2e2; color: #991b1b; }

.endpoint-path { font-family: monospace; font-size: .9rem; font-weight: 600; color: #1e293b; }
.endpoint-summary { font-size: .8rem; color: #64748b; margin-left: auto; }

/* Params table */
.param-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.param-table th { background: #f1f5f9; padding: 8px 12px; font-weight: 600; text-align: left; color: #475569; }
.param-table td { padding: 8px 12px; border-top: 1px solid #f1f5f9; vertical-align: top; }
.param-required { color: #dc2626; font-size: .7rem; font-weight: 700; }
.param-type { font-family: monospace; font-size: .75rem; color: #7c3aed; }

/* Code block */
.code-block {
  background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 14px 16px;
  font-family: monospace; font-size: .78rem; overflow-x: auto; white-space: pre;
}
.code-block .key   { color: #93c5fd; }
.code-block .str   { color: #86efac; }
.code-block .num   { color: #fcd34d; }
.code-block .bool  { color: #f9a8d4; }
.code-block .null  { color: #94a3b8; }

/* Try-it-out */
.try-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 16px; }
.try-panel label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block; }
.try-panel input, .try-panel textarea, .try-panel select {
  width: 100%; font-size: .82rem; padding: 7px 10px; border: 1px solid #d1d5db;
  border-radius: 6px; background: #fff; margin-bottom: 10px; font-family: inherit;
}
.try-panel textarea { font-family: monospace; height: 120px; resize: vertical; }
.try-response { margin-top: 12px; border-radius: 8px; overflow: hidden; }
.try-response-status { padding: 6px 14px; font-size: .78rem; font-weight: 700; }
.status-2xx { background: #dcfce7; color: #166534; }
.status-4xx { background: #fee2e2; color: #991b1b; }
.status-5xx { background: #fef3c7; color: #92400e; }

/* Section */
.api-section { margin-bottom: 32px; }
.api-section-title {
  font-size: 1rem; font-weight: 700; color: #1e293b; padding: 8px 0 12px;
  border-bottom: 2px solid #6366f1; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}

/* Auth bar */
.auth-bar {
  background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 10px;
  padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.auth-bar label { color: #94a3b8; font-size: .8rem; margin: 0; white-space: nowrap; }
.auth-bar input {
  flex: 1; min-width: 200px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
  border-radius: 6px; padding: 8px 12px; color: #f8fafc; font-family: monospace; font-size: .82rem;
}
.auth-bar input::placeholder { color: #475569; }
.btn-authorize {
  background: #6366f1; color: #fff; border: none; border-radius: 6px;
  padding: 8px 18px; font-size: .82rem; font-weight: 600; cursor: pointer; white-space: nowrap;
}
.btn-authorize:hover { background: #4f46e5; }
.auth-status { font-size: .78rem; font-weight: 600; }
.auth-ok   { color: #86efac; }
.auth-fail { color: #fca5a5; }
</style>

<div class="api-layout">

  <!-- Sidebar -->
  <div class="api-sidebar">
    <div class="nav-group-label">Visão geral</div>
    <a href="#section-intro" class="active">Introdução</a>
    <a href="#section-auth">Autenticação</a>

    <div class="nav-group-label">Atendimento</div>
    <a href="#ep-auth-login">POST /auth/login</a>
    <a href="#ep-auth-me">GET /auth/me</a>

    <div class="nav-group-label">Conversas</div>
    <a href="#ep-chats-list">GET /chats</a>
    <a href="#ep-chats-show">GET /chats/{id}</a>
    <a href="#ep-chats-messages">GET /chats/{id}/messages</a>
    <a href="#ep-chats-send">POST /chats/{id}/messages</a>
    <a href="#ep-chats-assign">POST /chats/{id}/assign</a>
    <a href="#ep-chats-finish">POST /chats/{id}/finish</a>

    <div class="nav-group-label">Contatos</div>
    <a href="#ep-contacts-list">GET /contacts</a>
    <a href="#ep-contacts-show">GET /contacts/{id}</a>
  </div>

  <!-- Content -->
  <div class="api-content">

    <!-- Auth bar -->
    <div class="auth-bar">
      <label><i class="bi bi-lock-fill me-1"></i>API Key:</label>
      <?php if (!empty($apiKeys)): ?>
      <select id="key-select" style="flex:0;min-width:0;width:auto;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:6px;padding:7px 10px;color:#f8fafc;font-size:.8rem;">
        <option value="">Selecionar chave...</option>
        <?php foreach ($apiKeys as $ak): if (!$ak['is_active']) continue; ?>
        <option value="<?= e($ak['key']) ?>"><?= e($ak['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <input type="password" id="global-token" placeholder="Cole sua API key aqui ou selecione acima…">
      <button class="btn-authorize" onclick="setGlobalToken()"><i class="bi bi-unlock me-1"></i>Autorizar</button>
      <span id="auth-status" class="auth-status"></span>
    </div>

    <!-- ── Introdução ─────────────────────────────────────────── -->
    <div class="api-section" id="section-intro">
      <div class="api-section-title"><i class="bi bi-book"></i> Introdução</div>
      <div class="card p-4">
        <h6 class="fw-bold mb-2">Base URL</h6>
        <div class="code-block mb-3"><?= e($BASE) ?></div>

        <h6 class="fw-bold mb-2">Formato das respostas</h6>
        <p class="small text-muted mb-2">Todas as respostas seguem o padrão:</p>
        <div class="code-block mb-3">{
  <span class="key">"success"</span>: <span class="bool">true</span>,
  <span class="key">"message"</span>: <span class="str">"Descrição"</span>,
  <span class="key">"data"</span>: { ... },
  <span class="key">"errors"</span>: {}
}</div>

        <h6 class="fw-bold mb-2">Códigos de status HTTP</h6>
        <table class="param-table mb-0">
          <thead><tr><th>Código</th><th>Significado</th></tr></thead>
          <tbody>
            <tr><td><span class="param-type">200</span></td><td>Sucesso</td></tr>
            <tr><td><span class="param-type">201</span></td><td>Criado com sucesso</td></tr>
            <tr><td><span class="param-type">401</span></td><td>Não autenticado — API key ausente ou inválida</td></tr>
            <tr><td><span class="param-type">403</span></td><td>Sem permissão</td></tr>
            <tr><td><span class="param-type">404</span></td><td>Recurso não encontrado</td></tr>
            <tr><td><span class="param-type">409</span></td><td>Conflito — ex: conversa já finalizada</td></tr>
            <tr><td><span class="param-type">422</span></td><td>Dados inválidos</td></tr>
            <tr><td><span class="param-type">502</span></td><td>Erro ao enviar via WhatsApp</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Autenticação ───────────────────────────────────────── -->
    <div class="api-section" id="section-auth">
      <div class="api-section-title"><i class="bi bi-shield-lock"></i> Autenticação</div>
      <div class="card p-4">
        <p class="small mb-3">Todas as rotas protegidas requerem um dos headers abaixo:</p>
        <div class="code-block mb-3">Authorization: Bearer <span class="str">{api_key}</span>

<span class="null">// ou</span>

X-API-Key: <span class="str">{api_key}</span></div>
        <p class="small text-muted mb-0">Obtenha sua chave em <strong>Configurações → API</strong> ou use o endpoint <code>POST /auth/login</code>.</p>
      </div>
    </div>

    <!-- ── AUTH endpoints ─────────────────────────────────────── -->
    <?php renderEndpoint([
      'id'      => 'ep-auth-login',
      'method'  => 'POST',
      'path'    => '/auth/login',
      'summary' => 'Autenticar e obter API key',
      'section' => 'Autenticação',
      'auth'    => false,
      'params'  => [],
      'body'    => [
        ['name'=>'email',    'type'=>'string', 'required'=>true,  'desc'=>'E-mail do usuário'],
        ['name'=>'password', 'type'=>'string', 'required'=>true,  'desc'=>'Senha do usuário'],
      ],
      'response' => '{
  "success": true,
  "message": "Autenticado com sucesso.",
  "data": {
    "token": "abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@empresa.com",
      "role": "admin"
    }
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-auth-me',
      'method'  => 'GET',
      'path'    => '/auth/me',
      'summary' => 'Retorna dados do usuário autenticado',
      'auth'    => true,
      'params'  => [],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@empresa.com",
      "role_slug": "admin"
    }
  }
}',
    ]); ?>

    <!-- ── CHATS ──────────────────────────────────────────────── -->
    <div class="api-section">
      <div class="api-section-title"><i class="bi bi-chat-dots"></i> Conversas</div>
    </div>

    <?php renderEndpoint([
      'id'      => 'ep-chats-list',
      'method'  => 'GET',
      'path'    => '/chats',
      'summary' => 'Listar conversas',
      'auth'    => true,
      'params'  => [
        ['name'=>'status', 'type'=>'string', 'required'=>false, 'desc'=>'Filtrar por status: waiting, in_progress, finished, bot, all (padrão: all)'],
        ['name'=>'search', 'type'=>'string', 'required'=>false, 'desc'=>'Buscar por nome ou telefone do contato'],
        ['name'=>'page',   'type'=>'integer','required'=>false, 'desc'=>'Página (padrão: 1)'],
        ['name'=>'limit',  'type'=>'integer','required'=>false, 'desc'=>'Itens por página, máx. 50 (padrão: 20)'],
      ],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "data": [
      {
        "id": 42,
        "status": "in_progress",
        "channel": "whatsapp",
        "last_message": "Olá, preciso de ajuda",
        "last_message_at": "2025-04-06 14:23:00",
        "unread_count": 3,
        "agent_name": "Maria Santos",
        "contact": {
          "name": "João Silva",
          "phone": "5511999990000"
        }
      }
    ],
    "total": 120,
    "per_page": 20,
    "current_page": 1,
    "last_page": 6
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-chats-show',
      'method'  => 'GET',
      'path'    => '/chats/{id}',
      'summary' => 'Detalhes de uma conversa',
      'auth'    => true,
      'params'  => [
        ['name'=>'id', 'type'=>'integer', 'required'=>true, 'desc'=>'ID da conversa (path param)'],
      ],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "chat": {
      "id": 42,
      "status": "in_progress",
      "channel": "whatsapp",
      "protocol": "PROT-00042",
      "last_message": "Olá, preciso de ajuda",
      "last_message_at": "2025-04-06 14:23:00",
      "unread_count": 0,
      "is_bot_active": false,
      "assigned_to": 3,
      "agent_name": "Maria Santos",
      "started_at": "2025-04-06 14:00:00",
      "finished_at": null,
      "contact": {
        "name": "João Silva",
        "phone": "5511999990000",
        "avatar": null
      }
    }
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-chats-messages',
      'method'  => 'GET',
      'path'    => '/chats/{id}/messages',
      'summary' => 'Listar mensagens de uma conversa',
      'auth'    => true,
      'params'  => [
        ['name'=>'id',        'type'=>'integer', 'required'=>true,  'desc'=>'ID da conversa (path param)'],
        ['name'=>'before_id', 'type'=>'integer', 'required'=>false, 'desc'=>'ID da mensagem — carrega mensagens mais antigas (paginação infinita)'],
        ['name'=>'limit',     'type'=>'integer', 'required'=>false, 'desc'=>'Quantidade, entre 10 e 100 (padrão: 40)'],
      ],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 100,
        "chat_id": 42,
        "direction": "inbound",
        "type": "text",
        "content": "Olá, preciso de ajuda",
        "status": "read",
        "media_url": null,
        "created_at": "2025-04-06 14:23:00"
      },
      {
        "id": 101,
        "chat_id": 42,
        "direction": "outbound",
        "type": "text",
        "content": "Olá! Como posso ajudar?",
        "status": "delivered",
        "media_url": null,
        "created_at": "2025-04-06 14:24:10"
      }
    ],
    "has_more": false,
    "chat_id": 42
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-chats-send',
      'method'  => 'POST',
      'path'    => '/chats/{id}/messages',
      'summary' => 'Enviar mensagem de texto',
      'auth'    => true,
      'params'  => [
        ['name'=>'id', 'type'=>'integer', 'required'=>true, 'desc'=>'ID da conversa (path param)'],
      ],
      'body'    => [
        ['name'=>'content', 'type'=>'string', 'required'=>true, 'desc'=>'Texto da mensagem a enviar'],
      ],
      'response' => '{
  "success": true,
  "message": "Mensagem enviada.",
  "data": {
    "message": {
      "id": 102,
      "chat_id": 42,
      "direction": "outbound",
      "type": "text",
      "content": "Olá! Como posso ajudar?",
      "status": "sent",
      "created_at": "2025-04-06 14:24:10"
    }
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-chats-assign',
      'method'  => 'POST',
      'path'    => '/chats/{id}/assign',
      'summary' => 'Atribuir conversa a um agente',
      'auth'    => true,
      'params'  => [
        ['name'=>'id', 'type'=>'integer', 'required'=>true, 'desc'=>'ID da conversa (path param)'],
      ],
      'body'    => [
        ['name'=>'user_id', 'type'=>'integer', 'required'=>false, 'desc'=>'ID do agente (apenas admin/supervisor). Se omitido, atribui ao usuário da API key.'],
      ],
      'response' => '{
  "success": true,
  "message": "Atribuído com sucesso.",
  "data": { "chat": { "id": 42, "status": "in_progress", "agent_name": "Maria Santos" } }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-chats-finish',
      'method'  => 'POST',
      'path'    => '/chats/{id}/finish',
      'summary' => 'Finalizar conversa',
      'auth'    => true,
      'params'  => [
        ['name'=>'id', 'type'=>'integer', 'required'=>true, 'desc'=>'ID da conversa (path param)'],
      ],
      'body'    => [
        ['name'=>'notes', 'type'=>'string', 'required'=>false, 'desc'=>'Observações sobre o encerramento'],
      ],
      'response' => '{
  "success": true,
  "message": "Conversa finalizada.",
  "data": { "chat": { "id": 42, "status": "finished", "finished_at": "2025-04-06 15:00:00" } }
}',
    ]); ?>

    <!-- ── CONTACTS ───────────────────────────────────────────── -->
    <div class="api-section">
      <div class="api-section-title"><i class="bi bi-people"></i> Contatos</div>
    </div>

    <?php renderEndpoint([
      'id'      => 'ep-contacts-list',
      'method'  => 'GET',
      'path'    => '/contacts',
      'summary' => 'Listar contatos',
      'auth'    => true,
      'params'  => [
        ['name'=>'search', 'type'=>'string',  'required'=>false, 'desc'=>'Buscar por nome, telefone ou e-mail'],
        ['name'=>'page',   'type'=>'integer', 'required'=>false, 'desc'=>'Página (padrão: 1)'],
        ['name'=>'limit',  'type'=>'integer', 'required'=>false, 'desc'=>'Itens por página, máx. 50 (padrão: 20)'],
      ],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "data": [
      {
        "id": 10,
        "name": "João Silva",
        "phone": "5511999990000",
        "email": "joao@exemplo.com",
        "status": "active",
        "last_seen_at": "2025-04-06 14:23:00"
      }
    ],
    "total": 350,
    "per_page": 20,
    "current_page": 1,
    "last_page": 18
  }
}',
    ]); ?>

    <?php renderEndpoint([
      'id'      => 'ep-contacts-show',
      'method'  => 'GET',
      'path'    => '/contacts/{id}',
      'summary' => 'Detalhes de um contato',
      'auth'    => true,
      'params'  => [
        ['name'=>'id', 'type'=>'integer', 'required'=>true, 'desc'=>'ID do contato (path param)'],
      ],
      'body'    => [],
      'response' => '{
  "success": true,
  "data": {
    "contact": {
      "id": 10,
      "name": "João Silva",
      "phone": "5511999990000",
      "email": "joao@exemplo.com",
      "status": "active",
      "notes": null,
      "last_seen_at": "2025-04-06 14:23:00",
      "created_at": "2025-01-15 09:00:00"
    }
  }
}',
    ]); ?>

  </div><!-- /api-content -->
</div><!-- /api-layout -->

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
var GLOBAL_TOKEN = '';
var BASE_URL     = '<?= e($BASE) ?>';

/* ── Sidebar active link on scroll ───── */
var sideLinks = document.querySelectorAll('.api-sidebar a[href^="#"]');
window.addEventListener('scroll', function () {
  var scrollY = window.scrollY + 100;
  sideLinks.forEach(function (a) {
    var target = document.getElementById(a.getAttribute('href').slice(1));
    if (target && target.offsetTop <= scrollY) {
      sideLinks.forEach(l => l.classList.remove('active'));
      a.classList.add('active');
    }
  });
}, { passive: true });

/* ── Smooth scroll ───────────────────── */
sideLinks.forEach(function (a) {
  a.addEventListener('click', function (e) {
    e.preventDefault();
    var target = document.getElementById(this.getAttribute('href').slice(1));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

/* ── Auth ────────────────────────────── */
var keySelect = document.getElementById('key-select');
if (keySelect) {
  keySelect.addEventListener('change', function () {
    document.getElementById('global-token').value = this.value;
  });
}

function setGlobalToken() {
  GLOBAL_TOKEN = document.getElementById('global-token').value.trim();
  var st = document.getElementById('auth-status');
  if (GLOBAL_TOKEN) {
    st.className = 'auth-status auth-ok';
    st.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Autorizado';
  } else {
    st.className = 'auth-status auth-fail';
    st.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Sem chave';
  }
}

/* ── Endpoint toggle ─────────────────── */
function toggleEndpoint(id) {
  var header = document.getElementById('hdr-' + id);
  var body   = document.getElementById('body-' + id);
  var chev   = document.getElementById('chev-' + id);
  var open   = body.classList.contains('show');
  body.classList.toggle('show', !open);
  header.classList.toggle('open', !open);
  if (chev) chev.style.transform = open ? '' : 'rotate(180deg)';
}

/* ── Execute request ─────────────────── */
async function executeRequest(id, method, path) {
  var pathInput  = document.getElementById('try-path-' + id);
  var bodyInput  = document.getElementById('try-body-' + id);
  var qsInput    = document.getElementById('try-qs-' + id);
  var respDiv    = document.getElementById('try-resp-' + id);

  var url = BASE_URL + (pathInput ? pathInput.value : path);
  if (qsInput && qsInput.value.trim()) {
    url += '?' + qsInput.value.trim().replace(/^\?/, '');
  }

  var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  if (GLOBAL_TOKEN) headers['Authorization'] = 'Bearer ' + GLOBAL_TOKEN;

  var options = { method: method, headers: headers };
  if (['POST','PUT','PATCH'].includes(method) && bodyInput) {
    try {
      options.body = bodyInput.value.trim() || '{}';
      JSON.parse(options.body); // validate
    } catch (e) {
      respDiv.innerHTML = '<div class="try-response-status status-4xx">JSON inválido no body</div>';
      return;
    }
  }

  respDiv.innerHTML = '<div class="try-response-status" style="background:#f1f5f9;color:#475569">Aguardando...</div>';
  try {
    var r = await fetch(url, options);
    var text = await r.text();
    var statusClass = r.status < 300 ? 'status-2xx' : (r.status < 500 ? 'status-4xx' : 'status-5xx');
    var pretty = '';
    try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch (e) { pretty = text; }
    respDiv.innerHTML = '<div class="try-response-status ' + statusClass + '">HTTP ' + r.status + '</div>'
      + '<div class="code-block" style="max-height:300px;overflow-y:auto;">' + escHtml(pretty) + '</div>';
  } catch (e) {
    respDiv.innerHTML = '<div class="try-response-status status-5xx">Erro de rede: ' + escHtml(e.message) + '</div>';
  }
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
<?php \Core\View::endSection() ?>
