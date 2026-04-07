<?php \Core\View::section('title') ?>Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Configurações<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<?php
$st = $settings['status'] ?? 'inactive';
$statusMap = [
  'active'   => ['bg'=>'#dcfce7','color'=>'#166534','dot'=>'#16a34a','label'=>'Conectado'],
  'inactive' => ['bg'=>'#f1f5f9','color'=>'#475569','dot'=>'#94a3b8','label'=>'Não testado'],
  'error'    => ['bg'=>'#fee2e2','color'=>'#991b1b','dot'=>'#dc2626','label'=>'Erro'],
];
$sc  = $statusMap[$st] ?? $statusMap['inactive'];
$cs  = $companySettings ?? [];
$tab = $activeTab ?? 'empresa';
?>

<!-- Tab nav -->
<ul class="nav nav-tabs mb-4" id="settingsTabs">
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'empresa'   ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=empresa') ?>">
      <i class="bi bi-building text-secondary"></i> Empresa
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'template'  ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=template') ?>">
      <i class="bi bi-palette text-warning"></i> Template
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'whatsapp'  ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=whatsapp') ?>">
      <i class="bi bi-whatsapp text-success"></i> WhatsApp
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'smtp'      ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=smtp') ?>">
      <i class="bi bi-envelope-fill text-primary"></i> E-mail / SMTP
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'api' ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=api') ?>">
      <i class="bi bi-key-fill text-danger"></i> API
    </a>
  </li>
  <?php if (\Core\Auth::isAdmin()): ?>
  <li class="nav-item ms-auto">
    <a class="nav-link d-flex align-items-center gap-2 <?= $tab === 'atualizacao' ? 'active' : '' ?>"
       href="<?= url('admin/settings?tab=atualizacao') ?>">
      <i class="bi bi-cloud-arrow-down-fill text-info"></i> Atualização
    </a>
  </li>
  <?php endif; ?>
</ul>


<!-- ═══════════════════════════════════════════════════════════
     TAB: Empresa
═══════════════════════════════════════════════════════════ -->
<div id="tab-empresa" <?= $tab !== 'empresa' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Dados da Empresa</h5>
  <small class="text-muted">Informações institucionais, logotipo e ícone do sistema</small>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-building text-secondary"></i> Informações da Empresa
      </div>
      <div class="card-body">
        <div id="company-form-alert"></div>
        <form id="company-form" novalidate enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-8">
              <label class="form-label">Razão Social / Nome da Empresa</label>
              <input type="text" name="company_name" class="form-control"
                     placeholder="Empresa Ltda."
                     value="<?= e($cs['company_name'] ?? '') ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">CNPJ</label>
              <input type="text" name="cnpj" class="form-control" id="field-cnpj"
                     placeholder="00.000.000/0000-00"
                     value="<?= e($cs['cnpj'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">E-mail da Empresa</label>
              <input type="email" name="email" class="form-control"
                     placeholder="contato@empresa.com"
                     value="<?= e($cs['email'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Telefone / WhatsApp</label>
              <input type="text" name="phone" class="form-control"
                     placeholder="(11) 90000-0000"
                     value="<?= e($cs['phone'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Endereço</label>
              <input type="text" name="address" class="form-control"
                     placeholder="Rua Exemplo, 100 — Sala 01"
                     value="<?= e($cs['address'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">CEP</label>
              <input type="text" name="zip" class="form-control" id="field-zip"
                     placeholder="00000-000"
                     value="<?= e($cs['zip'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Cidade</label>
              <input type="text" name="city" class="form-control" id="field-city"
                     placeholder="São Paulo"
                     value="<?= e($cs['city'] ?? '') ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Estado (UF)</label>
              <select name="state" class="form-select">
                <option value="">—</option>
                <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                <option value="<?= $uf ?>" <?= ($cs['state'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Logo -->
            <div class="col-md-6">
              <label class="form-label">Logotipo <small class="text-muted">(PNG, JPG, SVG)</small></label>
              <?php if (!empty($cs['logo_path'])): ?>
              <div class="mb-2">
                <img src="<?= url($cs['logo_path']) ?>" alt="Logo atual"
                     style="max-height:60px;max-width:200px;object-fit:contain;border:1px solid #e2e8f0;border-radius:6px;padding:6px;">
              </div>
              <?php endif; ?>
              <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/svg+xml,image/webp">
              <small class="text-muted">Recomendado: 300×80 px, fundo transparente</small>
            </div>

            <!-- Icon/Favicon -->
            <div class="col-md-6">
              <label class="form-label">Ícone / Favicon <small class="text-muted">(PNG, ICO)</small></label>
              <?php if (!empty($cs['icon_path'])): ?>
              <div class="mb-2">
                <img src="<?= url($cs['icon_path']) ?>" alt="Ícone atual"
                     style="max-height:48px;max-width:48px;object-fit:contain;border:1px solid #e2e8f0;border-radius:6px;padding:4px;">
              </div>
              <?php endif; ?>
              <input type="file" name="icon" class="form-control" accept="image/png,image/x-icon,image/jpeg">
              <small class="text-muted">Recomendado: 64×64 px ou 32×32 px</small>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btn-company-save">
              <span class="spinner-border spinner-border-sm d-none me-2" id="company-save-spin"></span>
              <i class="bi bi-floppy me-2"></i> Salvar dados da empresa
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-info"></i> Informações
      </div>
      <div class="card-body small text-muted">
        <p>Os dados aqui cadastrados são utilizados em:</p>
        <ul class="ps-3">
          <li>E-mails transacionais</li>
          <li>Rodapé do sistema</li>
          <li>Relatórios exportados</li>
        </ul>
        <p class="mb-0">O <strong>logotipo</strong> substituirá o nome do sistema na barra lateral. O <strong>ícone</strong> será usado como favicon no navegador.</p>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-empresa -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: Template
═══════════════════════════════════════════════════════════ -->
<div id="tab-template" <?= $tab !== 'template' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Template e Aparência</h5>
  <small class="text-muted">Personalize as cores e adicione CSS customizado ao sistema</small>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-palette text-warning"></i> Personalização Visual
      </div>
      <div class="card-body">
        <div id="template-form-alert"></div>
        <form id="template-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold">Cor Principal</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" name="primary_color" id="primary_color_picker" class="form-control form-control-color"
                       value="<?= e($cs['primary_color'] ?? '#3b82f6') ?>"
                       title="Escolha a cor principal">
                <input type="text" id="primary_color_hex" class="form-control font-monospace"
                       value="<?= e($cs['primary_color'] ?? '#3b82f6') ?>"
                       placeholder="#3b82f6" maxlength="7">
              </div>
              <small class="text-muted">Usada em botões, links e destaques do sistema</small>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">CSS Personalizado</label>
              <textarea name="custom_css" id="custom_css" class="form-control font-monospace"
                        rows="16" placeholder="/* Adicione seu CSS aqui */
/* Exemplos:
.sidebar { background: #1e293b !important; }
.btn-primary { border-radius: 20px; }
*/"
                        style="font-size:.83rem;tab-size:2;"><?= e($cs['custom_css'] ?? '') ?></textarea>
              <small class="text-muted">CSS injetado globalmente em todas as páginas do sistema.</small>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btn-template-save">
              <span class="spinner-border spinner-border-sm d-none me-2" id="template-save-spin"></span>
              <i class="bi bi-floppy me-2"></i> Salvar template
            </button>
            <button type="button" class="btn btn-outline-secondary px-4" id="btn-template-preview">
              <i class="bi bi-eye me-2"></i> Pré-visualizar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-lightbulb text-warning"></i> Dicas de CSS
      </div>
      <div class="card-body small text-muted p-3">
        <p class="fw-semibold mb-1">Sidebar</p>
        <code class="d-block mb-2">.sidebar { background: #1e293b; }</code>
        <p class="fw-semibold mb-1">Botões arredondados</p>
        <code class="d-block mb-2">.btn { border-radius: 20px; }</code>
        <p class="fw-semibold mb-1">Fonte personalizada</p>
        <code class="d-block mb-2">body { font-family: 'Inter', sans-serif; }</code>
        <p class="fw-semibold mb-1">Esconder elementos</p>
        <code class="d-block">.element { display: none !important; }</code>
      </div>
    </div>

    <!-- Live preview -->
    <div class="card mt-3" id="color-preview-card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-eye text-info"></i> Preview da cor
      </div>
      <div class="card-body">
        <div id="color-swatch" style="height:40px;border-radius:8px;background:<?= e($cs['primary_color'] ?? '#3b82f6') ?>;transition:background .2s;"></div>
        <button class="btn btn-sm w-100 mt-2 fw-semibold text-white" id="preview-btn-sample"
                style="background:<?= e($cs['primary_color'] ?? '#3b82f6') ?>;border:none;">
          Botão de exemplo
        </button>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-template -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: WhatsApp
═══════════════════════════════════════════════════════════ -->
<div id="tab-whatsapp" <?= $tab !== 'whatsapp' ? 'style="display:none"' : '' ?>>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Configurações do WhatsApp</h5>
    <small class="text-muted">Integração com a API Oficial da Meta (WhatsApp Business)</small>
  </div>
  <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
       id="status-badge"
       style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;font-size:.82rem;font-weight:600;">
    <span class="rounded-circle" id="status-dot"
          style="width:9px;height:9px;background:<?= $sc['dot'] ?>;display:inline-block;flex-shrink:0;"></span>
    <span id="status-text"><?= $sc['label'] ?></span>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-whatsapp text-success"></i> Credenciais da API Meta
      </div>
      <div class="card-body">
        <div id="form-alert"></div>
        <form id="settings-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label">Nome da Configuração</label>
              <input type="text" name="name" class="form-control"
                     value="<?= e($settings['name'] ?? 'Principal') ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Access Token <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" name="access_token" id="access_token" class="form-control font-monospace"
                       placeholder="EAAxxxxxxxxxxxxxxxx..."
                       value="<?= e($settings['access_token'] ?? '') ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleSecret('access_token','icon-at')">
                  <i class="bi bi-eye" id="icon-at"></i>
                </button>
              </div>
              <div class="invalid-feedback" id="err-access_token"></div>
              <small class="text-muted">Token permanente ou temporário do Meta Developer Portal.</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Verify Token <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" name="verify_token" class="form-control font-monospace"
                       value="<?= e($settings['verify_token'] ?? '') ?>">
                <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                        onclick="generateToken()" title="Gerar token">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
              </div>
              <div class="invalid-feedback" id="err-verify_token"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Versão da API</label>
              <select name="api_version" class="form-select">
                <?php foreach (['v25.0','v24.0','v23.0','v22.0','v21.0','v20.0','v19.0','v18.0'] as $v): ?>
                <option value="<?= $v ?>" <?= ($settings['api_version'] ?? 'v25.0') === $v ? 'selected' : '' ?>>
                  <?= $v ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
              <input type="text" name="phone_number_id" class="form-control font-monospace"
                     value="<?= e($settings['phone_number_id'] ?? '') ?>">
              <div class="invalid-feedback" id="err-phone_number_id"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Business Account ID (WABA)</label>
              <input type="text" name="business_account_id" class="form-control font-monospace"
                     value="<?= e($settings['business_account_id'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">App ID</label>
              <input type="text" name="app_id" class="form-control font-monospace"
                     value="<?= e($settings['app_id'] ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">App Secret</label>
              <div class="input-group">
                <input type="password" name="app_secret" id="app_secret" class="form-control font-monospace"
                       value="<?= e($settings['app_secret'] ?? '') ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleSecret('app_secret','icon-as')">
                  <i class="bi bi-eye" id="icon-as"></i>
                </button>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">URL do Webhook</label>
              <div class="input-group">
                <input type="text" class="form-control font-monospace bg-light"
                       value="<?= e($webhookUrl) ?>" readonly id="webhook-url-input">
                <button type="button" class="btn btn-outline-secondary" onclick="copyWebhook()">
                  <i class="bi bi-clipboard" id="copy-icon"></i>
                </button>
              </div>
              <small class="text-muted">Cole no campo <strong>Callback URL</strong> do App Meta.</small>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btn-save">
              <span class="spinner-border spinner-border-sm d-none me-2" id="save-spin"></span>
              <i class="bi bi-floppy me-2" id="save-icon"></i>
              <span id="save-text">Salvar configurações</span>
            </button>
            <button type="button" class="btn btn-outline-success px-4 fw-semibold" id="btn-test" onclick="testConnection()">
              <span class="spinner-border spinner-border-sm d-none me-2" id="test-spin"></span>
              <i class="bi bi-wifi me-2" id="test-icon"></i>
              <span id="test-text">Testar conexão</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-activity text-primary"></i> Status da Conexão
      </div>
      <div class="card-body">
        <?php if (!empty($settings)): ?>
        <dl class="row mb-0 small">
          <dt class="col-5 text-muted fw-normal">Status</dt>
          <dd class="col-7 fw-semibold">
            <?php if ($st === 'active'): ?>
              <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Ativo</span>
            <?php elseif ($st === 'error'): ?>
              <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Erro</span>
            <?php else: ?>
              <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Não testado</span>
            <?php endif; ?>
          </dd>
          <?php if (!empty($settings['phone_number_id'])): ?>
          <dt class="col-5 text-muted fw-normal mt-1">Phone ID</dt>
          <dd class="col-7 mt-1 font-monospace" style="font-size:.75rem"><?= e($settings['phone_number_id']) ?></dd>
          <?php endif; ?>
          <?php if (!empty($settings['api_version'])): ?>
          <dt class="col-5 text-muted fw-normal mt-1">Versão</dt>
          <dd class="col-7 mt-1"><?= e($settings['api_version']) ?></dd>
          <?php endif; ?>
          <?php if (!empty($settings['last_tested_at'])): ?>
          <dt class="col-5 text-muted fw-normal mt-1">Último teste</dt>
          <dd class="col-7 mt-1"><?= date('d/m/Y H:i', strtotime($settings['last_tested_at'])) ?></dd>
          <?php endif; ?>
          <?php if (!empty($settings['last_error'])): ?>
          <dt class="col-12 text-muted fw-normal mt-2">Último erro</dt>
          <dd class="col-12 mt-1"><div class="alert alert-danger py-2 mb-0 small"><?= e($settings['last_error']) ?></div></dd>
          <?php endif; ?>
        </dl>
        <?php else: ?>
        <div class="text-center text-muted py-3 small">Nenhuma configuração salva ainda.</div>
        <?php endif; ?>
        <div id="test-result" class="mt-3"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-info"></i> Como configurar
      </div>
      <div class="card-body small text-muted p-3">
        <ol class="ps-3 mb-0" style="line-height:2;">
          <li>Acesse <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
          <li>Crie um App do tipo <strong>Business</strong></li>
          <li>Adicione o produto <strong>WhatsApp</strong></li>
          <li>Copie o <strong>Access Token</strong></li>
          <li>Copie o <strong>Phone Number ID</strong> e <strong>WABA ID</strong></li>
          <li>Cole a <strong>URL do Webhook</strong> no App Meta</li>
          <li>Use o mesmo <strong>Verify Token</strong></li>
          <li>Assine o evento: <code>messages</code></li>
        </ol>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-whatsapp -->


<!-- ═══════════════════════════════════════════════════════════
     TAB: SMTP
═══════════════════════════════════════════════════════════ -->
<div id="tab-smtp" <?= $tab !== 'smtp' ? 'style="display:none"' : '' ?>>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Configurações de E-mail (SMTP)</h5>
  <small class="text-muted">Servidor de envio para convites, redefinição de senha e notificações</small>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-envelope-fill text-primary"></i> Servidor SMTP
      </div>
      <div class="card-body">
        <div id="mail-form-alert"></div>
        <form id="mail-settings-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-8">
              <label class="form-label">Host SMTP <span class="text-danger">*</span></label>
              <input type="text" name="mail_host" class="form-control font-monospace"
                     placeholder="smtp.gmail.com"
                     value="<?= e($mailSettings['host'] ?? '') ?>">
              <div class="invalid-feedback" id="err-mail_host"></div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Porta <span class="text-danger">*</span></label>
              <input type="number" name="mail_port" class="form-control"
                     placeholder="587"
                     value="<?= e($mailSettings['port'] ?? 587) ?>">
              <div class="invalid-feedback" id="err-mail_port"></div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Criptografia</label>
              <select name="mail_encryption" class="form-select">
                <?php foreach (['tls'=>'TLS (587)', 'ssl'=>'SSL (465)', 'none'=>'Nenhuma'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= ($mailSettings['encryption'] ?? 'tls') === $v ? 'selected' : '' ?>>
                  <?= $l ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Usuário (e-mail de login)</label>
              <input type="text" name="mail_username" class="form-control"
                     placeholder="seu@email.com"
                     value="<?= e($mailSettings['username'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Senha</label>
              <div class="input-group">
                <input type="password" name="mail_password" id="mail_password" class="form-control"
                       placeholder="Senha ou App Password"
                       value="<?= e($mailSettings['password'] ?? '') ?>">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="toggleSecret('mail_password','icon-mp')">
                  <i class="bi bi-eye" id="icon-mp"></i>
                </button>
              </div>
              <small class="text-muted">Para Gmail use uma <strong>Senha de App</strong> (não a senha da conta).</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Nome do Remetente <span class="text-danger">*</span></label>
              <input type="text" name="mail_from_name" class="form-control"
                     placeholder="ChatBot NowFlow"
                     value="<?= e($mailSettings['from_name'] ?? config('app.name', 'ChatBot')) ?>">
              <div class="invalid-feedback" id="err-mail_from_name"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">E-mail do Remetente <span class="text-danger">*</span></label>
              <input type="email" name="mail_from_address" class="form-control"
                     placeholder="noreply@seudominio.com"
                     value="<?= e($mailSettings['from_address'] ?? '') ?>">
              <div class="invalid-feedback" id="err-mail_from_address"></div>
            </div>

          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btn-mail-save">
              <span class="spinner-border spinner-border-sm d-none me-2" id="mail-save-spin"></span>
              <i class="bi bi-floppy me-2"></i> Salvar configurações
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-activity text-primary"></i> Status
      </div>
      <div class="card-body small">
        <?php if (!empty($mailSettings)): ?>
        <dl class="row mb-0">
          <dt class="col-5 text-muted fw-normal">Host</dt>
          <dd class="col-7 font-monospace"><?= e($mailSettings['host']) ?></dd>
          <dt class="col-5 text-muted fw-normal mt-1">Porta</dt>
          <dd class="col-7 mt-1"><?= e($mailSettings['port']) ?></dd>
          <dt class="col-5 text-muted fw-normal mt-1">Criptografia</dt>
          <dd class="col-7 mt-1"><?= strtoupper(e($mailSettings['encryption'])) ?></dd>
          <dt class="col-5 text-muted fw-normal mt-1">Usuário</dt>
          <dd class="col-7 mt-1 text-truncate"><?= e($mailSettings['username'] ?? '—') ?></dd>
          <dt class="col-5 text-muted fw-normal mt-1">Remetente</dt>
          <dd class="col-7 mt-1 text-truncate"><?= e($mailSettings['from_address'] ?? '—') ?></dd>
        </dl>
        <?php else: ?>
        <p class="text-muted mb-0">Nenhuma configuração salva ainda.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-send text-success"></i> Testar envio
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Enviar e-mail de teste para</label>
          <input type="email" id="test-email-input" class="form-control form-control-sm"
                 placeholder="seu@email.com">
        </div>
        <button class="btn btn-success btn-sm w-100" id="btn-mail-test">
          <span class="spinner-border spinner-border-sm d-none me-1" id="mail-test-spin"></span>
          <i class="bi bi-send-fill me-1"></i> Enviar teste
        </button>
        <div id="mail-test-result" class="mt-3"></div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-lightbulb text-warning"></i> Configurações populares
      </div>
      <div class="card-body small text-muted p-3">
        <p class="fw-semibold mb-1">Gmail</p>
        <code class="d-block">Host: smtp.gmail.com</code>
        <code class="d-block">Porta: 587 / TLS</code>
        <code class="d-block mb-2">Senha: App Password</code>
        <p class="fw-semibold mb-1">Outlook/Hotmail</p>
        <code class="d-block">Host: smtp-mail.outlook.com</code>
        <code class="d-block">Porta: 587 / TLS</code>
        <code class="d-block mb-2">Usuário: seu@outlook.com</code>
        <p class="fw-semibold mb-1">SendGrid</p>
        <code class="d-block">Host: smtp.sendgrid.net</code>
        <code class="d-block">Porta: 587 / TLS</code>
        <code class="d-block">Usuário: apikey</code>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-smtp -->

<!-- ═══════════════════════════════════════════════════════════
     TAB: Atualização (admin only)
═══════════════════════════════════════════════════════════ -->
<?php if (\Core\Auth::isAdmin()): ?>
<div id="tab-atualizacao" <?= $tab !== 'atualizacao' ? 'style="display:none"' : '' ?>>
<?php
$ud = $updateData ?? [];
$gitAvailable  = $ud['gitAvailable']  ?? false;
$gitVersion    = $ud['gitVersion']    ?? null;
$gitBin        = $ud['gitBin']        ?? null;
$hasRepo       = $ud['hasRepo']       ?? false;
$execEnabled   = $ud['execEnabled']   ?? false;
$lastCommit    = $ud['lastCommit']    ?? [];
$localVersion  = $ud['localVersion']  ?? [];
$gitPathHint   = $ud['gitPathHint']   ?? '';
?>

<div class="mb-4">
  <h5 class="fw-bold mb-0">Atualização do Sistema</h5>
  <small class="text-muted">Verifique e aplique atualizações direto do repositório</small>
</div>

<div class="row g-4">
  <div class="col-lg-8">

    <!-- Version comparison -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-layers text-primary"></i> Versões
        </span>
        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2"
                id="btn-check" onclick="checkUpdateStatus()">
          <i class="bi bi-arrow-clockwise" id="check-icon"></i> Verificar
        </button>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="bg-light rounded p-3">
              <div class="text-muted small fw-semibold mb-2"><i class="bi bi-hdd me-1"></i> Versão Instalada</div>
              <?php if (!empty($lastCommit['hash'])): ?>
              <code class="text-primary fw-bold" id="upd-local-hash"><?= e($lastCommit['hash']) ?></code>
              <div class="text-dark small mt-1" id="upd-local-subject"><?= e($lastCommit['subject'] ?? '') ?></div>
              <div class="text-muted mt-1" style="font-size:.72rem;">
                <i class="bi bi-person me-1"></i><span id="upd-local-author"><?= e($lastCommit['author'] ?? '') ?></span>
                &nbsp;·&nbsp;<span id="upd-local-date"><?= e($lastCommit['date'] ?? '') ?></span>
              </div>
              <?php elseif (!empty($localVersion['version'])): ?>
              <div class="fw-bold text-primary" id="upd-local-hash">v<?= e($localVersion['version']) ?></div>
              <div class="text-muted small mt-1" id="upd-local-subject"><?= e($localVersion['changelog'] ?? '') ?></div>
              <div class="text-muted small mt-1" id="upd-local-date"><?= e($localVersion['released_at'] ?? '') ?></div>
              <?php else: ?>
              <div class="text-muted small" id="upd-local-hash">Desconhecida</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="bg-light rounded p-3">
              <div class="text-muted small fw-semibold mb-2"><i class="bi bi-github me-1"></i> Versão no Repositório</div>
              <div id="upd-remote-loading" class="text-muted small">
                <span class="spinner-border spinner-border-sm me-2"></span>Consultando…
              </div>
              <div id="upd-remote-info" style="display:none;">
                <code class="text-success fw-bold" id="upd-remote-hash">—</code>
                <div class="text-dark small mt-1" id="upd-remote-subject">—</div>
                <div class="text-muted mt-1" style="font-size:.72rem;">
                  <i class="bi bi-person me-1"></i><span id="upd-remote-author">—</span>
                  &nbsp;·&nbsp;<span id="upd-remote-date">—</span>
                </div>
              </div>
              <div id="upd-remote-error" style="display:none;" class="text-danger small">
                <i class="bi bi-exclamation-triangle me-1"></i><span id="upd-remote-error-msg"></span>
              </div>
            </div>
          </div>
        </div>
        <div id="upd-status-banner" class="mt-3" style="display:none;">
          <div id="upd-status-inner" class="alert py-2 small mb-0 d-flex align-items-center gap-2"></div>
        </div>
      </div>
    </div>

    <!-- Pull action -->
    <?php if ($gitAvailable): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-cloud-download text-success"></i> Atualizar Sistema
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          Executa <code>git pull origin main</code> no servidor e recarrega a página automaticamente.
        </p>
        <div id="upd-pull-alert" class="mb-3" style="display:none;"></div>
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-primary fw-semibold d-flex align-items-center gap-2"
                  id="btn-upd-pull" onclick="runSystemPull()">
            <span class="spinner-border spinner-border-sm d-none" id="upd-pull-spinner"></span>
            <i class="bi bi-cloud-download-fill" id="upd-pull-icon"></i>
            <span id="upd-pull-text">Atualizar Agora</span>
          </button>
          <small class="text-muted">Recomenda-se backup antes de atualizar.</small>
        </div>
      </div>
    </div>
    <div class="card mb-4" id="upd-output-card" style="display:none;">
      <div class="card-header bg-dark py-2 px-3 d-flex align-items-center gap-2" style="border-radius:.375rem .375rem 0 0;">
        <i class="bi bi-terminal-fill text-success small"></i>
        <span class="text-white small fw-semibold">Saída do Git</span>
      </div>
      <div class="card-body p-0">
        <pre id="upd-git-output" style="margin:0;padding:1rem;background:#1e1e1e;color:#d4d4d4;font-size:.8rem;border-radius:0 0 .375rem .375rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all;"></pre>
      </div>
    </div>
    <?php else: ?>
    <!-- Diagnostics -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex align-items-center gap-2 text-warning">
        <i class="bi bi-exclamation-triangle"></i> Atualização automática não disponível
      </div>
      <div class="card-body d-flex flex-column gap-3">
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:<?= $execEnabled ? '#f0fdf4' : '#fef2f2' ?>">
          <i class="bi <?= $execEnabled ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
          <div>
            <div class="fw-semibold small"><?= $execEnabled ? 'Execução de comandos: OK' : 'Execução de comandos: Bloqueada' ?></div>
            <?php if (!$execEnabled): ?>
            <div class="text-muted small mt-1">
              No cPanel: <strong>Software → Select PHP Version → aba "Options"</strong> → remova <code>shell_exec</code>, <code>exec</code> e <code>proc_open</code> de <code>disable_functions</code>.
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:<?= $gitBin ? '#f0fdf4' : '#fef2f2' ?>">
          <i class="bi <?= $gitBin ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
          <div>
            <div class="fw-semibold small"><?= $gitBin ? 'Git: ' . e($gitBin) : 'Git: não encontrado' ?></div>
            <?php if (!$gitBin): ?>
            <div class="text-muted small mt-1">
              Via SSH: <code>which git</code> — copie o caminho e adicione ao <code>.env</code>:<br>
              <code>GIT_PATH=/caminho/do/git</code>
              <?php if ($gitPathHint): ?><br><span class="text-warning">Configurado: <code><?= e($gitPathHint) ?></code> (não acessível pelo PHP)</span><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:<?= $hasRepo ? '#f0fdf4' : '#fef2f2' ?>">
          <i class="bi <?= $hasRepo ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5 mt-1 flex-shrink-0"></i>
          <div>
            <div class="fw-semibold small"><?= $hasRepo ? 'Repositório Git: inicializado' : 'Repositório Git: não encontrado' ?></div>
            <?php if (!$hasRepo): ?>
            <div class="text-muted small mt-1">
              Via SSH:<br>
              <code>cd <?= e(dirname(PUBLIC_PATH)) ?></code><br>
              <code>git init && git remote add origin https://github.com/nowflowia/chatbot.git</code><br>
              <code>git fetch && git reset --hard origin/main</code>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="p-3 bg-light rounded">
          <div class="fw-semibold small mb-1"><i class="bi bi-terminal me-1"></i> Atualização manual via SSH</div>
          <pre class="mb-1" style="font-size:.8rem;background:transparent;">cd <?= e(dirname(PUBLIC_PATH)) ?>

git pull origin main</pre>
          <div class="text-muted small">Se der erro de branch: <code>git branch --set-upstream-to=origin/main main && git pull</code></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Environment info -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-info"></i> Ambiente
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr><td class="text-muted ps-3">PHP</td><td class="fw-semibold"><?= phpversion() ?></td></tr>
            <tr><td class="text-muted ps-3">Servidor</td><td class="fw-semibold"><?= e(preg_replace('/\/.+/', '', $_SERVER['SERVER_SOFTWARE'] ?? 'N/A')) ?></td></tr>
            <tr><td class="text-muted ps-3">Git</td><td class="fw-semibold"><?= e($gitVersion ?? 'Não disponível') ?></td></tr>
            <tr><td class="text-muted ps-3">Repositório</td><td><?= $hasRepo ? '<span class="text-success fw-semibold">OK</span>' : '<span class="text-danger fw-semibold">Não init.</span>' ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-atualizacao -->
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     TAB: API
═══════════════════════════════════════════════════════════ -->
<div id="tab-api" <?= $tab !== 'api' ? 'style="display:none"' : '' ?>>
<?php $csrf = \Core\CSRF::token(); ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-key-fill text-danger"></i>
        <span class="fw-semibold">Suas chaves de API</span>
        <a href="<?= url('admin/api-docs') ?>" class="btn btn-sm btn-outline-primary ms-auto" target="_blank">
          <i class="bi bi-book me-1"></i>Documentação
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($apiKeys)): ?>
        <p class="text-muted p-3 mb-0">Nenhuma chave criada ainda.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="api-keys-table">
            <thead class="table-light"><tr>
              <th>Nome</th><th>Chave</th><th>Último uso</th><th>Expira</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($apiKeys as $ak): ?>
            <tr id="apikey-row-<?= $ak['id'] ?>">
              <td class="small fw-semibold"><?= e($ak['name']) ?></td>
              <td>
                <div class="input-group input-group-sm" style="max-width:240px">
                  <input type="password" class="form-control font-monospace"
                         value="<?= e($ak['key']) ?>" id="apikey-<?= $ak['id'] ?>" readonly style="font-size:.72rem">
                  <button class="btn btn-outline-secondary" type="button"
                          onclick="toggleApiKey(<?= $ak['id'] ?>)" title="Mostrar/ocultar">
                    <i class="bi bi-eye" id="eye-<?= $ak['id'] ?>"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button"
                          onclick="copyApiKey(<?= $ak['id'] ?>)" title="Copiar">
                    <i class="bi bi-clipboard" id="clip-<?= $ak['id'] ?>"></i>
                  </button>
                </div>
              </td>
              <td class="small text-muted"><?= $ak['last_used_at'] ? date('d/m/Y H:i', strtotime($ak['last_used_at'])) : 'Nunca' ?></td>
              <td class="small"><?= $ak['expires_at'] ? date('d/m/Y', strtotime($ak['expires_at'])) : '∞' ?></td>
              <td>
                <span class="badge <?= $ak['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>" id="apikey-badge-<?= $ak['id'] ?>">
                  <?= $ak['is_active'] ? 'Ativa' : 'Inativa' ?>
                </span>
              </td>
              <td class="text-end pe-2" style="white-space:nowrap">
                <button class="btn btn-sm btn-outline-warning" onclick="toggleKeyStatus(<?= $ak['id'] ?>)" title="Ativar/desativar">
                  <i class="bi bi-toggle-<?= $ak['is_active'] ? 'on' : 'off' ?>" id="toggle-icon-<?= $ak['id'] ?>"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteApiKey(<?= $ak['id'] ?>)" title="Remover">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-plus-circle me-2"></i>Nova chave de API</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label form-label-sm fw-semibold">Nome <span class="text-danger">*</span></label>
            <input type="text" id="apikey-name" class="form-control form-control-sm"
                   placeholder="Ex: App Mobile, Integração Zapier…">
          </div>
          <div class="col-md-5">
            <label class="form-label form-label-sm fw-semibold">Expiração (opcional)</label>
            <input type="date" id="apikey-expires" class="form-control form-control-sm">
          </div>
        </div>
        <button class="btn btn-danger btn-sm mt-3" id="btn-create-apikey">
          <i class="bi bi-key me-1"></i>Gerar chave
        </button>
        <div id="apikey-result" class="mt-3"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Como usar</div>
      <div class="card-body small">
        <p class="mb-2 fw-semibold">Autenticação via header:</p>
        <div class="bg-dark text-light rounded p-2 font-monospace mb-3" style="font-size:.75rem;word-break:break-all;">
          Authorization: Bearer <span class="text-warning">{sua_chave}</span>
        </div>
        <p class="mb-2 fw-semibold">Ou via header alternativo:</p>
        <div class="bg-dark text-light rounded p-2 font-monospace mb-3" style="font-size:.75rem;word-break:break-all;">
          X-API-Key: <span class="text-warning">{sua_chave}</span>
        </div>
        <p class="mb-1 fw-semibold">Base URL:</p>
        <div class="bg-dark text-light rounded p-2 font-monospace mb-3" style="font-size:.75rem;word-break:break-all;">
          <?= rtrim(url('api/v1'), '/') ?>
        </div>
        <a href="<?= url('admin/api-docs') ?>" class="btn btn-outline-primary btn-sm w-100" target="_blank">
          <i class="bi bi-book me-1"></i>Ver documentação completa
        </a>
      </div>
    </div>
  </div>
</div>
</div><!-- /tab-api -->

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
(function () {
'use strict';
var BASE = '<?= url('admin') ?>';

// ── Empresa form ───────────────────────────────────────────────
document.getElementById('company-form').addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-company-save');
  var spin = document.getElementById('company-save-spin');
  btn.disabled = true;
  spin.classList.remove('d-none');

  // Use FormData for file uploads
  var fd = new FormData(this);
  fetch('<?= url('admin/settings/company') ?>', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: fd
  })
  .then(function (r) { return r.json(); })
  .then(function (res) {
    btn.disabled = false;
    spin.classList.add('d-none');
    if (res.success) {
      Toast.show(res.message, 'success');
      setTimeout(function () { window.location.reload(); }, 1200);
    } else {
      Toast.show(res.message || 'Erro ao salvar.', 'danger');
    }
  })
  .catch(function () {
    btn.disabled = false;
    spin.classList.add('d-none');
    Toast.show('Erro de conexão. Tente novamente.', 'error');
  });
});

// CNPJ mask
(function () {
  var field = document.getElementById('field-cnpj');
  if (!field) return;
  field.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').slice(0, 14);
    if (v.length > 12) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
    else if (v.length > 8) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
    else if (v.length > 5) v = v.replace(/^(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
    else if (v.length > 2) v = v.replace(/^(\d{2})(\d{0,3})/, '$1.$2');
    this.value = v;
  });
})();

// CEP auto-fill
(function () {
  var zipField  = document.getElementById('field-zip');
  var cityField = document.getElementById('field-city');
  if (!zipField) return;

  zipField.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    this.value = v;
  });

  zipField.addEventListener('blur', function () {
    var cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + cep + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro) return;
        if (cityField) cityField.value = d.localidade || '';
        var stateEl = document.querySelector('[name="state"]');
        if (stateEl) stateEl.value = d.uf || '';
        var addrEl = document.querySelector('[name="address"]');
        if (addrEl && !addrEl.value) addrEl.value = (d.logradouro || '') + (d.bairro ? ' — ' + d.bairro : '');
      })
      .catch(function () {});
  });
})();

// ── Template form ──────────────────────────────────────────────
(function () {
  var picker  = document.getElementById('primary_color_picker');
  var hexInp  = document.getElementById('primary_color_hex');
  var swatch  = document.getElementById('color-swatch');
  var sample  = document.getElementById('preview-btn-sample');

  function applyColor(color) {
    if (swatch) swatch.style.background = color;
    if (sample) sample.style.background = color;
  }

  if (picker) {
    picker.addEventListener('input', function () {
      if (hexInp) hexInp.value = this.value;
      applyColor(this.value);
    });
  }

  if (hexInp) {
    hexInp.addEventListener('input', function () {
      var v = this.value.trim();
      if (/^#[0-9a-fA-F]{6}$/.test(v)) {
        if (picker) picker.value = v;
        applyColor(v);
      }
    });
  }
})();

document.getElementById('template-form').addEventListener('submit', function (e) {
  e.preventDefault();
  var btn  = document.getElementById('btn-template-save');
  var spin = document.getElementById('template-save-spin');
  btn.disabled = true;
  spin.classList.remove('d-none');

  // sync hex input → hidden field name
  var hexInp = document.getElementById('primary_color_hex');
  var picker  = document.getElementById('primary_color_picker');
  if (hexInp && picker) {
    var v = hexInp.value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(v)) picker.value = v;
  }

  Api.formPost('<?= url('admin/settings/template') ?>', this, function (res) {
    btn.disabled = false;
    spin.classList.add('d-none');
    if (!res) return;
    if (res.success) {
      Toast.show(res.message, 'success');
      setTimeout(function () { window.location.reload(); }, 1200);
    } else {
      Toast.show(res.message || 'Erro ao salvar.', 'danger');
    }
  });
});

document.getElementById('btn-template-preview').addEventListener('click', function () {
  var css = document.getElementById('custom_css').value;
  var id  = 'preview-style-inject';
  var el  = document.getElementById(id);
  if (!el) { el = document.createElement('style'); el.id = id; document.head.appendChild(el); }
  el.textContent = css;
  Toast.show('CSS aplicado para pré-visualização (não salvo).', 'info', 3000);
});

// ── WhatsApp form ──────────────────────────────────────────────
document.getElementById('settings-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErrors('wa');
  FormHelper.setLoading(document.getElementById('btn-save'), true);
  document.getElementById('save-text').textContent = 'Salvando…';

  Api.formPost('<?= url('admin/settings') ?>', this, function (res) {
    FormHelper.setLoading(document.getElementById('btn-save'), false);
    document.getElementById('save-text').textContent = 'Salvar configurações';
    if (!res) return;
    if (res.success) {
      Toast.show(res.message, 'success');
    } else {
      showErrors('wa', res.errors || {});
      Toast.show(res.message, 'danger');
    }
  });
});

function testConnection() {
  var btn  = document.getElementById('btn-test');
  var spin = document.getElementById('test-spin');
  var icon = document.getElementById('test-icon');
  var box  = document.getElementById('test-result');
  btn.disabled = true;
  spin.classList.remove('d-none'); icon.className = 'd-none';
  document.getElementById('test-text').textContent = 'Testando…';
  box.innerHTML = '';

  Api.post(BASE + '/settings/test', {}).then(function (res) {
    btn.disabled = false;
    spin.classList.add('d-none'); icon.className = 'bi bi-wifi me-2';
    document.getElementById('test-text').textContent = 'Testar conexão';
    if (res.success) {
      box.innerHTML = mkAlert('success', res.message);
      updateStatusBadge('#dcfce7','#166534','#16a34a','Conectado');
    } else {
      box.innerHTML = mkAlert('danger', res.message);
      updateStatusBadge('#fee2e2','#991b1b','#dc2626','Erro de conexão');
    }
  });
}
window.testConnection = testConnection;

function updateStatusBadge(bg, color, dot, label) {
  var badge = document.getElementById('status-badge');
  var dotEl = document.getElementById('status-dot');
  var txtEl = document.getElementById('status-text');
  if (badge) { badge.style.background = bg; badge.style.color = color; }
  if (dotEl) dotEl.style.background = dot;
  if (txtEl) txtEl.textContent = label;
}

// ── SMTP form ─────────────────────────────────────────────────
document.getElementById('mail-settings-form').addEventListener('submit', function (e) {
  e.preventDefault();
  clearErrors('mail');
  var btn = document.getElementById('btn-mail-save');
  btn.disabled = true;
  document.getElementById('mail-save-spin').classList.remove('d-none');

  Api.formPost(BASE + '/settings/mail', this, function (res) {
    btn.disabled = false;
    document.getElementById('mail-save-spin').classList.add('d-none');
    if (!res) return;
    if (res.success) {
      Toast.show(res.message, 'success');
    } else {
      showErrors('mail', res.errors || {});
      Toast.show(res.message, 'danger');
    }
  });
});

document.getElementById('btn-mail-test').addEventListener('click', function () {
  var to   = document.getElementById('test-email-input').value.trim();
  var box  = document.getElementById('mail-test-result');
  var spin = document.getElementById('mail-test-spin');
  if (!to) { Toast.show('Informe um e-mail para o teste.', 'warning'); return; }

  this.disabled = true;
  spin.classList.remove('d-none');
  box.innerHTML = '';

  Api.post(BASE + '/settings/mail/test', { test_email: to }).then(function (res) {
    document.getElementById('btn-mail-test').disabled = false;
    spin.classList.add('d-none');
    box.innerHTML = mkAlert(res.success ? 'success' : 'danger', res.message);
  });
});

// ── Helpers ───────────────────────────────────────────────────
function mkAlert(type, msg) {
  return '<div class="alert alert-' + type + ' py-2 small d-flex gap-2 align-items-start">'
    + '<i class="bi bi-' + (type==='success'?'check':'x') + '-circle-fill mt-1 flex-shrink-0"></i>'
    + '<span>' + msg + '</span></div>';
}

function showErrors(ns, errors) {
  Object.entries(errors).forEach(function ([field, msgs]) {
    var key = ns === 'mail' ? 'mail_' + field : field;
    var el  = document.getElementById('err-' + key) || document.getElementById('err-' + field);
    var inp = document.querySelector('[name="' + key + '"]') || document.querySelector('[name="' + field + '"]');
    if (el && msgs.length) { el.textContent = msgs[0]; el.style.display = 'block'; }
    if (inp) inp.classList.add('is-invalid');
  });
}

function clearErrors() {
  document.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
  document.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; el.style.display = 'none'; });
}

function toggleSecret(inputId, iconId) {
  var inp = document.getElementById(inputId);
  var ico = document.getElementById(iconId);
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
}
window.toggleSecret = toggleSecret;

function generateToken() {
  var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  var token = '';
  for (var i = 0; i < 32; i++) token += chars[Math.floor(Math.random() * chars.length)];
  document.querySelector('[name="verify_token"]').value = token;
  Toast.show('Novo token gerado!', 'info', 2000);
}
window.generateToken = generateToken;

function copyWebhook() {
  var val = document.getElementById('webhook-url-input').value;
  navigator.clipboard.writeText(val).then(function () {
    document.getElementById('copy-icon').className = 'bi bi-check2 text-success';
    setTimeout(function () { document.getElementById('copy-icon').className = 'bi bi-clipboard'; }, 2000);
    Toast.show('URL copiada!', 'info', 2000);
  });
}
window.copyWebhook = copyWebhook;

})();
</script>

<?php if (\Core\Auth::isAdmin()): ?>
<style>@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }</style>
<script>
(function () {
const UPD_CSRF  = '<?= csrf_token() ?>';
const UPD_URLS  = {
  status: '<?= url('admin/system-update/status') ?>',
  pull:   '<?= url('admin/system-update/pull') ?>',
};

// Auto-check when tab is active
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('tab-atualizacao') &&
      document.getElementById('tab-atualizacao').style.display !== 'none') {
    checkUpdateStatus();
  }
});

window.checkUpdateStatus = function () {
  const btn  = document.getElementById('btn-check');
  const icon = document.getElementById('check-icon');
  if (btn)  btn.disabled = true;
  if (icon) icon.style.animation = 'spin 1s linear infinite';
  updShowRemoteLoading();
  updHideBanner();

  const fd = new FormData();
  fd.append('_csrf_token', UPD_CSRF);
  fetch(UPD_URLS.status, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(res => {
      if (btn)  btn.disabled = false;
      if (icon) icon.style.animation = '';
      if (!res.success) { updShowRemoteError(res.message || 'Erro ao verificar.'); return; }
      const d = res.data;
      updShowRemoteCommit(d.remote_commit, d.remote_hash);
      updUpdateLocal(d.last_commit, d.local_hash);
      if (d.up_to_date) {
        updShowBanner('success', '<i class="bi bi-check-circle-fill"></i> Sistema atualizado — você está na versão mais recente.');
      } else {
        const n = d.pending || '';
        updShowBanner('warning', '<i class="bi bi-arrow-up-circle-fill"></i> ' + (n ? n + ' commit(s) disponível(is).' : 'Nova versão disponível.'));
      }
    })
    .catch(() => {
      if (btn)  btn.disabled = false;
      if (icon) icon.style.animation = '';
      updShowRemoteError('Não foi possível consultar o GitHub.');
    });
};

window.runSystemPull = function () {
  const btn   = document.getElementById('btn-upd-pull');
  const spin  = document.getElementById('upd-pull-spinner');
  const icon  = document.getElementById('upd-pull-icon');
  const txt   = document.getElementById('upd-pull-text');
  const alert = document.getElementById('upd-pull-alert');
  const outCard = document.getElementById('upd-output-card');
  const output  = document.getElementById('upd-git-output');

  btn.disabled = true; spin.classList.remove('d-none');
  icon.style.display = 'none'; txt.textContent = 'Atualizando…';
  alert.style.display = 'none';

  const fd = new FormData();
  fd.append('_csrf_token', UPD_CSRF);
  fetch(UPD_URLS.pull, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; spin.classList.add('d-none');
      icon.style.display = ''; txt.textContent = 'Atualizar Agora';
      if (res.data?.output) { output.textContent = res.data.output; outCard.style.display = ''; }
      alert.style.display = '';
      if (res.success) {
        alert.innerHTML = '<div class="alert alert-success py-2 small d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>' + res.message + ' Recarregando em 3 segundos…</span></div>';
        updUpdateLocal(res.data?.last_commit, '');
        updShowBanner('success', '<i class="bi bi-check-circle-fill"></i> ' + res.message);
        setTimeout(() => location.reload(), 3000);
      } else {
        alert.innerHTML = '<div class="alert alert-danger py-2 small d-flex gap-2"><i class="bi bi-exclamation-triangle-fill mt-1"></i><span>' + res.message + '</span></div>';
      }
    })
    .catch(() => {
      btn.disabled = false; spin.classList.add('d-none');
      icon.style.display = ''; txt.textContent = 'Atualizar Agora';
      alert.style.display = '';
      alert.innerHTML = '<div class="alert alert-danger py-2 small">Erro de conexão.</div>';
    });
};

function updShowRemoteLoading() {
  g('upd-remote-loading').style.display = '';
  g('upd-remote-info').style.display    = 'none';
  g('upd-remote-error').style.display   = 'none';
}
function updShowRemoteCommit(c, hash) {
  g('upd-remote-loading').style.display = 'none';
  g('upd-remote-info').style.display    = '';
  g('upd-remote-error').style.display   = 'none';
  g('upd-remote-hash').textContent    = hash || (c?.sha||'').substring(0,7) || '—';
  g('upd-remote-subject').textContent = c?.message || '—';
  g('upd-remote-author').textContent  = c?.author  || '—';
  g('upd-remote-date').textContent    = c?.date ? new Date(c.date).toLocaleDateString('pt-BR') : '—';
}
function updShowRemoteError(msg) {
  g('upd-remote-loading').style.display = 'none';
  g('upd-remote-info').style.display    = 'none';
  g('upd-remote-error').style.display   = '';
  g('upd-remote-error-msg').textContent = msg;
}
function updUpdateLocal(commit, hash) {
  if (!commit) return;
  const h = g('upd-local-hash');
  const s = g('upd-local-subject');
  const a = g('upd-local-author');
  const d = g('upd-local-date');
  if (h) h.textContent = commit.hash    || hash || '—';
  if (s) s.textContent = commit.subject || commit.changelog || '—';
  if (a) a.textContent = commit.author  || '—';
  if (d) d.textContent = commit.date    || commit.released_at || '—';
}
function updShowBanner(type, html) {
  const b = g('upd-status-banner'); const i = g('upd-status-inner');
  if (!b||!i) return;
  i.className = 'alert py-2 small mb-0 d-flex align-items-center gap-2 alert-' + type;
  i.innerHTML = html; b.style.display = '';
}
function updHideBanner() { const b = g('upd-status-banner'); if (b) b.style.display = 'none'; }
function g(id) { return document.getElementById(id); }
})();
</script>
<?php endif; ?>

<script>
// ── API Keys ───────────────────────────────────────────────────
var API_CSRF = '<?= \Core\CSRF::token() ?>';
var API_BASE = '<?= url('admin') ?>';

function toggleApiKey(id) {
  var el  = document.getElementById('apikey-' + id);
  var eye = document.getElementById('eye-' + id);
  if (el.type === 'password') { el.type = 'text'; eye.className = 'bi bi-eye-slash'; }
  else { el.type = 'password'; eye.className = 'bi bi-eye'; }
}

function copyApiKey(id) {
  var el   = document.getElementById('apikey-' + id);
  var clip = document.getElementById('clip-' + id);
  navigator.clipboard.writeText(el.value).then(function () {
    clip.className = 'bi bi-check2 text-success';
    setTimeout(function () { clip.className = 'bi bi-clipboard'; }, 2000);
  });
}

function toggleKeyStatus(id) {
  fetch(API_BASE + '/settings/api-keys/' + id + '/toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': API_CSRF },
    body: JSON.stringify({ _token: API_CSRF })
  }).then(r => r.json()).then(res => {
    if (!res.success) { alert(res.message); return; }
    var badge  = document.getElementById('apikey-badge-' + id);
    var icon   = document.getElementById('toggle-icon-' + id);
    if (res.data.is_active) {
      badge.className = 'badge text-bg-success'; badge.textContent = 'Ativa';
      icon.className  = 'bi bi-toggle-on';
    } else {
      badge.className = 'badge text-bg-secondary'; badge.textContent = 'Inativa';
      icon.className  = 'bi bi-toggle-off';
    }
  });
}

function deleteApiKey(id) {
  if (!confirm('Remover esta chave de API? Aplicativos que a usam perderão acesso imediatamente.')) return;
  fetch(API_BASE + '/settings/api-keys/' + id + '/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': API_CSRF },
    body: JSON.stringify({ _token: API_CSRF })
  }).then(r => r.json()).then(res => {
    if (res.success) { document.getElementById('apikey-row-' + id)?.remove(); }
    else alert(res.message);
  });
}

document.getElementById('btn-create-apikey')?.addEventListener('click', function () {
  var name    = document.getElementById('apikey-name').value.trim();
  var expires = document.getElementById('apikey-expires').value;
  var result  = document.getElementById('apikey-result');
  if (!name) { result.innerHTML = '<div class="alert alert-danger py-2 small">Informe um nome para a chave.</div>'; return; }
  this.disabled = true;
  fetch(API_BASE + '/settings/api-keys', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': API_CSRF },
    body: JSON.stringify({ name: name, expires_at: expires || null, _token: API_CSRF })
  }).then(r => r.json()).then(res => {
    this.disabled = false;
    if (!res.success) { result.innerHTML = '<div class="alert alert-danger py-2 small">' + res.message + '</div>'; return; }
    var k = res.data.api_key;
    result.innerHTML = '<div class="alert alert-success py-2 small">'
      + '<strong>Chave criada! Copie agora — ela não será exibida novamente.</strong><br>'
      + '<div class="input-group input-group-sm mt-2" style="max-width:420px">'
      + '<input type="text" class="form-control font-monospace" value="' + k.key + '" id="new-key-val" readonly style="font-size:.72rem">'
      + '<button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'new-key-val\').value)">'
      + '<i class="bi bi-clipboard"></i></button></div></div>';
    document.getElementById('apikey-name').value = '';
    document.getElementById('apikey-expires').value = '';
    setTimeout(() => location.reload(), 4000);
  }).catch(() => { this.disabled = false; });
});
</script>

<?php \Core\View::endSection() ?>
