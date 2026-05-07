<?php \Core\View::section('title') ?>IA Config<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>IA Config<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$tab = $activeTab ?? 'persona';

function fmtSize(int $bytes): string {
    if ($bytes < 1024)        return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1024 / 1024, 2) . ' MB';
}
?>

<ul class="nav nav-tabs mb-4" id="aicfg-tabs">
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'persona' ? 'active' : '' ?>"
       href="<?= url('admin/ai-config?tab=persona') ?>">
      <i class="bi bi-person-badge text-primary"></i> Persona
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'qa' ? 'active' : '' ?>"
       href="<?= url('admin/ai-config?tab=qa') ?>">
      <i class="bi bi-chat-quote-fill text-success"></i> Pergunta e Resposta
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'docs' ? 'active' : '' ?>"
       href="<?= url('admin/ai-config?tab=docs') ?>">
      <i class="bi bi-file-earmark-text-fill text-primary"></i> Documentos
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'site' ? 'active' : '' ?>"
       href="<?= url('admin/ai-config?tab=site') ?>">
      <i class="bi bi-globe2 text-info"></i> Site
    </a>
  </li>
  <li class="nav-item ms-auto">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'testar' ? 'active' : '' ?>"
       href="<?= url('admin/ai-config?tab=testar') ?>">
      <i class="bi bi-chat-dots-fill text-warning"></i> Testar
    </a>
  </li>
</ul>


<!-- ═══════════════════════════════════════════════════════════
     TAB: Persona
═══════════════════════════════════════════════════════════ -->
<div id="tab-persona" <?= $tab !== 'persona' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Persona do Agente</h5>
  <small class="text-muted">Define como a IA deve se comportar, o tom de voz e regras de resposta</small>
</div>

<div class="row g-4">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-person-badge text-primary"></i> Prompt do agente
      </div>
      <div class="card-body">
        <form id="persona-form" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Prompt</label>
            <textarea name="prompt" class="form-control font-monospace" rows="14"
                      placeholder="Você é um atendente da empresa X. Responda de forma cordial e objetiva. Sempre que não souber a resposta, diga que vai transferir para um humano…"><?= e($persona['prompt'] ?? '') ?></textarea>
            <small class="text-muted">
              Este texto é usado como <em>system prompt</em> em todas as conversas com a IA.
            </small>
          </div>
          <button type="submit" class="btn btn-primary fw-semibold" id="btn-persona-save">
            <span class="spinner-border spinner-border-sm d-none me-2" id="persona-save-spin"></span>
            <i class="bi bi-floppy me-2"></i> Salvar persona
          </button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-lightbulb text-warning"></i> Dicas
      </div>
      <div class="card-body small text-muted">
        <ul class="ps-3 mb-0" style="line-height:1.8;">
          <li>Seja específico sobre o tom (formal, descontraído).</li>
          <li>Defina o que o agente <strong>não</strong> deve fazer.</li>
          <li>Inclua o nome da empresa e do produto.</li>
          <li>Diga em quais casos transferir para humano.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

</div><!-- /tab-persona -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: Pergunta e Resposta
═══════════════════════════════════════════════════════════ -->
<div id="tab-qa" <?= $tab !== 'qa' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Pergunta e Resposta</h5>
  <small class="text-muted">Cadastre pares de pergunta e resposta para o agente consultar</small>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-collection-fill text-success"></i> Lista
      </div>
      <div class="card-body">
        <div id="qa-empty" class="text-center text-muted py-4 small <?= empty($qa) ? '' : 'd-none' ?>">
          Nenhuma pergunta cadastrada ainda.
        </div>
        <div id="qa-list" class="list-group list-group-flush">
          <?php foreach ($qa as $row): ?>
          <div class="list-group-item px-0 py-3" id="qa-row-<?= (int)$row['id'] ?>"
               data-active="<?= (int)$row['is_active'] ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div class="flex-grow-1">
                <div class="fw-semibold small mb-1">
                  <i class="bi bi-question-circle text-success me-1"></i><?= e($row['question']) ?>
                </div>
                <div class="small text-muted" style="white-space:pre-wrap"><?= e($row['answer']) ?></div>
              </div>
              <div class="d-flex gap-1 flex-shrink-0">
                <button class="btn btn-sm btn-outline-secondary qa-toggle-btn"
                        title="<?= $row['is_active'] ? 'Desativar' : 'Ativar' ?>"
                        onclick="qaToggle(<?= (int)$row['id'] ?>)">
                  <i class="bi bi-<?= $row['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        title="Remover" onclick="qaDelete(<?= (int)$row['id'] ?>)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle text-success"></i> Nova entrada
      </div>
      <div class="card-body">
        <form id="qa-form" novalidate>
          <?= csrf_field() ?>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Pergunta</label>
            <input type="text" name="question" class="form-control" maxlength="500"
                   placeholder="Como faço para…">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Resposta</label>
            <textarea name="answer" class="form-control" rows="4"
                      placeholder="Para fazer isso, você deve…"></textarea>
          </div>
          <button type="submit" class="btn btn-success fw-semibold w-100" id="btn-qa-save">
            <span class="spinner-border spinner-border-sm d-none me-1" id="qa-save-spin"></span>
            <i class="bi bi-plus-lg me-1"></i> Cadastrar
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

</div><!-- /tab-qa -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: Documentos
═══════════════════════════════════════════════════════════ -->
<div id="tab-docs" <?= $tab !== 'docs' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Documentos</h5>
  <small class="text-muted">Envie arquivos para que a IA consulte como referência</small>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-folder-fill text-primary"></i> Arquivos enviados
      </div>
      <div class="card-body">
        <div id="doc-empty" class="text-center text-muted py-4 small <?= empty($docs) ? '' : 'd-none' ?>">
          Nenhum documento enviado.
        </div>
        <div id="doc-list" class="list-group list-group-flush">
          <?php foreach ($docs as $d):
            $ext = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
            $icoMap = [
              'pdf'      => ['bi-file-earmark-pdf-fill', '#dc2626'],
              'doc'      => ['bi-file-earmark-word-fill', '#2563eb'],
              'docx'     => ['bi-file-earmark-word-fill', '#2563eb'],
              'md'       => ['bi-markdown-fill', '#475569'],
              'markdown' => ['bi-markdown-fill', '#475569'],
            ];
            $ico = $icoMap[$ext] ?? ['bi-file-earmark', '#94a3b8'];
          ?>
          <div class="list-group-item px-0 py-2 d-flex align-items-center gap-2"
               id="doc-row-<?= (int)$d['id'] ?>">
            <i class="bi <?= $ico[0] ?>" style="color:<?= $ico[1] ?>;font-size:1.4rem"></i>
            <div class="flex-grow-1 min-w-0">
              <div class="small fw-semibold text-truncate"><?= e($d['original_name']) ?></div>
              <div class="text-muted" style="font-size:.7rem">
                <?= fmtSize((int)$d['size_bytes']) ?> ·
                <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
              </div>
            </div>
            <button class="btn btn-sm btn-outline-danger flex-shrink-0"
                    onclick="docDelete(<?= (int)$d['id'] ?>)" title="Remover">
              <i class="bi bi-trash"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-cloud-upload-fill text-primary"></i> Enviar arquivo
      </div>
      <div class="card-body">
        <form id="doc-form" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="file" name="document" id="doc-input"
                 class="form-control mb-3"
                 accept=".pdf,.md,.doc,.docx,application/pdf,text/markdown">
          <button type="submit" class="btn btn-primary fw-semibold w-100" id="btn-doc-upload">
            <span class="spinner-border spinner-border-sm d-none me-1" id="doc-spin"></span>
            <i class="bi bi-upload me-1"></i> Enviar
          </button>
          <small class="text-muted d-block mt-3">
            Formatos aceitos: PDF, MD, DOC e DOCX.<br>
            Limite de 25 MB por arquivo.
          </small>
        </form>
      </div>
    </div>
  </div>
</div>

</div><!-- /tab-docs -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: Site
═══════════════════════════════════════════════════════════ -->
<div id="tab-site" <?= $tab !== 'site' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">URLs de Sites</h5>
  <small class="text-muted">Páginas web que servirão como base de conhecimento para a IA</small>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-globe2 text-info"></i> URLs cadastradas
      </div>
      <div class="card-body">
        <form id="site-form" novalidate class="mb-4">
          <?= csrf_field() ?>
          <div class="row g-2">
            <div class="col-md-7">
              <label class="form-label small fw-semibold">URL da página</label>
              <input type="url" name="url" class="form-control"
                     placeholder="https://www.exemplo.com.br/sobre">
              <div class="invalid-feedback" id="err-site-url"></div>
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-semibold">Título (opcional)</label>
              <input type="text" name="title" class="form-control" maxlength="255"
                     placeholder="Sobre nós">
            </div>
          </div>
          <button type="submit" class="btn btn-info text-white btn-sm fw-semibold mt-3" id="btn-site-save">
            <span class="spinner-border spinner-border-sm d-none me-1" id="site-save-spin"></span>
            <i class="bi bi-plus-lg me-1"></i> Cadastrar URL
          </button>
        </form>

        <div id="site-empty" class="text-center text-muted py-4 small <?= empty($sites) ? '' : 'd-none' ?>">
          Nenhuma URL cadastrada.
        </div>
        <div id="site-list" class="list-group list-group-flush">
          <?php foreach ($sites as $s): ?>
          <div class="list-group-item px-0 py-2 d-flex align-items-center gap-2"
               id="site-row-<?= (int)$s['id'] ?>">
            <i class="bi bi-globe2 text-info" style="font-size:1.2rem"></i>
            <div class="flex-grow-1 min-w-0">
              <?php if (!empty($s['title'])): ?>
              <div class="small fw-semibold text-truncate"><?= e($s['title']) ?></div>
              <?php endif; ?>
              <a href="<?= e($s['url']) ?>" target="_blank" class="small text-truncate d-block"
                 style="font-size:.78rem"><?= e($s['url']) ?></a>
            </div>
            <button class="btn btn-sm btn-outline-danger flex-shrink-0"
                    onclick="siteDelete(<?= (int)$s['id'] ?>)" title="Remover">
              <i class="bi bi-trash"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /tab-site -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: Testar
═══════════════════════════════════════════════════════════ -->
<div id="tab-testar" <?= $tab !== 'testar' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Testar agente</h5>
  <small class="text-muted">Conversa de teste usando a Persona, Q&A, Documentos e Sites configurados</small>
</div>

<div class="row g-4">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-chat-dots-fill text-warning"></i> Conversa
      </div>
      <div class="card-body">
        <div id="chat-thread"
             style="min-height:320px;max-height:520px;overflow-y:auto;padding:8px 4px;background:#f8fafc;border-radius:8px;">
          <div class="text-center text-muted py-5 small" id="chat-empty">
            <i class="bi bi-chat-square-text" style="font-size:2rem;opacity:.3"></i>
            <div class="mt-2">Envie uma mensagem para testar o agente</div>
          </div>
        </div>
        <form id="test-form" class="mt-3" novalidate>
          <?= csrf_field() ?>
          <div class="input-group">
            <input type="text" name="message" id="test-input" class="form-control"
                   placeholder="Digite sua pergunta…" autocomplete="off">
            <button type="submit" class="btn btn-warning fw-semibold" id="btn-test-send">
              <span class="spinner-border spinner-border-sm d-none me-1" id="test-spin"></span>
              <i class="bi bi-send-fill me-1"></i> Enviar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-3">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-info"></i> Como funciona
      </div>
      <div class="card-body small text-muted">
        Cada mensagem é enviada para o provider de IA <strong>ativo</strong>
        em <a href="<?= url('admin/settings?tab=ia') ?>">Configurações → IA</a>
        com o seguinte contexto:
        <ul class="ps-3 mt-2 mb-0" style="line-height:1.7;">
          <li>Persona (system prompt)</li>
          <li>Perguntas e respostas ativas</li>
          <li>Texto extraído dos documentos</li>
          <li>Conteúdo das páginas cadastradas</li>
        </ul>
        <hr>
        <small>Cada teste consome créditos da sua conta no provider.</small>
      </div>
    </div>
  </div>
</div>

</div><!-- /tab-testar -->


<script>
var AI_BASE = '<?= url('admin/ai-config') ?>';
var AI_CSRF = '<?= \Core\CSRF::token() ?>';

function aiPost(url, body) {
  return fetch(url, {
    method: 'POST',
    body: body,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  }).then(r => r.json());
}

function aiToast(msg, type) {
  if (window.Toast && typeof Toast.show === 'function') {
    Toast.show(msg, type || 'success');
  } else {
    alert(msg);
  }
}

// ── Persona ───────────────────────────────────────────────────
document.getElementById('persona-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-persona-save');
  var spin = document.getElementById('persona-save-spin');
  btn.disabled = true; spin.classList.remove('d-none');
  aiPost(AI_BASE + '/persona', new FormData(this)).then(function (res) {
    btn.disabled = false; spin.classList.add('d-none');
    aiToast(res.message, res.success ? 'success' : 'danger');
  });
});

// ── Helpers ───────────────────────────────────────────────────
function escapeHtml(s) {
  return String(s || '').replace(/[&<>"']/g, function (c) {
    return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
  });
}
function fmtSize(bytes) {
  if (bytes < 1024)         return bytes + ' B';
  if (bytes < 1048576)      return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1048576).toFixed(2) + ' MB';
}
function fmtDate(iso) {
  if (!iso) return '';
  var d = new Date(iso.replace(' ', 'T'));
  if (isNaN(d)) return '';
  return d.toLocaleDateString('pt-BR') + ' ' +
         d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
}
function ensureEmptyHidden(emptyId) {
  var el = document.getElementById(emptyId);
  if (el) el.classList.add('d-none');
}
function showEmptyIfEmpty(listId, emptyId) {
  var list  = document.getElementById(listId);
  var empty = document.getElementById(emptyId);
  if (list && empty && !list.querySelector('.list-group-item')) {
    empty.classList.remove('d-none');
  }
}

// ── Q&A ───────────────────────────────────────────────────────
function qaRowHtml(row) {
  var icon = row.is_active ? 'eye' : 'eye-slash';
  var ttl  = row.is_active ? 'Desativar' : 'Ativar';
  return '<div class="list-group-item px-0 py-3" id="qa-row-' + row.id + '"'
       + ' data-active="' + (row.is_active ? 1 : 0) + '">'
       + '<div class="d-flex justify-content-between align-items-start gap-2">'
       +   '<div class="flex-grow-1">'
       +     '<div class="fw-semibold small mb-1">'
       +       '<i class="bi bi-question-circle text-success me-1"></i>' + escapeHtml(row.question)
       +     '</div>'
       +     '<div class="small text-muted" style="white-space:pre-wrap">' + escapeHtml(row.answer) + '</div>'
       +   '</div>'
       +   '<div class="d-flex gap-1 flex-shrink-0">'
       +     '<button class="btn btn-sm btn-outline-secondary qa-toggle-btn" title="' + ttl + '" onclick="qaToggle(' + row.id + ')">'
       +       '<i class="bi bi-' + icon + '"></i></button>'
       +     '<button class="btn btn-sm btn-outline-danger" title="Remover" onclick="qaDelete(' + row.id + ')">'
       +       '<i class="bi bi-trash"></i></button>'
       +   '</div>'
       + '</div></div>';
}

document.getElementById('qa-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-qa-save');
  var spin = document.getElementById('qa-save-spin');
  btn.disabled = true; spin.classList.remove('d-none');
  aiPost(AI_BASE + '/qa', new FormData(this)).then(function (res) {
    btn.disabled = false; spin.classList.add('d-none');
    aiToast(res.message, res.success ? 'success' : 'danger');
    if (res.success) {
      var list = document.getElementById('qa-list');
      list.insertAdjacentHTML('afterbegin', qaRowHtml(res.data));
      ensureEmptyHidden('qa-empty');
      document.getElementById('qa-form').reset();
    }
  });
});

function qaDelete(id) {
  if (!confirm('Remover esta pergunta?')) return;
  var fd = new FormData(); fd.append('_csrf_token', AI_CSRF);
  aiPost(AI_BASE + '/qa/' + id + '/delete', fd).then(function (res) {
    aiToast(res.message, res.success ? 'success' : 'danger');
    if (res.success) {
      document.getElementById('qa-row-' + id)?.remove();
      showEmptyIfEmpty('qa-list', 'qa-empty');
    }
  });
}

function qaToggle(id) {
  var fd = new FormData(); fd.append('_csrf_token', AI_CSRF);
  aiPost(AI_BASE + '/qa/' + id + '/toggle', fd).then(function (res) {
    if (!res.success) { aiToast(res.message, 'danger'); return; }
    var row = document.getElementById('qa-row-' + id);
    if (!row) return;
    var active = res.data?.is_active ? 1 : 0;
    row.dataset.active = active;
    var btn = row.querySelector('.qa-toggle-btn');
    var ico = btn?.querySelector('i');
    if (btn && ico) {
      ico.className = 'bi bi-' + (active ? 'eye' : 'eye-slash');
      btn.title = active ? 'Desativar' : 'Ativar';
    }
    aiToast(active ? 'Ativada.' : 'Desativada.', 'success');
  });
}

// ── Documents ─────────────────────────────────────────────────
function docIcon(filename) {
  var ext = (filename.split('.').pop() || '').toLowerCase();
  var map = {
    pdf:  ['bi-file-earmark-pdf-fill', '#dc2626'],
    doc:  ['bi-file-earmark-word-fill', '#2563eb'],
    docx: ['bi-file-earmark-word-fill', '#2563eb'],
    md:   ['bi-markdown-fill', '#475569'],
  };
  return map[ext] || ['bi-file-earmark', '#94a3b8'];
}
function docRowHtml(row) {
  var ico = docIcon(row.original_name);
  return '<div class="list-group-item px-0 py-2 d-flex align-items-center gap-2" id="doc-row-' + row.id + '">'
       + '<i class="bi ' + ico[0] + '" style="color:' + ico[1] + ';font-size:1.4rem"></i>'
       + '<div class="flex-grow-1 min-w-0">'
       +   '<div class="small fw-semibold text-truncate">' + escapeHtml(row.original_name) + '</div>'
       +   '<div class="text-muted" style="font-size:.7rem">'
       +     fmtSize(row.size_bytes) + ' · ' + escapeHtml(fmtDate(row.created_at))
       +   '</div>'
       + '</div>'
       + '<button class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="docDelete(' + row.id + ')" title="Remover">'
       +   '<i class="bi bi-trash"></i></button>'
       + '</div>';
}

document.getElementById('doc-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-doc-upload');
  var spin = document.getElementById('doc-spin');
  var inp  = document.getElementById('doc-input');
  if (!inp.files.length) {
    aiToast('Selecione um arquivo para enviar.', 'warning');
    return;
  }
  btn.disabled = true; spin.classList.remove('d-none');
  aiPost(AI_BASE + '/docs', new FormData(this)).then(function (res) {
    btn.disabled = false; spin.classList.add('d-none');
    aiToast(res.message, res.success ? 'success' : 'danger');
    if (res.success) {
      var list = document.getElementById('doc-list');
      list.insertAdjacentHTML('afterbegin', docRowHtml(res.data));
      ensureEmptyHidden('doc-empty');
      document.getElementById('doc-form').reset();
    }
  });
});

function docDelete(id) {
  if (!confirm('Remover este documento?')) return;
  var fd = new FormData(); fd.append('_csrf_token', AI_CSRF);
  aiPost(AI_BASE + '/docs/' + id + '/delete', fd).then(function (res) {
    aiToast(res.message, res.success ? 'success' : 'danger');
    if (res.success) {
      document.getElementById('doc-row-' + id)?.remove();
      showEmptyIfEmpty('doc-list', 'doc-empty');
    }
  });
}

// ── Sites ─────────────────────────────────────────────────────
function siteRowHtml(row) {
  var titleHtml = row.title
    ? '<div class="small fw-semibold text-truncate">' + escapeHtml(row.title) + '</div>'
    : '';
  return '<div class="list-group-item px-0 py-2 d-flex align-items-center gap-2" id="site-row-' + row.id + '">'
       + '<i class="bi bi-globe2 text-info" style="font-size:1.2rem"></i>'
       + '<div class="flex-grow-1 min-w-0">'
       +   titleHtml
       +   '<a href="' + escapeHtml(row.url) + '" target="_blank" class="small text-truncate d-block" style="font-size:.78rem">'
       +     escapeHtml(row.url) + '</a>'
       + '</div>'
       + '<button class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="siteDelete(' + row.id + ')" title="Remover">'
       +   '<i class="bi bi-trash"></i></button>'
       + '</div>';
}

document.getElementById('site-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-site-save');
  var spin = document.getElementById('site-save-spin');
  var err  = document.getElementById('err-site-url');
  err.textContent = ''; err.style.display = 'none';
  btn.disabled = true; spin.classList.remove('d-none');
  aiPost(AI_BASE + '/sites', new FormData(this)).then(function (res) {
    btn.disabled = false; spin.classList.add('d-none');
    if (res.success) {
      aiToast(res.message, 'success');
      var list = document.getElementById('site-list');
      list.insertAdjacentHTML('afterbegin', siteRowHtml(res.data));
      ensureEmptyHidden('site-empty');
      document.getElementById('site-form').reset();
    } else {
      aiToast(res.message, 'danger');
      if (res.errors && res.errors.url) {
        err.textContent = res.errors.url[0]; err.style.display = 'block';
      }
    }
  });
});

function siteDelete(id) {
  if (!confirm('Remover esta URL?')) return;
  var fd = new FormData(); fd.append('_csrf_token', AI_CSRF);
  aiPost(AI_BASE + '/sites/' + id + '/delete', fd).then(function (res) {
    aiToast(res.message, res.success ? 'success' : 'danger');
    if (res.success) {
      document.getElementById('site-row-' + id)?.remove();
      showEmptyIfEmpty('site-list', 'site-empty');
    }
  });
}

// ── Test chat ────────────────────────────────────────────────
function chatBubble(role, text) {
  var thread = document.getElementById('chat-thread');
  var empty  = document.getElementById('chat-empty');
  if (empty) empty.remove();

  var wrap = document.createElement('div');
  wrap.className = 'd-flex mb-2 ' + (role === 'user' ? 'justify-content-end' : 'justify-content-start');

  var bubble = document.createElement('div');
  bubble.style.maxWidth   = '78%';
  bubble.style.padding    = '8px 12px';
  bubble.style.borderRadius = '12px';
  bubble.style.fontSize   = '.88rem';
  bubble.style.whiteSpace = 'pre-wrap';
  bubble.style.wordBreak  = 'break-word';

  if (role === 'user') {
    bubble.style.background = '#3b82f6';
    bubble.style.color = '#fff';
  } else if (role === 'loading') {
    bubble.style.background = '#e2e8f0';
    bubble.style.color = '#475569';
    bubble.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Pensando…';
  } else if (role === 'error') {
    bubble.style.background = '#fee2e2';
    bubble.style.color = '#991b1b';
  } else {
    bubble.style.background = '#fff';
    bubble.style.border = '1px solid #e2e8f0';
    bubble.style.color = '#0f172a';
  }
  if (role !== 'loading') bubble.textContent = text;

  wrap.appendChild(bubble);
  thread.appendChild(wrap);
  thread.scrollTop = thread.scrollHeight;
  return wrap;
}

document.getElementById('test-form')?.addEventListener('submit', function (e) {
  e.preventDefault();
  var input = document.getElementById('test-input');
  var btn   = document.getElementById('btn-test-send');
  var spin  = document.getElementById('test-spin');
  var msg   = (input.value || '').trim();
  if (!msg) return;

  chatBubble('user', msg);
  input.value = '';
  btn.disabled = true; spin.classList.remove('d-none');
  var loadingNode = chatBubble('loading');

  var fd = new FormData();
  fd.append('_csrf_token', AI_CSRF);
  fd.append('message', msg);

  aiPost(AI_BASE + '/test', fd).then(function (res) {
    btn.disabled = false; spin.classList.add('d-none');
    loadingNode.remove();
    if (res.success) {
      chatBubble('assistant', res.data?.reply || '(vazio)');
    } else {
      chatBubble('error', res.message);
    }
    input.focus();
  }).catch(function () {
    btn.disabled = false; spin.classList.add('d-none');
    loadingNode.remove();
    chatBubble('error', 'Erro de conexão.');
  });
});
</script>

<?php \Core\View::endSection() ?>
