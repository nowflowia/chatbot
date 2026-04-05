<?php
// Shared fields for create/edit license form
// $edit is available when editing (or null for create)
$f = $edit ?? [];
?>
<div class="row g-3">
  <div class="col-12">
    <label class="form-label fw-semibold small">Domínio <span class="text-danger">*</span></label>
    <input type="text" name="domain" class="form-control form-control-sm font-monospace"
           placeholder="cliente.com.br" value="<?= e($f['domain'] ?? '') ?>" required>
    <div class="form-text">Sem http:// e sem www. Ex: cliente.com.br</div>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Plano</label>
    <select name="plan" class="form-select form-select-sm">
      <?php foreach (['trial'=>'Trial','basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $v => $l): ?>
      <option value="<?= $v ?>" <?= ($f['plan'] ?? 'trial') === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Status</label>
    <select name="status" class="form-select form-select-sm">
      <?php foreach (['trial'=>'Trial','active'=>'Ativo','suspended'=>'Suspenso','expired'=>'Expirado'] as $v => $l): ?>
      <option value="<?= $v ?>" <?= ($f['status'] ?? 'trial') === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Máx. usuários</label>
    <input type="number" name="max_users" class="form-control form-control-sm"
           value="<?= (int)($f['max_users'] ?? 3) ?>" min="1">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Máx. fluxos</label>
    <input type="number" name="max_flows" class="form-control form-control-sm"
           value="<?= (int)($f['max_flows'] ?? 10) ?>" min="1">
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Expira em</label>
    <input type="date" name="expires_at" class="form-control form-control-sm"
           value="<?= e($f['expires_at'] ?? '') ?>">
    <div class="form-text">Deixe vazio para nunca expirar</div>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold small">Features extras</label>
    <input type="text" name="features" class="form-control form-control-sm"
           placeholder="reports, api_access, ..."
           value="<?= e(implode(', ', json_decode($f['features'] ?? '[]', true))) ?>">
    <div class="form-text">Separadas por vírgula</div>
  </div>
  <div class="col-12">
    <label class="form-label fw-semibold small">Observações internas</label>
    <textarea name="notes" class="form-control form-control-sm" rows="2"
              placeholder="Cliente, contrato, etc."><?= e($f['notes'] ?? '') ?></textarea>
  </div>
</div>
