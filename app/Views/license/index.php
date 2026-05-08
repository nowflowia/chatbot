<?php \Core\View::section('title') ?>Licença<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Licença<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$lic   = $license ?? [];
$valid = !empty($lic['valid']);
$feats = $lic['features'] ?? [];

$cacheAge   = $cacheRow ? (time() - (int)$cacheRow['checked_at']) : null;
$cacheValid = $cacheAge !== null && $cacheAge < $cacheTtl;
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Diagnóstico da Licença</h5>
    <small class="text-muted">Veja exatamente o que a API de licenças está retornando para esta instalação</small>
  </div>
  <button class="btn btn-primary fw-semibold" id="btn-refresh-license">
    <span class="spinner-border spinner-border-sm d-none me-2" id="refresh-spin"></span>
    <i class="bi bi-arrow-clockwise me-2"></i> Forçar atualização
  </button>
</div>

<div id="refresh-alert"></div>

<div class="row g-4">
  <!-- ── Status da licença ── -->
  <div class="col-lg-7">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-key-fill text-primary"></i> Status atual
        </span>
        <?php if ($valid): ?>
        <span class="badge bg-success-subtle text-success" style="font-size:.7rem;">
          <i class="bi bi-check-circle-fill"></i> Válida
        </span>
        <?php else: ?>
        <span class="badge bg-danger-subtle text-danger" style="font-size:.7rem;">
          <i class="bi bi-x-circle-fill"></i> Inválida
        </span>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <tr>
              <td class="text-muted ps-3" style="width:35%">Domínio (na licença)</td>
              <td class="font-monospace fw-semibold"><?= e($lic['domain'] ?? '—') ?></td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Domínio (configurado)</td>
              <td class="font-monospace"><?= e($configDom) ?></td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Plano</td>
              <td class="fw-semibold"><?= e($lic['plan'] ?? '—') ?></td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Status</td>
              <td><?= e($lic['status'] ?? '—') ?></td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Expira em</td>
              <td>
                <?php if (!empty($lic['expires_at'])): ?>
                  <?= date('d/m/Y H:i', strtotime((string)$lic['expires_at'])) ?>
                <?php else: ?>
                  <span class="text-muted">— nunca —</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Máx. usuários</td>
              <td><?= (int)($lic['max_users'] ?? 0) ?></td>
            </tr>
            <tr>
              <td class="text-muted ps-3">Máx. fluxos</td>
              <td><?= (int)($lic['max_flows'] ?? 0) ?></td>
            </tr>
            <?php if (!empty($lic['error'])): ?>
            <tr>
              <td class="text-muted ps-3">Erro</td>
              <td class="text-danger fw-semibold"><?= e($lic['error']) ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Features ── -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-puzzle-fill text-success"></i> Módulos / Features
      </div>
      <div class="card-body">
        <?php if (empty($feats)): ?>
        <div class="alert alert-warning py-2 small mb-0">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>
          A API <strong>não retornou nenhum módulo</strong> para esta licença.
          Verifique o cadastro da licença no Owner Panel.
        </div>
        <?php else: ?>
        <p class="text-muted small mb-2">
          Esta licença libera os seguintes módulos no painel do cliente:
        </p>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($feats as $f): ?>
          <span class="badge bg-primary-subtle text-primary fw-semibold" style="font-size:.78rem;">
            <i class="bi bi-check2 me-1"></i><?= e((string)$f) ?>
          </span>
          <?php endforeach; ?>
        </div>
        <details>
          <summary class="small text-muted" style="cursor:pointer">Ver array bruto (JSON)</summary>
          <pre class="small bg-light p-3 mt-2 rounded mb-0"
               style="font-size:.76rem; white-space:pre-wrap;"><?= e(json_encode($feats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
        </details>
        <?php endif; ?>

        <hr>
        <p class="small text-muted mb-2">
          <strong>Chaves esperadas pelo código:</strong> os menus/recursos abaixo aparecem somente
          se a licença incluir o nome correto da feature:
        </p>
        <table class="table table-sm small mb-0">
          <thead><tr><th>Recurso</th><th>Chave esperada</th><th>Status</th></tr></thead>
          <tbody>
            <?php
            $expectedFeatures = [
              'CRM (Negociações, Empresas, Contatos)' => 'crm',
              'Marketing (Campanhas, Listas, Instagram)' => 'marketing',
              'API REST externa'                       => 'api',
            ];
            foreach ($expectedFeatures as $label => $key):
              $has = in_array($key, $feats, true);
            ?>
            <tr>
              <td><?= e($label) ?></td>
              <td><code><?= e($key) ?></code></td>
              <td>
                <?php if ($has): ?>
                <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Habilitado</span>
                <?php else: ?>
                <span class="text-muted"><i class="bi bi-dash-circle"></i> Não incluído</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Resposta completa da API ── -->
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-code-slash text-secondary"></i> Resposta completa
      </div>
      <div class="card-body p-0">
        <pre class="bg-light p-3 mb-0"
             style="font-size:.78rem; white-space:pre-wrap; max-height:380px; overflow:auto;"><?= e(json_encode($lic, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) ?></pre>
      </div>
    </div>
  </div>

  <!-- ── Configuração local + cache ── -->
  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-gear-fill text-secondary"></i> Configuração local (.env)
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted ps-3" style="width:42%">LICENSE_API_URL</td>
              <td class="font-monospace text-break">
                <?= $apiUrl !== '' ? e($apiUrl) : '<span class="text-danger fw-semibold">não definido</span>' ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3">LICENSE_KEY</td>
              <td class="font-monospace">
                <?php if ($keyLength > 0): ?>
                <?= e($keyMasked) ?>
                <span class="text-muted">(<?= $keyLength ?> chars)</span>
                <?php else: ?>
                <span class="text-danger fw-semibold">não definido</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3">APP_URL → host</td>
              <td class="font-monospace"><?= e($configDom) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-database text-info"></i> Cache local
      </div>
      <div class="card-body small">
        <?php if ($cacheRow): ?>
        <dl class="row mb-0">
          <dt class="col-5 text-muted fw-normal">Última consulta</dt>
          <dd class="col-7"><?= date('d/m/Y H:i:s', (int)$cacheRow['checked_at']) ?></dd>
          <dt class="col-5 text-muted fw-normal mt-1">Idade</dt>
          <dd class="col-7 mt-1">
            <?php
              $mins = floor($cacheAge / 60);
              $secs = $cacheAge % 60;
              echo "{$mins}min {$secs}s";
            ?>
          </dd>
          <dt class="col-5 text-muted fw-normal mt-1">Status</dt>
          <dd class="col-7 mt-1">
            <?php if ($cacheValid): ?>
            <span class="text-success fw-semibold">
              <i class="bi bi-check-circle-fill"></i> Válido (TTL <?= $cacheTtl ?>s)
            </span>
            <?php else: ?>
            <span class="text-warning fw-semibold">
              <i class="bi bi-clock-history"></i> Expirado — próxima leitura buscará na API
            </span>
            <?php endif; ?>
          </dd>
        </dl>
        <?php else: ?>
        <p class="text-muted mb-0">Cache vazio — próxima leitura vai buscar diretamente na API.</p>
        <?php endif; ?>

        <hr>
        <p class="small text-muted mb-0">
          O painel do cliente lê a licença da tabela <code>license_cache</code> por até
          <strong>1 hora</strong> antes de consultar a API novamente.
          Use o botão <strong>Forçar atualização</strong> acima para limpar o cache imediatamente.
        </p>
      </div>
    </div>

    <div class="card border-warning mb-4">
      <div class="card-header bg-warning-subtle d-flex align-items-center justify-content-between">
        <span class="fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-bug-fill text-warning"></i> Diagnóstico de conexão
        </span>
        <button class="btn btn-warning btn-sm fw-semibold" id="btn-probe-api">
          <span class="spinner-border spinner-border-sm d-none me-1" id="probe-spin"></span>
          <i class="bi bi-broadcast me-1"></i> Testar API agora
        </button>
      </div>
      <div class="card-body p-3">
        <p class="small text-muted mb-2">
          Faz uma chamada raw HTTP à <code>LICENSE_API_URL</code> e mostra status, tempo e corpo da resposta.
          Útil quando aparece <em>"License API unreachable"</em>.
        </p>
        <div id="probe-result"></div>
      </div>
    </div>

    <div class="card border-info">
      <div class="card-header bg-info-subtle d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-info"></i> Como funciona
      </div>
      <div class="card-body small text-muted">
        <ol class="ps-3 mb-0" style="line-height:1.7;">
          <li>O cliente chama <code>LICENSE_API_URL</code> com domínio e chave.</li>
          <li>A API retorna o JSON com plano, limites e <strong>features</strong> (módulos).</li>
          <li>O resultado é cacheado por 1 hora em <code>license_cache</code>.</li>
          <li>Cada feature (ex.: <code>crm</code>, <code>api</code>) libera menus/rotas.</li>
        </ol>
      </div>
    </div>
  </div>
</div>


<script>
// ── Probe API ────────────────────────────────────────────────
document.getElementById('btn-probe-api')?.addEventListener('click', function () {
  var btn  = this;
  var spin = document.getElementById('probe-spin');
  var box  = document.getElementById('probe-result');
  btn.disabled = true; spin.classList.remove('d-none');
  box.innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split"></i> Testando…</div>';

  var fd = new FormData();
  fd.append('_csrf_token', '<?= \Core\CSRF::token() ?>');

  fetch('<?= url('admin/license/probe') ?>', {
    method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; spin.classList.add('d-none');
    if (!res.success) {
      box.innerHTML = '<div class="alert alert-danger py-2 small">' + res.message + '</div>';
      return;
    }
    var d = res.data || {};
    var statusHtml = '';
    if (d.error) {
      statusHtml = '<span class="badge bg-danger">Falhou</span>';
    } else if (d.code >= 200 && d.code < 300 && d.parsed) {
      statusHtml = '<span class="badge bg-success">HTTP ' + d.code + ' · JSON OK</span>';
    } else if (d.code >= 200 && d.code < 300) {
      statusHtml = '<span class="badge bg-warning">HTTP ' + d.code + ' · resposta não é JSON</span>';
    } else {
      statusHtml = '<span class="badge bg-danger">HTTP ' + d.code + '</span>';
    }

    var html = ''
      + '<table class="table table-sm small mb-3">'
      + '<tbody>'
      +   '<tr><td class="text-muted" style="width:30%">URL chamada</td>'
      +     '<td class="font-monospace text-break" style="font-size:.72rem">' + escapeHtml(d.url) + '</td></tr>'
      +   '<tr><td class="text-muted">Status</td><td>' + statusHtml + '</td></tr>'
      +   '<tr><td class="text-muted">Tempo</td><td>' + d.duration_ms + ' ms</td></tr>';
    if (d.error) {
      html += '<tr><td class="text-muted">Erro cURL</td><td class="text-danger fw-semibold">' + escapeHtml(d.error) + '</td></tr>';
    }
    html += '</tbody></table>';

    if (d.parsed) {
      html += '<div class="small fw-semibold mb-1 text-success">'
            + '<i class="bi bi-check-circle-fill"></i> JSON recebido</div>'
            + '<pre class="bg-light p-3 rounded mb-0" style="font-size:.74rem;max-height:300px;overflow:auto">'
            + escapeHtml(JSON.stringify(d.parsed, null, 2)) + '</pre>';
    } else if (d.body) {
      html += '<div class="small fw-semibold mb-1 text-warning">'
            + '<i class="bi bi-exclamation-triangle-fill"></i> Corpo da resposta (não-JSON)</div>'
            + '<pre class="bg-light p-3 rounded mb-0" style="font-size:.74rem;max-height:300px;overflow:auto">'
            + escapeHtml(d.body) + '</pre>';
    } else {
      html += '<div class="alert alert-danger py-2 small mb-0">'
            + 'Resposta vazia. Verifique se a URL está acessível pelo servidor (firewall, DNS, SSL).'
            + '</div>';
    }
    box.innerHTML = html;
  })
  .catch(function (err) {
    btn.disabled = false; spin.classList.add('d-none');
    box.innerHTML = '<div class="alert alert-danger py-2 small">Erro: ' + err + '</div>';
  });
});
function escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
  });
}

document.getElementById('btn-refresh-license')?.addEventListener('click', function () {
  var btn  = this;
  var spin = document.getElementById('refresh-spin');
  var box  = document.getElementById('refresh-alert');
  btn.disabled = true; spin.classList.remove('d-none');
  box.innerHTML = '';

  var fd = new FormData();
  fd.append('_csrf_token', '<?= \Core\CSRF::token() ?>');

  fetch('<?= url('admin/license/refresh') ?>', {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; spin.classList.add('d-none');
    if (res.success) {
      box.innerHTML = '<div class="alert alert-success py-2 small d-flex gap-2">'
        + '<i class="bi bi-check-circle-fill mt-1"></i>'
        + '<span>' + res.message + ' Recarregando…</span></div>';
      setTimeout(function () { location.reload(); }, 800);
    } else {
      box.innerHTML = '<div class="alert alert-danger py-2 small">' + res.message + '</div>';
    }
  })
  .catch(function () {
    btn.disabled = false; spin.classList.add('d-none');
    box.innerHTML = '<div class="alert alert-danger py-2 small">Erro de conexão.</div>';
  });
});
</script>

<?php \Core\View::endSection() ?>
