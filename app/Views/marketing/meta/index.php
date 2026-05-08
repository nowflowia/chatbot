<?php \Core\View::section('title') ?>META Ads — Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>META Ads — Campanhas<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">META Ads com IA</h5>
    <small class="text-muted">Agente Claude cria estratégias, redige anúncios e sobe campanhas — tudo com sua aprovação</small>
  </div>
  <button class="btn btn-primary d-flex align-items-center gap-2" onclick="openNewSession()">
    <i class="bi bi-stars me-1"></i> Nova Sessão com Agente
  </button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="metaTabs">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-agent">
      <i class="bi bi-robot me-1"></i> Agente IA
      <span class="badge bg-secondary ms-1"><?= count($sessions) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-campaigns">
      <i class="bi bi-bar-chart-fill me-1"></i> Campanhas
      <span class="badge bg-secondary ms-1"><?= count($campaigns) ?></span>
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ══════════════════ AGENTE ══════════════════ -->
  <div class="tab-pane fade show active" id="pane-agent">
    <div class="row g-3" style="height:calc(100vh - 320px);min-height:500px;">

      <!-- Sidebar de sessões -->
      <div class="col-md-3 d-flex flex-column" style="height:100%;">
        <div class="fw-semibold small text-muted mb-2 ps-1">Sessões</div>
        <div class="flex-grow-1 overflow-auto" id="sessions-list" style="border-right:1px solid #e5e7eb;">
          <?php if (empty($sessions)): ?>
          <div class="text-center text-muted py-4 small" id="sessions-empty">
            Nenhuma sessão ainda.<br>Clique em <strong>Nova Sessão</strong>.
          </div>
          <?php else: ?>
          <?php foreach ($sessions as $s): ?>
          <div class="session-item p-2 px-3 border-bottom" style="cursor:pointer;"
               id="si-<?= $s['id'] ?>" onclick="loadSession(<?= $s['id'] ?>)">
            <div class="small fw-semibold text-truncate"><?= e($s['title']) ?></div>
            <div class="d-flex align-items-center gap-1" style="font-size:.7rem;">
              <span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?> py-0" style="font-size:.65rem;"><?= $s['status']==='active'?'Ativa':'Encerrada' ?></span>
              <span class="text-muted"><?= date('d/m H:i', strtotime($s['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chat principal -->
      <div class="col-md-9 d-flex flex-column" style="height:100%;">

        <!-- Estado vazio -->
        <div id="agent-empty" class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-muted">
          <div style="width:72px;height:72px;background:linear-gradient(135deg,#1877f2,#e1306c);border-radius:20px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
            <i class="bi bi-stars text-white fs-2"></i>
          </div>
          <h6 class="fw-bold">Agente META Ads com IA</h6>
          <p class="text-center small mb-3" style="max-width:420px;">
            O agente Claude cria estratégias personalizadas, redige copy dos anúncios,
            configura público-alvo e sobe as campanhas na Meta — tudo com <strong>sua aprovação</strong> antes de executar.
          </p>
          <button class="btn btn-primary" onclick="openNewSession()">
            <i class="bi bi-stars me-1"></i> Iniciar Nova Sessão
          </button>
        </div>

        <!-- Chat ativo -->
        <div id="agent-chat" class="d-flex flex-column h-100" style="display:none!important;">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold" id="chat-title">—</div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary" onclick="showBriefPanel()" title="Brief rápido">
                <i class="bi bi-lightning-charge-fill"></i> Brief
              </button>
            </div>
          </div>

          <!-- Messages -->
          <div class="flex-grow-1 overflow-auto border rounded p-3 bg-white" id="chat-messages"
               style="scroll-behavior:smooth;">
            <div class="text-center text-muted py-4 small" id="chat-welcome">
              Olá! Descreva o produto/serviço, objetivo e orçamento da campanha para começar.
            </div>
          </div>

          <!-- Brief panel (quick form) -->
          <div id="brief-panel" class="border rounded p-3 mt-2 bg-light" style="display:none;">
            <div class="fw-semibold small mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Brief Rápido</div>
            <div class="row g-2">
              <div class="col-md-6">
                <input type="text" id="b-product" class="form-control form-control-sm" placeholder="Produto / serviço *">
              </div>
              <div class="col-md-6">
                <select id="b-objective" class="form-select form-select-sm">
                  <option value="OUTCOME_AWARENESS">Reconhecimento de Marca</option>
                  <option value="OUTCOME_TRAFFIC" selected>Tráfego</option>
                  <option value="OUTCOME_ENGAGEMENT">Engajamento</option>
                  <option value="OUTCOME_LEADS">Geração de Leads</option>
                  <option value="OUTCOME_SALES">Vendas / Conversões</option>
                </select>
              </div>
              <div class="col-md-4">
                <input type="text" id="b-audience" class="form-control form-control-sm" placeholder="Público-alvo (ex: mulheres 25-45)">
              </div>
              <div class="col-md-4">
                <input type="text" id="b-budget" class="form-control form-control-sm" placeholder="Orçamento diário (R$)">
              </div>
              <div class="col-md-4">
                <select id="b-platform" class="form-select form-select-sm">
                  <option value="ambas">Facebook + Instagram</option>
                  <option value="facebook">Somente Facebook</option>
                  <option value="instagram">Somente Instagram</option>
                </select>
              </div>
              <div class="col-12">
                <input type="text" id="b-extra" class="form-control form-control-sm" placeholder="Informações adicionais (opcional)">
              </div>
            </div>
            <div class="d-flex gap-2 mt-2">
              <button class="btn btn-sm btn-primary" onclick="sendBrief()">
                <i class="bi bi-send me-1"></i> Enviar Brief ao Agente
              </button>
              <button class="btn btn-sm btn-light" onclick="document.getElementById('brief-panel').style.display='none'">Cancelar</button>
            </div>
          </div>

          <!-- Input -->
          <div class="mt-2 d-flex gap-2 align-items-end">
            <textarea id="chat-input" class="form-control" rows="2"
                      placeholder="Escreva para o agente..."
                      style="resize:none;"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
            <button class="btn btn-primary px-3" onclick="sendMessage()" id="btn-send-msg" style="height:42px;">
              <span class="spinner-border spinner-border-sm d-none" id="msg-spinner"></span>
              <i class="bi bi-send-fill" id="msg-icon"></i>
            </button>
          </div>
          <div class="text-muted mt-1" style="font-size:.7rem;">Enter para enviar · Shift+Enter nova linha</div>

        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════ CAMPANHAS ══════════════════ -->
  <div class="tab-pane fade" id="pane-campaigns">
    <?php if (empty($campaigns)): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-50"></i>
      Nenhuma campanha criada ainda. Use o <strong>Agente IA</strong> para criar a primeira.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Nome</th>
            <th>Objetivo</th>
            <th>Plataformas</th>
            <th>Status</th>
            <th>Meta ID</th>
            <th>Criada em</th>
            <th class="pe-3 text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($campaigns as $c): ?>
        <?php
        $platforms = json_decode($c['platforms'] ?? '[]', true) ?: [];
        $statusMap = [
            'draft'            => ['secondary','Rascunho'],
            'pending_approval' => ['warning text-dark','Aguardando'],
            'active'           => ['success','Ativa'],
            'paused'           => ['info text-dark','Pausada'],
            'completed'        => ['primary','Concluída'],
            'cancelled'        => ['danger','Cancelada'],
        ];
        [$scls,$slabel] = $statusMap[$c['status'] ?? 'draft'] ?? ['secondary','Rascunho'];
        $objMap = [
            'OUTCOME_AWARENESS' => 'Reconhecimento',
            'OUTCOME_TRAFFIC'   => 'Tráfego',
            'OUTCOME_ENGAGEMENT'=> 'Engajamento',
            'OUTCOME_LEADS'     => 'Leads',
            'OUTCOME_SALES'     => 'Vendas',
        ];
        ?>
        <tr id="camp-<?= $c['id'] ?>">
          <td class="ps-3 fw-semibold small"><?= e($c['name']) ?></td>
          <td class="small text-muted"><?= $objMap[$c['objective'] ?? ''] ?? e($c['objective'] ?? '—') ?></td>
          <td>
            <?php foreach ($platforms as $p): ?>
            <span class="badge bg-light text-dark border small me-1">
              <i class="bi bi-<?= $p === 'instagram' ? 'instagram' : 'facebook' ?> me-1"></i><?= ucfirst($p) ?>
            </span>
            <?php endforeach; ?>
          </td>
          <td><span class="badge bg-<?= $scls ?>"><?= $slabel ?></span></td>
          <td class="small font-monospace text-muted"><?= e($c['meta_campaign_id'] ?? '—') ?></td>
          <td class="small text-muted"><?= !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—' ?></td>
          <td class="pe-3 text-end">
            <?php if (!empty($c['meta_campaign_id'])): ?>
            <button class="btn btn-sm btn-outline-info" onclick="refreshInsights(<?= $c['id'] ?>)" title="Atualizar métricas">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php if (!empty($c['insights'])): ?>
        <?php $ins = is_string($c['insights']) ? json_decode($c['insights'], true) : $c['insights']; ?>
        <tr>
          <td colspan="7" class="ps-4 pb-2 pt-0">
            <div class="d-flex flex-wrap gap-3 small text-muted">
              <?php foreach (['impressions'=>'Impressões','reach'=>'Alcance','clicks'=>'Cliques','spend'=>'Investido','cpc'=>'CPC','ctr'=>'CTR'] as $k=>$label): ?>
              <?php if (isset($ins[$k])): ?>
              <span><strong class="text-dark"><?= $label ?>:</strong> <?= $k === 'spend' ? 'R$ ' . number_format((float)$ins[$k], 2, ',', '.') : (in_array($k,['cpc']) ? 'R$ '.number_format((float)$ins[$k],2,',','.') : e($ins[$k])) ?></span>
              <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /tab-content -->

<!-- MODAL: Nova Sessão -->
<div class="modal fade" id="newSessionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-stars text-primary me-2"></i>Nova Sessão com Agente
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label fw-semibold small">Nome da sessão</label>
        <input type="text" id="new-session-title" class="form-control"
               placeholder="Ex.: Campanha Maio 2025 — Produto X" value="">
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" onclick="createSession()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="ns-spinner"></span>
          Criar Sessão
        </button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const META_MK = {
  sessions:  '<?= url('admin/marketing/meta/agent/session') ?>',
  agent:     '<?= url('admin/marketing/meta/agent') ?>',
  campaigns: '<?= url('admin/marketing/meta/campaigns') ?>',
  csrf:      '<?= csrf_token() ?>',
};

let activeSessionId = null;
let newSessionModal;

document.addEventListener('DOMContentLoaded', () => {
  newSessionModal = new bootstrap.Modal(document.getElementById('newSessionModal'));
});

// ── Session management ────────────────────────────────────────────────

function openNewSession() {
  document.getElementById('new-session-title').value = '';
  newSessionModal.show();
  setTimeout(() => document.getElementById('new-session-title').focus(), 400);
}

function createSession() {
  const title = document.getElementById('new-session-title').value.trim() || 'Nova estratégia';
  const spin  = document.getElementById('ns-spinner');
  spin.classList.remove('d-none');

  const fd = new FormData();
  fd.append('title', title);
  fd.append('_csrf_token', META_MK.csrf);

  fetch(META_MK.sessions, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json())
    .then(res => {
      spin.classList.add('d-none');
      if (res.success) {
        newSessionModal.hide();
        // Add to sidebar
        document.getElementById('sessions-empty')?.remove();
        const sid = res.data.session_id;
        document.getElementById('sessions-list').insertAdjacentHTML('afterbegin',
          `<div class="session-item p-2 px-3 border-bottom session-active" style="cursor:pointer;" id="si-${sid}" onclick="loadSession(${sid})">
             <div class="small fw-semibold text-truncate">${escHtml(res.data.title)}</div>
             <div style="font-size:.7rem;"><span class="badge bg-success py-0" style="font-size:.65rem;">Ativa</span></div>
           </div>`);
        loadSession(sid, res.data.title);
      }
    })
    .catch(()=>{ spin.classList.add('d-none'); Toast.show('Erro ao criar sessão.','error'); });
}

function loadSession(id, titleOverride) {
  // Highlight active
  document.querySelectorAll('.session-item').forEach(el => el.classList.remove('session-active','bg-primary','bg-opacity-10'));
  const el = document.getElementById('si-'+id);
  if (el) el.classList.add('bg-primary','bg-opacity-10');

  activeSessionId = id;
  document.getElementById('agent-empty').style.display = 'none';
  const chat = document.getElementById('agent-chat');
  chat.style.display = null;
  chat.style.removeProperty('display');
  document.getElementById('chat-messages').innerHTML = '<div class="text-center text-muted py-2 small" id="chat-loading"><div class="spinner-border spinner-border-sm me-1"></div> Carregando...</div>';

  fetch(META_MK.agent + '/' + id, { headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r=>r.json())
    .then(res => {
      if (!res.success) return;
      const s = res.data.session;
      document.getElementById('chat-title').textContent = titleOverride || s.title;
      document.getElementById('chat-messages').innerHTML = '';
      const msgs = s.messages || [];
      if (!msgs.length) {
        document.getElementById('chat-messages').innerHTML =
          '<div class="text-center text-muted py-4 small" id="chat-welcome">Olá! Descreva o produto/serviço, objetivo e orçamento para começar.</div>';
      } else {
        msgs.forEach(m => appendBubble(m.role, m.content, m.actions || [], m.system || false));
      }
      scrollChat();
    });
}

// ── Messaging ─────────────────────────────────────────────────────────

function sendMessage() {
  const inp = document.getElementById('chat-input');
  const msg = inp.value.trim();
  if (!msg || !activeSessionId) return;
  inp.value = '';
  doSend(msg, {});
}

function showBriefPanel() {
  const p = document.getElementById('brief-panel');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

function sendBrief() {
  if (!activeSessionId) return;
  const brief = {
    'Produto/Serviço':   document.getElementById('b-product').value,
    'Objetivo':          document.getElementById('b-objective').value,
    'Público-alvo':      document.getElementById('b-audience').value,
    'Orçamento diário':  document.getElementById('b-budget').value + (document.getElementById('b-budget').value ? ' R$' : ''),
    'Plataformas':       document.getElementById('b-platform').value,
    'Informações extras':document.getElementById('b-extra').value,
  };
  document.getElementById('brief-panel').style.display = 'none';
  doSend('', brief);
}

function doSend(message, brief) {
  if (!activeSessionId) return;
  setLoading(true);
  document.getElementById('chat-welcome')?.remove();

  if (message) appendBubble('user', message, []);

  const fd = new FormData();
  fd.append('_csrf_token', META_MK.csrf);
  if (message) fd.append('message', message);
  if (Object.keys(brief).length) {
    Object.entries(brief).forEach(([k,v]) => { if (v) fd.append(`brief[${k}]`, v); });
  }

  // Thinking bubble
  const thinkId = 'think-' + Date.now();
  document.getElementById('chat-messages').insertAdjacentHTML('beforeend',
    `<div id="${thinkId}" class="d-flex gap-2 mb-3">
       <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width:28px;height:28px;flex-shrink:0;font-size:.75rem;"><i class="bi bi-stars"></i></div>
       <div class="bg-light border rounded p-2 px-3 small" style="max-width:85%;">
         <div class="spinner-border spinner-border-sm me-1"></div> <span class="text-muted">Agente pensando…</span>
       </div>
     </div>`);
  scrollChat();

  fetch(META_MK.agent + '/' + activeSessionId + '/chat', {
    method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  })
    .then(r=>r.json())
    .then(res => {
      setLoading(false);
      document.getElementById(thinkId)?.remove();
      if (res.success) {
        appendBubble('assistant', res.data.content, res.data.actions || []);
      } else {
        appendBubble('assistant', '⚠️ ' + (res.message || 'Erro desconhecido.'), [], false, true);
      }
      scrollChat();
    })
    .catch(()=>{ setLoading(false); document.getElementById(thinkId)?.remove(); Toast.show('Erro de conexão.','error'); });
}

// ── Bubble rendering ──────────────────────────────────────────────────

function appendBubble(role, content, actions, isSystem, isError) {
  const isUser = role === 'user';
  const wrap   = document.getElementById('chat-messages');

  let actionsHtml = '';
  if (actions && actions.length) {
    actionsHtml = `<div class="mt-2 pt-2 border-top">
      <div class="small fw-semibold text-warning mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Ações propostas — revise antes de aprovar:</div>
      ${actions.map((a,i) => `
        <div class="border rounded p-2 mb-2 bg-white" style="font-size:.82rem;">
          <div class="fw-semibold mb-1">${actionTypeLabel(a.type)} <span class="text-muted fw-normal">— ${escHtml(a.description||'')}</span></div>
          <details class="mb-2">
            <summary class="text-muted small" style="cursor:pointer;">Ver detalhes técnicos</summary>
            <pre class="small bg-light p-2 rounded mt-1 mb-0" style="white-space:pre-wrap;font-size:.72rem;">${escHtml(JSON.stringify(a.data||{},null,2))}</pre>
          </details>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-success fw-semibold" onclick="approveAction(this,'${escHtml(a.type)}',${escHtml(JSON.stringify(a.data||{}))})">
              <i class="bi bi-check-lg me-1"></i>Aprovar e Executar
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="rejectAction(this,'${escHtml(a.description||a.type)}')">
              <i class="bi bi-x-lg me-1"></i>Rejeitar
            </button>
          </div>
        </div>`).join('')}
    </div>`;
  }

  const md = markdownToHtml(content);
  wrap.insertAdjacentHTML('beforeend',
    `<div class="d-flex ${isUser?'justify-content-end':''} gap-2 mb-3">
       ${!isUser ? `<div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white flex-shrink-0" style="width:28px;height:28px;font-size:.75rem;align-self:flex-start;margin-top:2px;"><i class="bi bi-stars"></i></div>` : ''}
       <div class="${isUser?'bg-primary text-white':'bg-light border'} rounded p-2 px-3" style="max-width:85%;font-size:.87rem;">
         <div>${md}</div>
         ${actionsHtml}
       </div>
       ${isUser ? `<div class="d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white flex-shrink-0" style="width:28px;height:28px;font-size:.75rem;align-self:flex-start;margin-top:2px;"><i class="bi bi-person-fill"></i></div>` : ''}
     </div>`);
}

// ── Action approval ───────────────────────────────────────────────────

function approveAction(btn, type, data) {
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Executando…';

  const fd = new FormData();
  fd.append('_csrf_token', META_MK.csrf);
  fd.append('type', type);
  fd.append('data', JSON.stringify(data));

  fetch(META_MK.agent + '/' + activeSessionId + '/execute', {
    method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  })
    .then(r=>r.json())
    .then(res => {
      const card = btn.closest('.border.rounded.p-2.mb-2');
      if (res.success) {
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Executado!';
        btn.className = 'btn btn-sm btn-success disabled';
        card.style.background = '#f0fdf4';
        Toast.show('Ação executada com sucesso!', 'success');
        appendBubble('assistant', `✅ **${actionTypeLabel(type)}** executada com sucesso.\nID Meta: ${res.data?.meta_result?.id || 'N/A'}`, []);
        scrollChat();
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Aprovar e Executar';
        Toast.show(res.message || 'Erro ao executar ação.', 'error');
        appendBubble('assistant', `⚠️ Falha ao executar **${actionTypeLabel(type)}**: ${res.message}`, []);
        scrollChat();
      }
    })
    .catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Aprovar e Executar'; Toast.show('Erro de conexão.','error'); });
}

function rejectAction(btn, desc) {
  const card = btn.closest('.border.rounded.p-2.mb-2');
  card.style.opacity = '0.5';
  btn.closest('.d-flex').innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle me-1"></i>Ação rejeitada pelo usuário</span>';
  // Inform agent
  doSend(`Rejeitei a ação: ${desc}. Por favor, sugira uma alternativa ou ajuste.`, {});
}

// ── Insights ──────────────────────────────────────────────────────────

function refreshInsights(id) {
  const fd = new FormData();
  fd.append('_csrf_token', META_MK.csrf);
  fetch(META_MK.campaigns + '/' + id + '/insights', {
    method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  })
    .then(r=>r.json())
    .then(res => Toast.show(res.message, res.success?'success':'error'))
    .catch(()=>Toast.show('Erro de conexão.','error'));
}

// ── Helpers ───────────────────────────────────────────────────────────

function actionTypeLabel(type) {
  return {
    create_campaign:  '📣 Criar Campanha',
    create_adset:     '🎯 Criar Conjunto de Anúncios',
    create_creative:  '🎨 Criar Criativo',
    create_ad:        '📢 Criar Anúncio',
    activate_campaign:'▶️ Ativar Campanha',
    pause_campaign:   '⏸️ Pausar Campanha',
    fetch_insights:   '📊 Buscar Métricas',
  }[type] || type;
}

function markdownToHtml(text) {
  if (!text) return '';
  return escHtml(text)
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/`(.+?)`/g, '<code>$1</code>')
    .replace(/\n/g, '<br>');
}

function setLoading(on) {
  const btn  = document.getElementById('btn-send-msg');
  const spin = document.getElementById('msg-spinner');
  const icon = document.getElementById('msg-icon');
  btn.disabled = on;
  spin.classList.toggle('d-none', !on);
  icon.classList.toggle('d-none', on);
}

function scrollChat() {
  const c = document.getElementById('chat-messages');
  if (c) c.scrollTop = c.scrollHeight;
}

function escHtml(s) {
  if (typeof s !== 'string') s = JSON.stringify(s);
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(s));
  return d.innerHTML;
}
</script>

<style>
.session-item:hover { background: #f8fafc; }
.session-active { background: #eff6ff !important; border-left: 3px solid #3b82f6; }
#chat-messages { min-height: 200px; }
</style>

<?php \Core\View::endSection() ?>
