<?php \Core\View::section('title') ?>CRM — Administração<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>CRM — Administração<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h5 class="fw-bold mb-0 text-dark">CRM — Administração</h5>
    <small class="text-muted">Ferramentas administrativas do módulo CRM</small>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="crmAdminTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" id="tab-contacts" data-bs-toggle="tab"
            data-bs-target="#pane-contacts" type="button">
      <i class="bi bi-person-lines-fill me-1"></i> Contatos
    </button>
  </li>
</ul>

<div class="tab-content" id="crmAdminTabContent">

  <!-- ── Aba: Contatos ── -->
  <div class="tab-pane fade show active" id="pane-contacts">

    <div class="row g-4">

      <!-- Importar CSV -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-upload me-2 text-primary"></i>Importar Contatos via CSV</h6>
            <p class="text-muted small mb-0 mt-1">Máximo de <?= number_format(1000) ?> contatos por arquivo.</p>
          </div>
          <div class="card-body px-4 pt-3 pb-4">

            <div id="import-alert"></div>

            <form id="import-form" novalidate>
              <?= csrf_field() ?>

              <!-- Drop zone -->
              <div id="drop-zone"
                   class="border border-2 border-dashed rounded-3 text-center p-5 mb-3"
                   style="cursor:pointer;transition:background .2s;"
                   onclick="document.getElementById('csv-file').click()"
                   ondragover="event.preventDefault();this.classList.add('bg-primary','bg-opacity-10')"
                   ondragleave="this.classList.remove('bg-primary','bg-opacity-10')"
                   ondrop="handleDrop(event)">
                <i class="bi bi-file-earmark-spreadsheet fs-1 text-primary opacity-75 d-block mb-2"></i>
                <div class="fw-semibold mb-1">Arraste o CSV aqui ou clique para selecionar</div>
                <div class="text-muted small" id="file-name-display">Nenhum arquivo selecionado</div>
              </div>

              <input type="file" id="csv-file" name="csv_file" accept=".csv,.txt"
                     class="d-none" onchange="onFileSelected(this)">

              <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary flex-grow-1 fw-semibold" id="btn-import" onclick="submitImport()" disabled>
                  <span class="spinner-border spinner-border-sm d-none me-1" id="import-spinner"></span>
                  <i class="bi bi-upload me-1" id="import-icon"></i> Importar
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </form>

            <!-- Result -->
            <div id="import-result" class="mt-3" style="display:none;">
              <div class="row g-2 text-center mb-3" id="result-stats"></div>
              <div id="result-errors"></div>
            </div>

          </div>
        </div>
      </div>

      <!-- Modelo + instruções -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body px-4 py-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-arrow-down me-2 text-success"></i>Modelo de CSV</h6>
            <p class="text-muted small mb-3">Baixe o arquivo modelo, preencha e importe.</p>
            <a href="<?= url('admin/crm-admin/contacts/template') ?>"
               class="btn btn-outline-success w-100 fw-semibold">
              <i class="bi bi-download me-1"></i> Baixar modelo_contatos.csv
            </a>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body px-4 py-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-info"></i>Como usar</h6>
            <ul class="small text-muted mb-0 ps-3" style="line-height:1.8;">
              <li>Abra o modelo no Excel ou Google Sheets</li>
              <li>Preencha as colunas (separador <code>;</code>)</li>
              <li>Salve como <strong>CSV UTF-8</strong></li>
              <li>Limite: <strong>1.000 linhas</strong> por arquivo</li>
              <li>Contatos com o mesmo telefone serão <strong>atualizados</strong></li>
              <li>Telefone é obrigatório; email e observações são opcionais</li>
            </ul>

            <hr class="my-2">
            <p class="small fw-semibold mb-1">Colunas aceitas:</p>
            <div class="d-flex flex-wrap gap-1">
              <?php foreach (['nome', 'telefone', 'email', 'observacoes'] as $col): ?>
              <code class="bg-light border rounded px-2 py-1 small"><?= $col ?></code>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- /pane-contacts -->

</div><!-- /tab-content -->

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const IMPORT_URL = '<?= url('admin/crm-admin/contacts/import') ?>';

function onFileSelected(input) {
  const file = input.files[0];
  if (file) {
    document.getElementById('file-name-display').textContent = file.name + ' (' + formatBytes(file.size) + ')';
    document.getElementById('drop-zone').style.borderColor = '#0d6efd';
    document.getElementById('btn-import').disabled = false;
    resetResult();
  }
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('drop-zone').classList.remove('bg-primary', 'bg-opacity-10');
  const file = e.dataTransfer.files[0];
  if (!file) return;
  const dt = new DataTransfer();
  dt.items.add(file);
  const input = document.getElementById('csv-file');
  input.files = dt.files;
  onFileSelected(input);
}

function resetForm() {
  document.getElementById('import-form').reset();
  document.getElementById('file-name-display').textContent = 'Nenhum arquivo selecionado';
  document.getElementById('drop-zone').style.borderColor = '';
  document.getElementById('btn-import').disabled = true;
  document.getElementById('import-alert').innerHTML = '';
  resetResult();
}

function resetResult() {
  const r = document.getElementById('import-result');
  r.style.display = 'none';
  document.getElementById('result-stats').innerHTML = '';
  document.getElementById('result-errors').innerHTML = '';
}

function submitImport() {
  const fileInput = document.getElementById('csv-file');
  if (!fileInput.files.length) return;

  const btn  = document.getElementById('btn-import');
  const spin = document.getElementById('import-spinner');
  const icon = document.getElementById('import-icon');
  btn.disabled = true;
  spin.classList.remove('d-none');
  icon.classList.add('d-none');
  document.getElementById('import-alert').innerHTML = '';
  resetResult();

  const fd = new FormData(document.getElementById('import-form'));

  fetch(IMPORT_URL, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.classList.remove('d-none');

      if (res.success) {
        const d = res.data || {};
        showStats(d.inserted || 0, d.updated || 0, d.skipped || 0);
        showErrors(d.errors || []);
        Toast.show(res.message, 'success');
      } else {
        document.getElementById('import-alert').innerHTML =
          '<div class="alert alert-danger py-2 small d-flex gap-2 align-items-start">' +
          '<i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i><span>' + escHtml(res.message) + '</span></div>';
      }
    })
    .catch(() => {
      btn.disabled = false;
      spin.classList.add('d-none');
      icon.classList.remove('d-none');
      Toast.show('Erro de conexão.', 'error');
    });
}

function showStats(inserted, updated, skipped) {
  const stats = [
    { label: 'Inseridos', value: inserted, color: 'success' },
    { label: 'Atualizados', value: updated, color: 'primary' },
    { label: 'Ignorados', value: skipped, color: 'warning' },
  ];
  document.getElementById('result-stats').innerHTML = stats.map(s =>
    `<div class="col-4">
       <div class="rounded-3 py-3 bg-${s.color} bg-opacity-10 border border-${s.color} border-opacity-25">
         <div class="fs-4 fw-bold text-${s.color}">${s.value}</div>
         <div class="small text-muted">${s.label}</div>
       </div>
     </div>`
  ).join('');
  document.getElementById('import-result').style.display = 'block';
}

function showErrors(errors) {
  const el = document.getElementById('result-errors');
  if (!errors.length) { el.innerHTML = ''; return; }
  el.innerHTML =
    '<div class="alert alert-warning py-2 small mb-0">' +
    '<strong>Avisos:</strong><ul class="mb-0 mt-1 ps-3">' +
    errors.map(e => '<li>' + escHtml(e) + '</li>').join('') +
    '</ul></div>';
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>
