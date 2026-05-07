<?php \Core\View::section('title') ?>Conversas Ativas<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Conversas Ativas<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Conversas Ativas</h5>
    <small class="text-muted">Conversas outbound iniciadas via template WhatsApp</small>
  </div>
  <button class="btn btn-primary d-flex align-items-center gap-2"
          data-bs-toggle="modal" data-bs-target="#newConvModal">
    <i class="bi bi-plus-lg"></i> Nova Conversa
  </button>
</div>

<!-- Filter pills -->
<ul class="nav nav-pills mb-3 gap-1">
  <li class="nav-item">
    <a href="<?= url('admin/conversations/active') ?>"
       class="nav-link <?= $activeCategory === '' ? 'active' : '' ?>">
      Todas
    </a>
  </li>
  <?php foreach ([
    'marketing'      => ['Megaphone-fill', 'Marketing'],
    'utility'        => ['Tools', 'Utilitário'],
    'service'        => ['Headset', 'Serviço'],
    'authentication' => ['Shield-check', 'Autenticação'],
  ] as $cat => [$icon, $label]): ?>
  <li class="nav-item">
    <a href="<?= url('admin/conversations/active') ?>?category=<?= $cat ?>"
       class="nav-link <?= $activeCategory === $cat ? 'active' : '' ?>">
      <i class="bi bi-<?= strtolower($icon) ?> me-1"></i><?= $label ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<!-- Conversations table -->
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th class="ps-3">Contato</th>
          <th>Categoria</th>
          <th>Última mensagem</th>
          <th>Data</th>
          <th class="text-end pe-3">Status</th>
        </tr>
      </thead>
      <tbody id="conv-tbody">
        <?php if (empty($conversations)): ?>
        <tr id="conv-empty-row">
          <td colspan="5" class="text-center text-muted py-5">
            <i class="bi bi-chat-square-dots fs-2 d-block mb-2 opacity-50"></i>
            Nenhuma conversa ativa encontrada.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($conversations as $conv): ?>
        <tr>
          <td class="ps-3">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                   style="width:36px;height:36px;flex-shrink:0;font-size:.8rem;background:<?= convAvatarColor($conv['contact_name'] ?? '') ?>;">
                <?= strtoupper(substr($conv['contact_name'] ?? '?', 0, 1)) ?>
              </div>
              <div>
                <div class="fw-semibold small"><?= e($conv['contact_name'] ?? 'Desconhecido') ?></div>
                <div class="text-muted" style="font-size:.72rem;"><?= e($conv['contact_phone'] ?? '') ?></div>
              </div>
            </div>
          </td>
          <td><?= categoryBadge($conv['conversation_category'] ?? '') ?></td>
          <td>
            <div class="small text-truncate" style="max-width:240px;">
              <?= e($conv['last_message'] ?? '—') ?>
            </div>
          </td>
          <td class="small text-muted">
            <?= !empty($conv['last_message_at']) ? date('d/m/Y H:i', strtotime($conv['last_message_at'])) : '—' ?>
          </td>
          <td class="text-end pe-3">
            <?= convStatusBadge($conv['status'] ?? '') ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ================================================================
     MODAL: Nova Conversa
================================================================ -->
<div class="modal fade" id="newConvModal" tabindex="-1" aria-labelledby="newConvModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="newConvModalLabel">
          <i class="bi bi-whatsapp text-success me-2"></i>Nova Conversa
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="conv-modal-alert"></div>
        <form id="conv-form" novalidate>
          <?= csrf_field() ?>

          <!-- Step 1: Contact search -->
          <div class="mb-3">
            <label class="form-label fw-semibold small">Buscar Contato <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="contact-search" class="form-control"
                     placeholder="Nome ou telefone..." autocomplete="off">
            </div>
            <div id="contact-results" class="border rounded mt-1 bg-white" style="display:none;max-height:200px;overflow-y:auto;"></div>
            <div id="selected-contact" class="mt-2" style="display:none;">
              <div class="alert alert-success py-2 d-flex align-items-center justify-content-between mb-0">
                <span><i class="bi bi-person-check-fill me-2"></i><span id="sel-contact-name"></span> — <span id="sel-contact-phone" class="text-muted small"></span></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearContactSelection()">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <input type="hidden" name="contact_ids[]" id="sel-contact-id">
            </div>
          </div>

          <!-- Step 2: Template select -->
          <div class="mb-3">
            <label class="form-label fw-semibold small">Template Aprovado <span class="text-danger">*</span></label>
            <select name="template_id" id="conv-template-id" class="form-select" onchange="onTemplateChange()">
              <option value="">— Selecione um template —</option>
              <?php foreach ($approvedTemplates as $tpl): ?>
              <option value="<?= $tpl['id'] ?>"
                      data-category="<?= e($tpl['category']) ?>"
                      data-language="<?= e($tpl['language']) ?>"
                      data-body="<?= e($tpl['body_text']) ?>"
                      data-variables="<?= e(json_encode($tpl['variables_decoded'])) ?>">
                [<?= e(ucfirst($tpl['category'])) ?>] <?= e($tpl['name']) ?> (<?= e($tpl['language']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Step 3: Variables -->
          <div id="variables-section" style="display:none;">
            <label class="form-label fw-semibold small">Variáveis</label>
            <div id="variables-inputs" class="row g-2"></div>
          </div>

          <!-- Step 4: Preview -->
          <div id="conv-preview-section" class="mt-3" style="display:none;">
            <label class="form-label fw-semibold small text-muted">Pré-visualização</label>
            <div class="p-3 rounded" style="background:#e5ddd5;font-size:.87rem;">
              <div class="p-3 rounded shadow-sm" style="background:#fff;max-width:360px;">
                <div id="conv-preview-body" style="white-space:pre-wrap;"></div>
              </div>
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-semibold px-4" id="btn-send-conv" onclick="sendConversation()">
          <span class="spinner-border spinner-border-sm d-none me-2" id="conv-send-spinner"></span>
          <i class="bi bi-send me-1"></i> Enviar
        </button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const CONV_URLS = {
  send:          '<?= url('admin/conversations/send') ?>',
  searchContact: '<?= url('admin/conversations/search-contact') ?>',
};

let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function () {
  // Reset form when modal closes
  document.getElementById('newConvModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('conv-form').reset();
    document.getElementById('conv-modal-alert').innerHTML = '';
    document.getElementById('contact-results').style.display = 'none';
    document.getElementById('selected-contact').style.display = 'none';
    document.getElementById('variables-section').style.display = 'none';
    document.getElementById('conv-preview-section').style.display = 'none';
    document.getElementById('variables-inputs').innerHTML = '';
  });

  // Contact search input
  document.getElementById('contact-search').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
      document.getElementById('contact-results').style.display = 'none';
      return;
    }
    searchTimeout = setTimeout(() => doContactSearch(q), 300);
  });

  // Close contact results when clicking outside
  document.addEventListener('click', function (e) {
    if (!document.getElementById('newConvModal').contains(e.target)) return;
    if (!e.target.closest('#contact-results') && !e.target.closest('#contact-search')) {
      document.getElementById('contact-results').style.display = 'none';
    }
  });
});

// ---- Contact search ----
function doContactSearch(q) {
  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');
  fd.append('q', q);

  fetch(CONV_URLS.searchContact, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      const container = document.getElementById('contact-results');
      const contacts  = res.data?.contacts || [];

      if (contacts.length === 0) {
        container.innerHTML = '<div class="p-3 text-muted small">Nenhum contato encontrado.</div>';
        container.style.display = 'block';
        return;
      }

      container.innerHTML = contacts.map(c => `
        <button type="button" class="list-group-item list-group-item-action border-0 d-flex align-items-center gap-2 py-2 px-3"
                onclick="selectContact(${c.id}, ${JSON.stringify(c.name)}, ${JSON.stringify(c.phone)})">
          <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
               style="width:32px;height:32px;flex-shrink:0;font-size:.75rem;background:#6366f1;">
            ${escHtml(c.name.charAt(0).toUpperCase())}
          </div>
          <div>
            <div class="fw-semibold small">${escHtml(c.name)}</div>
            <div class="text-muted" style="font-size:.72rem;">${escHtml(c.phone)}</div>
          </div>
        </button>
      `).join('');
      container.style.display = 'block';
    })
    .catch(() => {});
}

function selectContact(id, name, phone) {
  document.getElementById('sel-contact-id').value    = id;
  document.getElementById('sel-contact-name').textContent  = name;
  document.getElementById('sel-contact-phone').textContent = phone;
  document.getElementById('selected-contact').style.display = 'block';
  document.getElementById('contact-results').style.display  = 'none';
  document.getElementById('contact-search').value = '';
}

function clearContactSelection() {
  document.getElementById('sel-contact-id').value = '';
  document.getElementById('selected-contact').style.display = 'none';
}

// ---- Template selection ----
function onTemplateChange() {
  const sel  = document.getElementById('conv-template-id');
  const opt  = sel.options[sel.selectedIndex];

  if (!opt || !opt.value) {
    document.getElementById('variables-section').style.display   = 'none';
    document.getElementById('conv-preview-section').style.display = 'none';
    return;
  }

  const vars    = JSON.parse(opt.dataset.variables || '[]');
  const body    = opt.dataset.body || '';

  // Build variable inputs
  const wrap = document.getElementById('variables-inputs');
  wrap.innerHTML = '';

  if (vars.length > 0) {
    vars.forEach(function (idx) {
      const col = document.createElement('div');
      col.className = 'col-md-6';
      col.innerHTML = `
        <label class="form-label small fw-semibold">Variável {{${idx}}}</label>
        <input type="text" name="variables[${idx}]"
               class="form-control var-input"
               data-placeholder="{{${idx}}}"
               placeholder="Valor para {{${idx}}}"
               oninput="updateConvPreview()">
      `;
      wrap.appendChild(col);
    });
    document.getElementById('variables-section').style.display = 'block';
  } else {
    document.getElementById('variables-section').style.display = 'none';
  }

  updateConvPreview();
}

function updateConvPreview() {
  const sel  = document.getElementById('conv-template-id');
  const opt  = sel.options[sel.selectedIndex];
  if (!opt || !opt.value) return;

  let body = opt.dataset.body || '';

  document.querySelectorAll('.var-input').forEach(function (inp) {
    const ph  = inp.dataset.placeholder;
    const val = inp.value || ph;
    body = body.split(ph).join(val);
  });

  document.getElementById('conv-preview-body').textContent = body;
  document.getElementById('conv-preview-section').style.display = 'block';
}

// ---- Send conversation ----
function sendConversation() {
  const contactId  = document.getElementById('sel-contact-id').value;
  const templateId = document.getElementById('conv-template-id').value;

  if (!contactId) {
    showConvAlert('Selecione um contato.', 'warning');
    return;
  }
  if (!templateId) {
    showConvAlert('Selecione um template.', 'warning');
    return;
  }

  const btn  = document.getElementById('btn-send-conv');
  const spin = document.getElementById('conv-send-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');

  const fd = new FormData(document.getElementById('conv-form'));
  // Ensure contact_ids[] is set (hidden field already named contact_ids[])

  fetch(CONV_URLS.send, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');

      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('newConvModal')).hide();
        Toast.show(res.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showConvAlert(res.message, 'danger');
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      Toast.show('Erro de conexão.', 'error');
    });
}

function showConvAlert(msg, type) {
  document.getElementById('conv-modal-alert').innerHTML =
    `<div class="alert alert-${type} py-2 small d-flex gap-2 mb-2">
       <i class="bi bi-exclamation-triangle-fill mt-1"></i><span>${escHtml(msg)}</span>
     </div>`;
}

function escHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>

<?php
// ---- View Helpers ----
function categoryBadge(string $cat): string
{
    $map = [
        'marketing'      => ['primary',   'megaphone-fill', 'Marketing'],
        'utility'        => ['info',      'tools',          'Utilitário'],
        'service'        => ['secondary', 'headset',        'Serviço'],
        'authentication' => ['dark',      'shield-check',   'Autenticação'],
    ];
    [$cls, $icon, $label] = $map[$cat] ?? ['light text-dark border', 'tag', ucfirst($cat)];
    return "<span class=\"badge bg-{$cls} d-inline-flex align-items-center gap-1\">
              <i class=\"bi bi-{$icon}\"></i>{$label}</span>";
}

function convStatusBadge(string $status): string
{
    $map = [
        'waiting'     => ['warning text-dark', 'Aguardando'],
        'in_progress' => ['primary',           'Em andamento'],
        'finished'    => ['secondary',         'Encerrado'],
        'bot'         => ['success',           'Bot ativo'],
    ];
    [$cls, $label] = $map[$status] ?? ['light text-dark border', ucfirst($status)];
    return "<span class=\"badge bg-{$cls}\">{$label}</span>";
}

function convAvatarColor(string $name): string
{
    $colors = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#0ea5e9'];
    return $colors[ord($name[0] ?? 'A') % count($colors)];
}
?>
