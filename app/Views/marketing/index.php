<?php \Core\View::section('title') ?>Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Marketing<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Marketing</h5>
    <small class="text-muted">Gerencie campanhas e listas de contatos para disparos em massa</small>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="mkTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link <?= $activeTab !== 'lists' ? 'active' : '' ?>"
            id="tab-campaigns" data-bs-toggle="tab" data-bs-target="#pane-campaigns" type="button">
      <i class="bi bi-megaphone-fill me-1"></i> Campanhas
      <span class="badge bg-secondary ms-1"><?= count($campaigns) ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link <?= $activeTab === 'lists' ? 'active' : '' ?>"
            id="tab-lists" data-bs-toggle="tab" data-bs-target="#pane-lists" type="button">
      <i class="bi bi-list-ul me-1"></i> Listas
      <span class="badge bg-secondary ms-1" id="count-lists"><?= count($lists) ?></span>
    </button>
  </li>
</ul>

<div class="tab-content" id="mkTabContent">

  <!-- ══════════════════════════════════════════
       CAMPANHAS
  ══════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab !== 'lists' ? 'show active' : '' ?>" id="pane-campaigns">

    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary d-flex align-items-center gap-2"
              data-bs-toggle="modal" data-bs-target="#campaignModal">
        <i class="bi bi-plus-lg"></i> Nova Campanha
      </button>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl-campaigns">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Nome</th>
              <th>Lista</th>
              <th>Template</th>
              <th>Contatos</th>
              <th>Status</th>
              <th>Criada em</th>
              <th class="text-end pe-3" width="130">Ações</th>
            </tr>
          </thead>
          <tbody id="tbody-campaigns">
            <?php if (empty($campaigns)): ?>
            <tr class="empty-row">
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-megaphone fs-2 d-block mb-2 opacity-50"></i>
                Nenhuma campanha criada ainda.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($campaigns as $c): ?>
            <?= campaignRow($c) ?>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       LISTAS
  ══════════════════════════════════════════════ -->
  <div class="tab-pane fade <?= $activeTab === 'lists' ? 'show active' : '' ?>" id="pane-lists">

    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary d-flex align-items-center gap-2"
              data-bs-toggle="modal" data-bs-target="#listModal">
        <i class="bi bi-plus-lg"></i> Nova Lista
      </button>
    </div>

    <div class="row g-3" id="lists-grid">
      <?php if (empty($lists)): ?>
      <div class="col-12 text-center text-muted py-5" id="lists-empty">
        <i class="bi bi-list-ul fs-2 d-block mb-2 opacity-50"></i>
        Nenhuma lista criada ainda.
      </div>
      <?php else: ?>
      <?php foreach ($lists as $l): ?>
      <?= listCard($l) ?>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

</div><!-- /tab-content -->

<!-- ================================================================
     MODAL: Nova Campanha
================================================================ -->
<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-megaphone-fill text-primary me-2"></i>Nova Campanha
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="campaign-alert"></div>
        <form id="campaign-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label fw-semibold small">Nome da Campanha <span class="text-danger">*</span></label>
              <input type="text" name="name" id="c-name" class="form-control" placeholder="Ex.: Promo Maio 2025">
              <div class="invalid-feedback" id="err-c-name"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Lista de Contatos <span class="text-danger">*</span></label>
              <select name="list_id" id="c-list_id" class="form-select">
                <option value="">Selecione uma lista...</option>
                <?php foreach ($lists as $l): ?>
                <option value="<?= $l['id'] ?>"><?= e($l['name']) ?> (<?= (int)$l['contact_count'] ?> contatos)</option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback" id="err-c-list_id"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Template WhatsApp <span class="text-danger">*</span></label>
              <select name="template_id" id="c-template_id" class="form-select" onchange="onTemplateChange()">
                <option value="">Selecione um template aprovado...</option>
                <?php foreach ($approvedTemplates as $tpl): ?>
                <option value="<?= $tpl['id'] ?>"
                        data-body="<?= e($tpl['body_text']) ?>"
                        data-vars="<?= e(json_encode($tpl['variables_decoded'])) ?>">
                  <?= e($tpl['name']) ?> — <?= e($tpl['category']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback" id="err-c-template_id"></div>
            </div>

            <!-- Variáveis dinâmicas -->
            <div class="col-12" id="c-vars-wrap" style="display:none;">
              <label class="form-label fw-semibold small">Variáveis da mensagem</label>
              <div id="c-vars-inputs" class="row g-2"></div>
            </div>

            <!-- Preview -->
            <div class="col-12" id="c-preview-wrap" style="display:none;">
              <label class="form-label fw-semibold small text-muted">Pré-visualização</label>
              <div class="p-3 rounded" style="background:#e5ddd5;font-size:.87rem;">
                <div class="p-3 rounded shadow-sm" style="background:#fff;max-width:360px;">
                  <div id="c-preview-body" style="white-space:pre-wrap;"></div>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btn-save-campaign" onclick="saveCampaign()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="c-spinner"></span>
          Criar Campanha
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Nova Lista
================================================================ -->
<div class="modal fade" id="listModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-list-ul text-primary me-2"></i>Nova Lista
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="list-alert"></div>
        <form id="list-form" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Nome da Lista <span class="text-danger">*</span></label>
            <input type="text" name="name" id="l-name" class="form-control" placeholder="Ex.: Clientes VIP">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Descrição <span class="text-muted fw-normal">(opcional)</span></label>
            <textarea name="description" id="l-desc" class="form-control" rows="2"
                      placeholder="Descreva o público desta lista..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btn-save-list" onclick="saveList()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="l-spinner"></span>
          Criar Lista
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Adicionar Contatos à Lista
================================================================ -->
<div class="modal fade" id="addContactsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-person-plus-fill text-success me-2"></i>
          Adicionar Contatos — <span id="ac-list-name"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="ac-alert"></div>
        <ul class="nav nav-tabs mb-3" id="acTabs">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ac-search">
              <i class="bi bi-search me-1"></i> Buscar contato
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ac-csv">
              <i class="bi bi-file-earmark-spreadsheet me-1"></i> Importar CSV
            </button>
          </li>
        </ul>
        <div class="tab-content">

          <div class="tab-pane fade show active" id="ac-search">
            <div class="position-relative mb-2">
              <input type="text" id="ac-q" class="form-control" placeholder="Buscar por nome ou telefone..."
                     oninput="searchForList(this.value)">
            </div>
            <div id="ac-results" class="border rounded overflow-auto" style="max-height:220px;display:none;"></div>
            <div id="ac-selected-wrap" class="mt-2" style="display:none;">
              <small class="text-muted fw-semibold">Selecionados:</small>
              <div id="ac-selected-chips" class="d-flex flex-wrap gap-1 mt-1"></div>
            </div>
          </div>

          <div class="tab-pane fade" id="ac-csv">
            <p class="text-muted small mb-2">Envie um CSV com coluna <code>telefone</code>. Os contatos já cadastrados serão adicionados à lista.</p>
            <input type="file" id="ac-csv-file" class="form-control" accept=".csv,.txt">
          </div>

        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-semibold px-4" id="btn-add-contacts" onclick="submitAddContacts()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="ac-spinner"></span>
          Adicionar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirm Send Campaign
================================================================ -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-send-fill text-primary fs-5"></i>
        </div>
        <h6 class="fw-bold">Enviar campanha?</h6>
        <p class="text-muted small mb-0">
          A campanha <strong id="send-name"></strong> será enviada para
          <strong id="send-count"></strong> contato(s). Esta ação não pode ser desfeita.
        </p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary px-4 fw-semibold" id="btn-confirm-send">
          <span class="spinner-border spinner-border-sm d-none me-1" id="send-spinner"></span>
          Enviar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirm Delete
================================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-trash-fill text-danger fs-5"></i>
        </div>
        <h6 class="fw-bold">Excluir?</h6>
        <p class="text-muted small mb-0" id="delete-msg"></p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-confirm-delete">
          <span class="spinner-border spinner-border-sm d-none me-1" id="delete-spinner"></span>
          Excluir
        </button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const MK = {
  campaigns:      '<?= url('admin/marketing/campaigns') ?>',
  lists:          '<?= url('admin/marketing/lists') ?>',
  searchContact:  '<?= url('admin/marketing/contacts/search') ?>',
  csrf:           '<?= csrf_token() ?>',
};

let sendModal, deleteModal, addContactsModal;
let currentListId = null;
let selectedContacts = {};

document.addEventListener('DOMContentLoaded', () => {
  sendModal        = new bootstrap.Modal(document.getElementById('sendModal'));
  deleteModal      = new bootstrap.Modal(document.getElementById('deleteModal'));
  addContactsModal = new bootstrap.Modal(document.getElementById('addContactsModal'));

  document.getElementById('campaignModal').addEventListener('hidden.bs.modal', resetCampaignForm);
  document.getElementById('listModal').addEventListener('hidden.bs.modal', resetListForm);
  document.getElementById('addContactsModal').addEventListener('hidden.bs.modal', resetAddContacts);
});

// ── Campaign ─────────────────────────────────────────────────────────

function onTemplateChange() {
  const sel = document.getElementById('c-template_id');
  const opt = sel.options[sel.selectedIndex];
  const body = opt.dataset.body || '';
  const vars = JSON.parse(opt.dataset.vars || '[]');

  const varsWrap   = document.getElementById('c-vars-wrap');
  const varsInputs = document.getElementById('c-vars-inputs');
  const prevWrap   = document.getElementById('c-preview-wrap');

  varsInputs.innerHTML = '';
  if (vars.length > 0) {
    vars.forEach((v, i) => {
      varsInputs.innerHTML += `
        <div class="col-md-6">
          <label class="form-label small text-muted">Variável {{${+v}}}</label>
          <input type="text" name="variables[${+v}]" class="form-control form-control-sm var-input"
                 data-idx="${+v}" placeholder="Valor da variável ${+v}"
                 oninput="updateCampaignPreview()">
        </div>`;
    });
    varsWrap.style.display = 'block';
  } else {
    varsWrap.style.display = 'none';
  }

  if (body) {
    prevWrap.style.display = 'block';
    document.getElementById('c-preview-body').textContent = body;
  } else {
    prevWrap.style.display = 'none';
  }
}

function updateCampaignPreview() {
  const sel  = document.getElementById('c-template_id');
  const opt  = sel.options[sel.selectedIndex];
  let   body = opt.dataset.body || '';
  document.querySelectorAll('.var-input').forEach(inp => {
    body = body.replaceAll('{{' + inp.dataset.idx + '}}', inp.value || ('{{' + inp.dataset.idx + '}}'));
  });
  document.getElementById('c-preview-body').textContent = body;
}

function saveCampaign() {
  const btn  = document.getElementById('btn-save-campaign');
  const spin = document.getElementById('c-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');
  document.getElementById('campaign-alert').innerHTML = '';

  const fd = new FormData(document.getElementById('campaign-form'));
  fetch(MK.campaigns, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('campaignModal')).hide();
        Toast.show(res.message, 'success');
        const c = res.data?.campaign;
        if (c) appendCampaignRow(c);
      } else {
        document.getElementById('campaign-alert').innerHTML =
          `<div class="alert alert-danger py-2 small">${escHtml(res.message)}</div>`;
      }
    })
    .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function appendCampaignRow(c) {
  const tbody = document.getElementById('tbody-campaigns');
  tbody.querySelector('.empty-row')?.remove();
  const date = c.created_at ? c.created_at.substring(0,10).split('-').reverse().join('/') : '';
  tbody.insertAdjacentHTML('afterbegin', buildCampaignRow(c, date));
}

function buildCampaignRow(c, date) {
  return `<tr id="camp-row-${c.id}">
    <td class="ps-3 fw-semibold small">${escHtml(c.name)}</td>
    <td class="small text-muted">${escHtml(c.list_name || '—')}</td>
    <td class="small text-muted">${escHtml(c.template_name || '—')}</td>
    <td class="small">${c.total_contacts}</td>
    <td>${statusBadge(c.status)}</td>
    <td class="small text-muted">${date}</td>
    <td class="text-end pe-3">
      <div class="d-flex gap-1 justify-content-end">
        <button class="btn btn-sm btn-outline-primary" onclick="confirmSend(${c.id}, '${escHtml(c.name)}', ${c.total_contacts})"
                id="btn-send-${c.id}" title="Enviar">
          <i class="bi bi-send"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCampaign(${c.id})" title="Excluir">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </td>
  </tr>`;
}

function confirmSend(id, name, count) {
  document.getElementById('send-name').textContent  = name;
  document.getElementById('send-count').textContent = count;
  sendModal.show();

  const btn  = document.getElementById('btn-confirm-send');
  const spin = document.getElementById('send-spinner');
  btn.onclick = () => {
    btn.disabled = true;
    spin.classList.remove('d-none');

    const fd = new FormData();
    fd.append('_csrf_token', MK.csrf);
    fetch(MK.campaigns + '/' + id + '/send', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        spin.classList.add('d-none');
        sendModal.hide();
        Toast.show(res.message, res.success ? 'success' : 'error');
        if (res.success) {
          const badge = document.querySelector(`#camp-row-${id} .status-badge`);
          if (badge) badge.outerHTML = statusBadge('sent');
          const sendBtn = document.getElementById('btn-send-' + id);
          if (sendBtn) sendBtn.style.display = 'none';
        }
      })
      .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
  };
}

function confirmDeleteCampaign(id) {
  document.getElementById('delete-msg').textContent = 'A campanha será excluída permanentemente.';
  deleteModal.show();
  document.getElementById('btn-confirm-delete').onclick = () => doDelete(MK.campaigns + '/' + id + '/delete', 'camp-row-' + id);
}

// ── Lists ─────────────────────────────────────────────────────────────

function saveList() {
  const btn  = document.getElementById('btn-save-list');
  const spin = document.getElementById('l-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');
  document.getElementById('list-alert').innerHTML = '';

  const fd = new FormData(document.getElementById('list-form'));
  fetch(MK.lists, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('listModal')).hide();
        Toast.show(res.message, 'success');
        const l = res.data?.list;
        if (l) appendListCard(l);
        // Also add to campaign select
        addListOption(l);
      } else {
        document.getElementById('list-alert').innerHTML =
          `<div class="alert alert-danger py-2 small">${escHtml(res.message)}</div>`;
      }
    })
    .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function appendListCard(l) {
  document.getElementById('lists-empty')?.remove();
  document.getElementById('lists-grid').insertAdjacentHTML('afterbegin', buildListCard(l));
  const cnt = document.getElementById('count-lists');
  if (cnt) cnt.textContent = parseInt(cnt.textContent || '0') + 1;
}

function buildListCard(l) {
  return `<div class="col-md-4" id="list-card-${l.id}">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <h6 class="fw-bold mb-0">${escHtml(l.name)}</h6>
          <button class="btn btn-sm btn-outline-danger p-1" onclick="confirmDeleteList(${l.id}, '${escHtml(l.name)}')" title="Excluir">
            <i class="bi bi-trash" style="font-size:.8rem;"></i>
          </button>
        </div>
        ${l.description ? `<p class="text-muted small mb-2">${escHtml(l.description)}</p>` : ''}
        <div class="d-flex align-items-center justify-content-between">
          <span class="badge bg-light text-dark border" id="list-count-${l.id}">
            <i class="bi bi-people me-1"></i>${l.contact_count || 0} contatos
          </span>
          <button class="btn btn-sm btn-outline-success" onclick="openAddContacts(${l.id}, '${escHtml(l.name)}')">
            <i class="bi bi-person-plus me-1"></i> Adicionar
          </button>
        </div>
      </div>
    </div>
  </div>`;
}

function addListOption(l) {
  const sel = document.getElementById('c-list_id');
  if (sel) {
    const opt = document.createElement('option');
    opt.value = l.id;
    opt.textContent = l.name + ' (0 contatos)';
    sel.appendChild(opt);
  }
}

function confirmDeleteList(id, name) {
  document.getElementById('delete-msg').textContent = `A lista "${name}" e todos os seus contatos vinculados serão removidos.`;
  deleteModal.show();
  document.getElementById('btn-confirm-delete').onclick = () => {
    doDelete(MK.lists + '/' + id + '/delete', 'list-card-' + id, () => {
      const cnt = document.getElementById('count-lists');
      if (cnt) cnt.textContent = Math.max(0, parseInt(cnt.textContent || '0') - 1);
      // Remove from campaign select
      document.querySelector(`#c-list_id option[value="${id}"]`)?.remove();
    });
  };
}

// ── Add Contacts ─────────────────────────────────────────────────────

function openAddContacts(listId, listName) {
  currentListId = listId;
  selectedContacts = {};
  document.getElementById('ac-list-name').textContent = listName;
  addContactsModal.show();
}

let acTimer;
function searchForList(q) {
  clearTimeout(acTimer);
  if (q.length < 2) { document.getElementById('ac-results').style.display = 'none'; return; }
  acTimer = setTimeout(() => {
    const fd = new FormData();
    fd.append('q', q);
    fd.append('_csrf_token', MK.csrf);
    fetch(MK.searchContact, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => renderAcResults(res.data?.contacts || []));
  }, 280);
}

function renderAcResults(contacts) {
  const el = document.getElementById('ac-results');
  if (!contacts.length) { el.style.display = 'none'; return; }
  el.style.display = 'block';
  el.innerHTML = contacts.map(c =>
    `<div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center ac-item"
          style="cursor:pointer;" onclick="selectContact(${c.id}, '${escHtml(c.name)}', '${escHtml(c.phone)}')">
       <div>
         <div class="fw-semibold small">${escHtml(c.name)}</div>
         <div class="text-muted" style="font-size:.75rem;">${escHtml(c.phone)}</div>
       </div>
       ${selectedContacts[c.id] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-plus-circle text-muted"></i>'}
     </div>`
  ).join('');
}

function selectContact(id, name, phone) {
  if (selectedContacts[id]) {
    delete selectedContacts[id];
  } else {
    selectedContacts[id] = { id, name, phone };
  }
  updateChips();
  searchForList(document.getElementById('ac-q').value);
}

function updateChips() {
  const chips = document.getElementById('ac-selected-chips');
  const wrap  = document.getElementById('ac-selected-wrap');
  const keys  = Object.keys(selectedContacts);
  if (!keys.length) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';
  chips.innerHTML = keys.map(id => {
    const c = selectedContacts[id];
    return `<span class="badge bg-primary d-flex align-items-center gap-1 py-1 px-2">
      ${escHtml(c.name)}
      <button class="btn-close btn-close-white" style="font-size:.6rem;" onclick="selectContact(${c.id},'','')"></button>
    </span>`;
  }).join('');
}

function submitAddContacts() {
  const btn  = document.getElementById('btn-add-contacts');
  const spin = document.getElementById('ac-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');
  document.getElementById('ac-alert').innerHTML = '';

  const fd = new FormData();
  fd.append('_csrf_token', MK.csrf);

  const csvFile = document.getElementById('ac-csv-file').files[0];
  if (csvFile) {
    fd.append('csv_file', csvFile);
  }
  Object.keys(selectedContacts).forEach(id => fd.append('contact_ids[]', id));

  fetch(MK.lists + '/' + currentListId + '/contacts', {
    method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      if (res.success) {
        addContactsModal.hide();
        Toast.show(res.message, 'success');
        // Update count badge on card
        const countBadge = document.getElementById('list-count-' + currentListId);
        if (countBadge && res.data?.total !== undefined) {
          countBadge.innerHTML = `<i class="bi bi-people me-1"></i>${res.data.total} contatos`;
        }
        // Update campaign select option
        const opt = document.querySelector(`#c-list_id option[value="${currentListId}"]`);
        if (opt && res.data?.total !== undefined) {
          opt.textContent = opt.textContent.replace(/\(\d+ contatos\)/, `(${res.data.total} contatos)`);
        }
      } else {
        document.getElementById('ac-alert').innerHTML =
          `<div class="alert alert-danger py-2 small">${escHtml(res.message)}</div>`;
      }
    })
    .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function resetAddContacts() {
  currentListId = null;
  selectedContacts = {};
  document.getElementById('ac-q').value = '';
  document.getElementById('ac-results').style.display = 'none';
  document.getElementById('ac-selected-wrap').style.display = 'none';
  document.getElementById('ac-csv-file').value = '';
  document.getElementById('ac-alert').innerHTML = '';
}

// ── Generic Delete ────────────────────────────────────────────────────

function doDelete(url, rowId, after) {
  const btn  = document.getElementById('btn-confirm-delete');
  const spin = document.getElementById('delete-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');

  const fd = new FormData();
  fd.append('_csrf_token', MK.csrf);
  fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      deleteModal.hide();
      if (res.success) {
        const el = document.getElementById(rowId);
        if (el) { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }
        Toast.show(res.message, 'success');
        if (after) after();
      } else {
        Toast.show(res.message, 'error');
      }
    })
    .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

// ── Reset forms ───────────────────────────────────────────────────────

function resetCampaignForm() {
  document.getElementById('campaign-form').reset();
  document.getElementById('campaign-alert').innerHTML = '';
  document.getElementById('c-vars-wrap').style.display = 'none';
  document.getElementById('c-preview-wrap').style.display = 'none';
}
function resetListForm() {
  document.getElementById('list-form').reset();
  document.getElementById('list-alert').innerHTML = '';
}

// ── Helpers ───────────────────────────────────────────────────────────

function statusBadge(status) {
  const map = {
    draft:     ['secondary', 'Rascunho'],
    scheduled: ['info text-dark', 'Agendada'],
    sending:   ['warning text-dark', 'Enviando…'],
    sent:      ['success', 'Enviada'],
    cancelled: ['danger', 'Cancelada'],
  };
  const [cls, label] = map[status] || map.draft;
  return `<span class="badge bg-${cls} status-badge">${label}</span>`;
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>

<?php
function campaignRow(array $c): string
{
    $date = !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—';
    $statusMap = [
        'draft'     => ['secondary', 'Rascunho'],
        'scheduled' => ['info text-dark', 'Agendada'],
        'sending'   => ['warning text-dark', 'Enviando…'],
        'sent'      => ['success', 'Enviada'],
        'cancelled' => ['danger', 'Cancelada'],
    ];
    [$cls, $label] = $statusMap[$c['status'] ?? 'draft'] ?? ['secondary', 'Rascunho'];
    $canSend = in_array($c['status'] ?? '', ['draft', 'scheduled']);
    $sendBtn = $canSend
        ? '<button class="btn btn-sm btn-outline-primary" onclick="confirmSend(' . $c['id'] . ', \'' . e($c['name']) . '\', ' . (int)$c['total_contacts'] . ')" id="btn-send-' . $c['id'] . '" title="Enviar"><i class="bi bi-send"></i></button>'
        : '';
    return "
    <tr id=\"camp-row-{$c['id']}\">
      <td class=\"ps-3 fw-semibold small\">" . e($c['name']) . "</td>
      <td class=\"small text-muted\">" . e($c['list_name'] ?? '—') . "</td>
      <td class=\"small text-muted\">" . e($c['template_name'] ?? '—') . "</td>
      <td class=\"small\">{$c['total_contacts']}</td>
      <td><span class=\"badge bg-{$cls} status-badge\">{$label}</span></td>
      <td class=\"small text-muted\">{$date}</td>
      <td class=\"text-end pe-3\">
        <div class=\"d-flex gap-1 justify-content-end\">
          {$sendBtn}
          <button class=\"btn btn-sm btn-outline-danger\" onclick=\"confirmDeleteCampaign({$c['id']})\" title=\"Excluir\">
            <i class=\"bi bi-trash\"></i>
          </button>
        </div>
      </td>
    </tr>";
}

function listCard(array $l): string
{
    $desc = !empty($l['description']) ? '<p class="text-muted small mb-2">' . e($l['description']) . '</p>' : '';
    $cnt  = (int)($l['contact_count'] ?? 0);
    return "
    <div class=\"col-md-4\" id=\"list-card-{$l['id']}\">
      <div class=\"card border-0 shadow-sm h-100\">
        <div class=\"card-body\">
          <div class=\"d-flex align-items-start justify-content-between mb-2\">
            <h6 class=\"fw-bold mb-0\">" . e($l['name']) . "</h6>
            <button class=\"btn btn-sm btn-outline-danger p-1\" onclick=\"confirmDeleteList({$l['id']}, '" . e($l['name']) . "')\" title=\"Excluir\">
              <i class=\"bi bi-trash\" style=\"font-size:.8rem;\"></i>
            </button>
          </div>
          {$desc}
          <div class=\"d-flex align-items-center justify-content-between\">
            <span class=\"badge bg-light text-dark border\" id=\"list-count-{$l['id']}\">
              <i class=\"bi bi-people me-1\"></i>{$cnt} contatos
            </span>
            <button class=\"btn btn-sm btn-outline-success\" onclick=\"openAddContacts({$l['id']}, '" . e($l['name']) . "')\">
              <i class=\"bi bi-person-plus me-1\"></i> Adicionar
            </button>
          </div>
        </div>
      </div>
    </div>";
}
?>
