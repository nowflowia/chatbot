<?php \Core\View::section('title') ?>WhatsApp<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>WhatsApp<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Templates WhatsApp</h5>
    <small class="text-muted">Gerencie os templates de mensagem para campanhas e notificações</small>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="syncFromMeta()" id="btn-sync">
      <span class="spinner-border spinner-border-sm d-none" id="sync-spinner"></span>
      <i class="bi bi-arrow-repeat" id="sync-icon"></i> Sincronizar Meta
    </button>
    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#templateModal">
      <i class="bi bi-plus-lg"></i> Novo Template
    </button>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="waTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" id="tab-marketing" data-bs-toggle="tab"
            data-bs-target="#pane-marketing" type="button">
      <i class="bi bi-megaphone-fill me-1"></i> Marketing
      <span class="badge bg-secondary ms-1" id="count-marketing"><?= count($grouped['marketing']) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="tab-utility" data-bs-toggle="tab"
            data-bs-target="#pane-utility" type="button">
      <i class="bi bi-tools me-1"></i> Utilitário
      <span class="badge bg-secondary ms-1" id="count-utility"><?= count($grouped['utility']) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="tab-service" data-bs-toggle="tab"
            data-bs-target="#pane-service" type="button">
      <i class="bi bi-headset me-1"></i> Serviço
      <span class="badge bg-secondary ms-1" id="count-service"><?= count($grouped['service']) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="tab-authentication" data-bs-toggle="tab"
            data-bs-target="#pane-authentication" type="button">
      <i class="bi bi-shield-check me-1"></i> Autenticação
      <span class="badge bg-secondary ms-1" id="count-authentication"><?= count($grouped['authentication']) ?></span>
    </button>
  </li>
</ul>

<div class="tab-content" id="waTabContent">

<?php foreach (['marketing' => 'Marketing', 'utility' => 'Utilitário', 'service' => 'Serviço', 'authentication' => 'Autenticação'] as $catKey => $catLabel): ?>
  <div class="tab-pane fade <?= $catKey === 'marketing' ? 'show active' : '' ?>" id="pane-<?= $catKey ?>">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-<?= $catKey ?>">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Nome</th>
              <th>Idioma</th>
              <th>Status</th>
              <th>Criado em</th>
              <th class="text-end pe-3" width="160">Ações</th>
            </tr>
          </thead>
          <tbody id="tbody-<?= $catKey ?>">
            <?php if (empty($grouped[$catKey])): ?>
            <tr class="empty-row">
              <td colspan="5" class="text-center text-muted py-5">
                <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-50"></i>
                Nenhum template de <?= $catLabel ?>.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($grouped[$catKey] as $tpl): ?>
            <?= templateRow($tpl) ?>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endforeach; ?>

</div><!-- /tab-content -->

<!-- ================================================================
     MODAL: Novo Template
================================================================ -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="templateModalLabel">
          <i class="bi bi-whatsapp text-success me-2"></i>Novo Template
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="tpl-modal-alert"></div>
        <form id="template-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold small">
                Nome <span class="text-danger">*</span>
                <span class="text-muted fw-normal">(snake_case)</span>
              </label>
              <input type="text" name="name" id="f-name" class="form-control"
                     placeholder="meu_template_1"
                     pattern="[a-z0-9_]+"
                     title="Somente letras minúsculas, números e underscore">
              <div class="invalid-feedback" id="err-name"></div>
              <div class="form-text">Use apenas letras minúsculas, números e _ (ex: promo_maio_2024)</div>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold small">Categoria <span class="text-danger">*</span></label>
              <select name="category" id="f-category" class="form-select">
                <option value="marketing">Marketing</option>
                <option value="utility">Utilitário</option>
                <option value="service">Serviço</option>
                <option value="authentication">Autenticação</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold small">Idioma <span class="text-danger">*</span></label>
              <select name="language" id="f-language" class="form-select">
                <option value="pt_BR">Português (BR)</option>
                <option value="en_US">English (US)</option>
                <option value="es_ES">Español</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Cabeçalho</label>
              <select name="header_type" id="f-header_type" class="form-select" onchange="toggleHeaderText()">
                <option value="none">Nenhum</option>
                <option value="text">Texto</option>
              </select>
            </div>

            <div class="col-md-8" id="header-text-wrap" style="display:none;">
              <label class="form-label fw-semibold small">Texto do Cabeçalho</label>
              <input type="text" name="header_text" id="f-header_text" class="form-control"
                     placeholder="Título do cabeçalho">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">
                Corpo da Mensagem <span class="text-danger">*</span>
              </label>
              <textarea name="body_text" id="f-body_text" class="form-control" rows="5"
                        placeholder="Olá, {{1}}! Sua promoção está disponível até {{2}}."
                        oninput="updatePreview()"></textarea>
              <div class="invalid-feedback" id="err-body_text"></div>
              <div class="form-text">Use <code>{{1}}</code>, <code>{{2}}</code> etc. para variáveis dinâmicas.</div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Rodapé <span class="text-muted fw-normal">(máx. 60 caracteres)</span></label>
              <input type="text" name="footer_text" id="f-footer_text" class="form-control"
                     placeholder="Empresa Ltda. — Não responda."
                     maxlength="60">
            </div>

            <!-- Preview -->
            <div class="col-12" id="preview-wrap" style="display:none;">
              <label class="form-label fw-semibold small text-muted">Pré-visualização</label>
              <div class="p-3 rounded" style="background:#e5ddd5;font-size:.87rem;">
                <div class="p-3 rounded shadow-sm" style="background:#fff;max-width:340px;">
                  <div id="preview-header" class="fw-bold mb-1 small" style="display:none;"></div>
                  <div id="preview-body" class="mb-1" style="white-space:pre-wrap;"></div>
                  <div id="preview-footer" class="text-muted" style="font-size:.75rem;display:none;"></div>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btn-save-tpl" onclick="saveTemplate()">
          <span class="spinner-border spinner-border-sm d-none me-2" id="tpl-save-spinner"></span>
          Salvar Rascunho
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirm Delete
================================================================ -->
<div class="modal fade" id="deleteTplModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-trash-fill text-danger fs-5"></i>
        </div>
        <h6 class="fw-bold">Excluir template?</h6>
        <p class="text-muted small mb-0">Esta ação é irreversível.<br>
          O template <strong id="delete-tpl-name"></strong> será removido.</p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-confirm-delete-tpl">
          <span class="spinner-border spinner-border-sm d-none me-1" id="delete-tpl-spinner"></span>
          Excluir
        </button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const WA_URLS = {
  store:   '<?= url('admin/whatsapp/templates') ?>',
  delete:  '<?= url('admin/whatsapp/templates') ?>/',
  submit:  '<?= url('admin/whatsapp/templates') ?>/',
  sync:    '<?= url('admin/whatsapp/sync') ?>',
};

let deleteTplModal;

document.addEventListener('DOMContentLoaded', function () {
  deleteTplModal = new bootstrap.Modal(document.getElementById('deleteTplModal'));

  // Reset form when template modal is hidden
  document.getElementById('templateModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('template-form').reset();
    document.getElementById('tpl-modal-alert').innerHTML = '';
    document.getElementById('preview-wrap').style.display = 'none';
    document.getElementById('header-text-wrap').style.display = 'none';
    clearTplErrors();
  });
});

// ---- Header type toggle ----
function toggleHeaderText() {
  const type = document.getElementById('f-header_type').value;
  document.getElementById('header-text-wrap').style.display = type === 'text' ? 'flex' : 'none';
  updatePreview();
}

// ---- Live preview ----
function updatePreview() {
  const body   = document.getElementById('f-body_text').value;
  const header = document.getElementById('f-header_type').value === 'text'
                 ? (document.getElementById('f-header_text')?.value || '') : '';
  const footer = document.getElementById('f-footer_text').value;

  const wrap       = document.getElementById('preview-wrap');
  const previewB   = document.getElementById('preview-body');
  const previewH   = document.getElementById('preview-header');
  const previewF   = document.getElementById('preview-footer');

  if (!body && !header) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';

  previewB.textContent = body || '';

  if (header) {
    previewH.textContent    = header;
    previewH.style.display  = 'block';
  } else {
    previewH.style.display = 'none';
  }

  if (footer) {
    previewF.textContent   = footer;
    previewF.style.display = 'block';
  } else {
    previewF.style.display = 'none';
  }
}

// ---- Save Template ----
function saveTemplate() {
  clearTplErrors();
  const btn    = document.getElementById('btn-save-tpl');
  const spin   = document.getElementById('tpl-save-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');

  const fd = new FormData(document.getElementById('template-form'));

  fetch(WA_URLS.store, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');

      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
        Toast.show(res.message, 'success');

        const tpl = res.data?.template;
        if (tpl) {
          appendTemplateRow(tpl);
        }
      } else {
        document.getElementById('tpl-modal-alert').innerHTML =
          '<div class="alert alert-danger py-2 small d-flex gap-2"><i class="bi bi-exclamation-triangle-fill mt-1"></i><span>' + res.message + '</span></div>';
        if (res.errors) {
          Object.entries(res.errors).forEach(([field, msgs]) => {
            const el  = document.getElementById('err-' + field);
            const inp = document.getElementById('f-' + field);
            if (el)  { el.textContent = msgs[0]; el.style.display = 'block'; }
            if (inp) inp.classList.add('is-invalid');
          });
        }
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      Toast.show('Erro de conexão.', 'error');
    });
}

// ---- Append newly created template row ----
function appendTemplateRow(tpl) {
  const cat   = tpl.category || 'marketing';
  const tbody = document.getElementById('tbody-' + cat);
  if (!tbody) return;

  // Remove empty-state row if present
  const emptyRow = tbody.querySelector('.empty-row');
  if (emptyRow) emptyRow.remove();

  const date = tpl.created_at ? tpl.created_at.substring(0, 10).split('-').reverse().join('/') : '';
  const html = buildRowHtml(tpl, date);
  tbody.insertAdjacentHTML('afterbegin', html);

  // Update tab counter
  const countEl = document.getElementById('count-' + cat);
  if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;

  // Activate correct tab
  const tabBtn = document.getElementById('tab-' + cat);
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function buildRowHtml(tpl, date) {
  const langMap = { pt_BR: 'pt-BR', en_US: 'en-US', es_ES: 'es-ES' };
  const lang    = langMap[tpl.language] || tpl.language;
  return `<tr id="tpl-row-${tpl.id}">
    <td class="ps-3">
      <div class="fw-semibold small">${escHtml(tpl.name)}</div>
      <div class="text-muted" style="font-size:.72rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(tpl.body_text || '')}</div>
    </td>
    <td><span class="badge bg-light text-dark border small">${escHtml(lang)}</span></td>
    <td>${statusBadgeHtml('draft')}</td>
    <td class="small text-muted">${date}</td>
    <td class="text-end pe-3">
      <div class="d-flex gap-2 justify-content-end">
        <button class="btn btn-sm btn-outline-primary" onclick="submitToMeta(${tpl.id})" id="btn-submit-${tpl.id}" title="Solicitar aprovação Meta">
          <i class="bi bi-send"></i> Solicitar Meta
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteTpl(${tpl.id}, '${escHtml(tpl.name)}')" title="Excluir">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </td>
  </tr>`;
}

// ---- Submit to Meta ----
function submitToMeta(id) {
  const btn = document.getElementById('btn-submit-' + id);
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');

  fetch(WA_URLS.submit + id + '/submit', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      if (btn) { btn.disabled = false; }

      if (res.success) {
        Toast.show(res.message, 'success');
        const newStatus = res.data?.status || 'pending';
        const badge     = document.querySelector('#tpl-row-' + id + ' .status-badge');
        if (badge) badge.outerHTML = statusBadgeHtml(newStatus);
        // Hide submit button if no longer draft
        if (newStatus !== 'draft' && newStatus !== 'rejected') {
          if (btn) btn.style.display = 'none';
        } else {
          if (btn) btn.innerHTML = '<i class="bi bi-send"></i> Solicitar Meta';
        }
      } else {
        if (btn) btn.innerHTML = '<i class="bi bi-send"></i> Solicitar Meta';
        Toast.show(res.message, 'error');
      }
    })
    .catch(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Solicitar Meta'; }
      Toast.show('Erro de conexão.', 'error');
    });
}

// ---- Confirm Delete ----
function confirmDeleteTpl(id, name) {
  document.getElementById('delete-tpl-name').textContent = name;
  deleteTplModal.show();

  document.getElementById('btn-confirm-delete-tpl').onclick = function () {
    const btn  = this;
    const spin = document.getElementById('delete-tpl-spinner');
    btn.disabled = true;
    spin.classList.remove('d-none');

    const fd = new FormData();
    fd.append('_csrf_token', '<?= csrf_token() ?>');

    fetch(WA_URLS.delete + id + '/delete', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        spin.classList.add('d-none');
        deleteTplModal.hide();

        if (res.success) {
          const row = document.getElementById('tpl-row-' + id);
          if (row) {
            // Find category from the tab pane
            const pane = row.closest('.tab-pane');
            const cat  = pane ? pane.id.replace('pane-', '') : '';
            row.style.transition = 'opacity .4s';
            row.style.opacity    = '0';
            setTimeout(() => {
              row.remove();
              if (cat) {
                const countEl = document.getElementById('count-' + cat);
                if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent || '0') - 1);
              }
            }, 400);
          }
          Toast.show(res.message, 'success');
        } else {
          Toast.show(res.message, 'error');
        }
      })
      .catch(() => {
        btn.disabled = false;
        spin.classList.add('d-none');
        Toast.show('Erro de conexão.', 'error');
      });
  };
}

// ---- Sync from Meta ----
function syncFromMeta() {
  const btn  = document.getElementById('btn-sync');
  const icon = document.getElementById('sync-icon');
  const spin = document.getElementById('sync-spinner');
  btn.disabled = true;
  icon.style.display = 'none';
  spin.classList.remove('d-none');

  const fd = new FormData();
  fd.append('_csrf_token', '<?= csrf_token() ?>');

  fetch(WA_URLS.sync, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      icon.style.display = '';
      spin.classList.add('d-none');
      Toast.show(res.message, res.success ? 'success' : 'error');
      if (res.success && (res.data?.synced || 0) > 0) {
        setTimeout(() => location.reload(), 1200);
      }
    })
    .catch(() => {
      btn.disabled = false;
      icon.style.display = '';
      spin.classList.add('d-none');
      Toast.show('Erro de conexão.', 'error');
    });
}

// ---- Helpers ----
function statusBadgeHtml(status) {
  const map = {
    draft:    ['secondary', 'Rascunho'],
    pending:  ['warning text-dark', 'Pendente'],
    approved: ['success', 'Aprovado'],
    rejected: ['danger', 'Rejeitado'],
  };
  const [cls, label] = map[status] || map['draft'];
  return `<span class="badge bg-${cls} status-badge">${label}</span>`;
}

function escHtml(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}

function clearTplErrors() {
  document.querySelectorAll('#template-form .is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('#template-form .invalid-feedback').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}
</script>
<?php \Core\View::endSection() ?>

<?php
// ---- View Helpers ----
function templateRow(array $tpl): string
{
    $langMap = ['pt_BR' => 'pt-BR', 'en_US' => 'en-US', 'es_ES' => 'es-ES'];
    $lang    = $langMap[$tpl['language'] ?? ''] ?? ($tpl['language'] ?? '');

    $date = !empty($tpl['created_at'])
        ? date('d/m/Y', strtotime($tpl['created_at']))
        : '—';

    $statusMap = [
        'draft'    => ['secondary', 'Rascunho'],
        'pending'  => ['warning text-dark', 'Pendente'],
        'approved' => ['success', 'Aprovado'],
        'rejected' => ['danger', 'Rejeitado'],
    ];
    [$badgeCls, $badgeLabel] = $statusMap[$tpl['status'] ?? 'draft'] ?? ['secondary', 'Rascunho'];

    $isDraftOrRejected = in_array($tpl['status'] ?? '', ['draft', 'rejected']);
    $submitBtn = $isDraftOrRejected
        ? '<button class="btn btn-sm btn-outline-primary" onclick="submitToMeta(' . $tpl['id'] . ')" id="btn-submit-' . $tpl['id'] . '" title="Solicitar aprovação Meta">
             <i class="bi bi-send"></i> Solicitar Meta
           </button>'
        : '';

    $name     = e($tpl['name'] ?? '');
    $bodyText = e(mb_substr($tpl['body_text'] ?? '', 0, 80));

    return "
    <tr id=\"tpl-row-{$tpl['id']}\">
      <td class=\"ps-3\">
        <div class=\"fw-semibold small\">{$name}</div>
        <div class=\"text-muted\" style=\"font-size:.72rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;\">{$bodyText}</div>
      </td>
      <td><span class=\"badge bg-light text-dark border small\">{$lang}</span></td>
      <td><span class=\"badge bg-{$badgeCls} status-badge\">{$badgeLabel}</span></td>
      <td class=\"small text-muted\">{$date}</td>
      <td class=\"text-end pe-3\">
        <div class=\"d-flex gap-2 justify-content-end\">
          {$submitBtn}
          <button class=\"btn btn-sm btn-outline-danger\" onclick=\"confirmDeleteTpl({$tpl['id']}, '{$name}')\" title=\"Excluir\">
            <i class=\"bi bi-trash\"></i>
          </button>
        </div>
      </td>
    </tr>";
}
?>
