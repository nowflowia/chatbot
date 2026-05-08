<?php \Core\View::section('title') ?>Instagram — Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Instagram — Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Instagram — Posts Orgânicos</h5>
    <small class="text-muted">Crie, agende e publique posts no Instagram Business</small>
  </div>
  <button class="btn btn-primary d-flex align-items-center gap-2"
          data-bs-toggle="modal" data-bs-target="#postModal">
    <i class="bi bi-plus-lg"></i> Novo Post
  </button>
</div>

<!-- Stats rápidos -->
<?php
$statusGroups = ['draft'=>0,'scheduled'=>0,'published'=>0,'failed'=>0];
foreach ($posts as $p) {
    $s = $p['status'] ?? 'draft';
    if (isset($statusGroups[$s])) $statusGroups[$s]++;
}
$statItems = [
    ['Rascunhos','draft','secondary','bi-file-earmark-text'],
    ['Agendados','scheduled','info','bi-clock'],
    ['Publicados','published','success','bi-check-circle-fill'],
    ['Com falha','failed','danger','bi-exclamation-triangle-fill'],
];
?>
<div class="row g-3 mb-4">
<?php foreach ($statItems as [$label,$key,$color,$icon]): ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <i class="bi <?= $icon ?> fs-4 text-<?= $color ?> mb-1"></i>
      <div class="fs-4 fw-bold"><?= $statusGroups[$key] ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- Grid de posts -->
<?php if (empty($posts)): ?>
<div class="text-center text-muted py-5">
  <i class="bi bi-instagram fs-1 d-block mb-3 opacity-50" style="color:#e1306c;"></i>
  <p class="mb-0">Nenhum post criado ainda. Clique em <strong>Novo Post</strong> para começar.</p>
</div>
<?php else: ?>
<div class="row g-3" id="posts-grid">
  <?php foreach ($posts as $p): ?>
  <?= postCard($p) ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ================================================================
     MODAL: Novo Post
================================================================ -->
<div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-instagram me-2" style="color:#e1306c;"></i>Novo Post
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div id="post-alert"></div>
        <form id="post-form" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Nome da Campanha <span class="text-muted fw-normal">(opcional)</span></label>
              <input type="text" name="campaign_name" class="form-control" placeholder="Ex.: Lançamento Produto X">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Tipo de Formato <span class="text-danger">*</span></label>
              <select name="media_type" id="p-media_type" class="form-select" onchange="onTypeChange()">
                <option value="IMAGE">🖼️ Foto (Feed)</option>
                <option value="CAROUSEL">🎠 Carrossel (múltiplas fotos)</option>
                <option value="VIDEO">🎬 Vídeo (Feed)</option>
                <option value="REELS">🎥 Reels</option>
                <option value="STORIES">📖 Stories</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small" id="url-label">
                URL da Imagem <span class="text-danger">*</span>
              </label>
              <textarea name="media_urls" id="p-media_urls" class="form-control font-monospace" rows="2"
                        placeholder="https://exemplo.com/imagem.jpg"></textarea>
              <div class="form-text" id="url-hint">URL pública da imagem (JPEG ou PNG).</div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Legenda</label>
              <textarea name="caption" id="p-caption" class="form-control" rows="4"
                        placeholder="Escreva a legenda do post..."
                        oninput="updatePreview()"></textarea>
              <div class="d-flex justify-content-between">
                <div class="form-text">Máx. recomendado: 2.200 caracteres.</div>
                <small class="text-muted mt-1" id="caption-count">0</small>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold small">Hashtags</label>
              <input type="text" name="hashtags" id="p-hashtags" class="form-control"
                     placeholder="#produto #marca #lancamento"
                     oninput="updatePreview()">
              <div class="form-text">Serão adicionadas ao final da legenda.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold small">Agendar para <span class="text-muted fw-normal">(opcional)</span></label>
              <input type="datetime-local" name="scheduled_at" class="form-control">
              <div class="form-text">Deixe em branco para salvar como rascunho.</div>
            </div>

            <!-- Preview -->
            <div class="col-12" id="preview-wrap" style="display:none;">
              <label class="form-label fw-semibold small text-muted">Pré-visualização da legenda</label>
              <div class="p-3 rounded border" style="background:#fafafa;font-size:.85rem;white-space:pre-wrap;max-height:120px;overflow-y:auto;"
                   id="preview-caption"></div>
            </div>

          </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btn-save-post" onclick="savePost()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="post-spinner"></span>
          Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirmar publicação
================================================================ -->
<div class="modal fade" id="publishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-instagram text-white fs-5"></i>
        </div>
        <h6 class="fw-bold">Publicar agora?</h6>
        <p class="text-muted small mb-0">O post será enviado imediatamente ao Instagram.</p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-confirm-publish"
                style="background:linear-gradient(135deg,#833ab4,#fd1d1d);border:none;">
          <span class="spinner-border spinner-border-sm d-none me-1" id="pub-spinner"></span>
          Publicar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     MODAL: Confirmar exclusão
================================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
          <i class="bi bi-trash-fill text-danger fs-5"></i>
        </div>
        <h6 class="fw-bold">Excluir post?</h6>
        <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger px-4 fw-semibold" id="btn-confirm-delete">
          <span class="spinner-border spinner-border-sm d-none me-1" id="del-spinner"></span>
          Excluir
        </button>
      </div>
    </div>
  </div>
</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
const IG_MK = {
  posts:   '<?= url('admin/marketing/instagram/posts') ?>',
  csrf:    '<?= csrf_token() ?>',
};

let publishModal, deleteModal;

document.addEventListener('DOMContentLoaded', () => {
  publishModal = new bootstrap.Modal(document.getElementById('publishModal'));
  deleteModal  = new bootstrap.Modal(document.getElementById('deleteModal'));
  document.getElementById('postModal').addEventListener('hidden.bs.modal', resetPostForm);
  document.getElementById('p-caption').addEventListener('input', () => {
    document.getElementById('caption-count').textContent = document.getElementById('p-caption').value.length;
  });
});

function onTypeChange() {
  const type = document.getElementById('p-media_type').value;
  const urlLabel = document.getElementById('url-label');
  const urlHint  = document.getElementById('url-hint');

  const cfg = {
    IMAGE:    ['URL da Imagem', 'URL pública da imagem (JPEG ou PNG). Ex.: https://cdn.exemplo.com/foto.jpg'],
    CAROUSEL: ['URLs das Imagens (uma por linha)', 'Até 10 imagens. Uma URL por linha.'],
    VIDEO:    ['URL do Vídeo', 'URL pública do vídeo (MP4). Ex.: https://cdn.exemplo.com/video.mp4'],
    REELS:    ['URL do Reel (vídeo)', 'URL pública do vídeo vertical (MP4, máx. 90s).'],
    STORIES:  ['URL da Imagem/Vídeo', 'URL pública da mídia para Stories (proporção 9:16 recomendada).'],
  };
  const [label, hint] = cfg[type] || cfg.IMAGE;
  urlLabel.innerHTML = label + ' <span class="text-danger">*</span>';
  urlHint.textContent = hint;
  document.getElementById('p-media_urls').rows = type === 'CAROUSEL' ? 4 : 2;
}

function updatePreview() {
  const cap     = document.getElementById('p-caption').value;
  const hash    = document.getElementById('p-hashtags').value;
  const wrap    = document.getElementById('preview-wrap');
  const preview = document.getElementById('preview-caption');

  const full = [cap, hash].filter(Boolean).join('\n\n');
  if (!full) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';
  preview.textContent = full;
}

function savePost() {
  const btn  = document.getElementById('btn-save-post');
  const spin = document.getElementById('post-spinner');
  btn.disabled = true;
  spin.classList.remove('d-none');
  document.getElementById('post-alert').innerHTML = '';

  fetch(IG_MK.posts, {
    method: 'POST',
    body: new FormData(document.getElementById('post-form')),
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      spin.classList.add('d-none');
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('postModal')).hide();
        Toast.show(res.message, 'success');
        prependCard(res.data?.post);
      } else {
        document.getElementById('post-alert').innerHTML =
          `<div class="alert alert-danger py-2 small">${escHtml(res.message)}</div>`;
      }
    })
    .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
}

function prependCard(p) {
  if (!p) return;
  document.querySelector('.text-center.text-muted.py-5')?.remove();
  const grid = document.getElementById('posts-grid') || (() => {
    const g = document.createElement('div');
    g.id = 'posts-grid';
    g.className = 'row g-3';
    document.querySelector('.d-flex.align-items-center.justify-content-between.mb-4').after(g);
    return g;
  })();
  grid.insertAdjacentHTML('afterbegin', buildCard(p));
}

function buildCard(p) {
  const typeIcon = { IMAGE:'bi-image', CAROUSEL:'bi-images', VIDEO:'bi-camera-video', REELS:'bi-camera-reels', STORIES:'bi-circle-half' };
  const typeLabel = { IMAGE:'Foto', CAROUSEL:'Carrossel', VIDEO:'Vídeo', REELS:'Reels', STORIES:'Stories' };
  const urls = typeof p.media_urls === 'string' ? JSON.parse(p.media_urls || '[]') : (p.media_urls_decoded || []);
  const date = p.created_at ? p.created_at.substring(0,10).split('-').reverse().join('/') : '';
  const icon = typeIcon[p.media_type] || 'bi-image';
  const label = typeLabel[p.media_type] || p.media_type;
  const canPublish = ['draft','scheduled','failed'].includes(p.status);

  return `<div class="col-md-4" id="post-card-${p.id}">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="badge bg-light text-dark border small"><i class="bi ${icon} me-1"></i>${label}</span>
          ${statusBadge(p.status)}
        </div>
        ${p.campaign_name ? `<div class="small fw-semibold text-muted mb-1">${escHtml(p.campaign_name)}</div>` : ''}
        <p class="small mb-2" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
          ${escHtml(p.caption || '(sem legenda)')}
        </p>
        ${urls[0] ? `<div class="text-muted small mb-2 text-truncate"><i class="bi bi-link-45deg me-1"></i>${escHtml(urls[0])}</div>` : ''}
        ${p.permalink ? `<a href="${escHtml(p.permalink)}" target="_blank" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="bi bi-box-arrow-up-right me-1"></i>Ver no Instagram</a>` : ''}
        <div class="d-flex gap-2 mt-2">
          ${canPublish ? `<button class="btn btn-sm btn-primary flex-grow-1" onclick="confirmPublish(${p.id})"><i class="bi bi-send me-1"></i>Publicar</button>` : ''}
          <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${p.id})"><i class="bi bi-trash"></i></button>
        </div>
        <div class="text-muted mt-2" style="font-size:.72rem;">${date}</div>
      </div>
    </div>
  </div>`;
}

function confirmPublish(id) {
  publishModal.show();
  const btn  = document.getElementById('btn-confirm-publish');
  const spin = document.getElementById('pub-spinner');
  btn.onclick = () => {
    btn.disabled = true;
    spin.classList.remove('d-none');
    const fd = new FormData();
    fd.append('_csrf_token', IG_MK.csrf);
    fetch(IG_MK.posts + '/' + id + '/publish', {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        spin.classList.add('d-none');
        publishModal.hide();
        Toast.show(res.message, res.success ? 'success' : 'error');
        if (res.success) {
          const badge = document.querySelector(`#post-card-${id} .status-badge`);
          if (badge) badge.outerHTML = statusBadge('published');
          const pubBtn = document.querySelector(`#post-card-${id} .btn-primary`);
          if (pubBtn) pubBtn.remove();
          if (res.data?.permalink) {
            const card = document.querySelector(`#post-card-${id} .card-body`);
            if (card) {
              card.insertAdjacentHTML('beforeend',
                `<a href="${escHtml(res.data.permalink)}" target="_blank" class="btn btn-sm btn-outline-success w-100 mt-1">
                   <i class="bi bi-box-arrow-up-right me-1"></i>Ver no Instagram
                 </a>`);
            }
          }
        }
      })
      .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
  };
}

function confirmDelete(id) {
  deleteModal.show();
  const btn  = document.getElementById('btn-confirm-delete');
  const spin = document.getElementById('del-spinner');
  btn.onclick = () => {
    btn.disabled = true;
    spin.classList.remove('d-none');
    const fd = new FormData();
    fd.append('_csrf_token', IG_MK.csrf);
    fetch(IG_MK.posts + '/' + id + '/delete', {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        spin.classList.add('d-none');
        deleteModal.hide();
        if (res.success) {
          const el = document.getElementById('post-card-' + id);
          if (el) { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }
          Toast.show(res.message, 'success');
        } else {
          Toast.show(res.message, 'error');
        }
      })
      .catch(() => { btn.disabled = false; spin.classList.add('d-none'); Toast.show('Erro de conexão.', 'error'); });
  };
}

function statusBadge(status) {
  const map = {
    draft:       ['secondary',    'Rascunho'],
    scheduled:   ['info text-dark','Agendado'],
    publishing:  ['warning text-dark','Publicando…'],
    published:   ['success',      'Publicado'],
    failed:      ['danger',       'Falhou'],
  };
  const [cls, label] = map[status] || map.draft;
  return `<span class="badge bg-${cls} status-badge">${label}</span>`;
}

function resetPostForm() {
  document.getElementById('post-form').reset();
  document.getElementById('post-alert').innerHTML = '';
  document.getElementById('preview-wrap').style.display = 'none';
  document.getElementById('caption-count').textContent = '0';
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s)));
  return d.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>

<?php
function postCard(array $p): string
{
    $typeIcon  = ['IMAGE'=>'bi-image','CAROUSEL'=>'bi-images','VIDEO'=>'bi-camera-video','REELS'=>'bi-camera-reels','STORIES'=>'bi-circle-half'];
    $typeLabel = ['IMAGE'=>'Foto','CAROUSEL'=>'Carrossel','VIDEO'=>'Vídeo','REELS'=>'Reels','STORIES'=>'Stories'];
    $statusMap = ['draft'=>['secondary','Rascunho'],'scheduled'=>['info text-dark','Agendado'],'publishing'=>['warning text-dark','Publicando…'],'published'=>['success','Publicado'],'failed'=>['danger','Falhou']];

    $type   = $p['media_type'] ?? 'IMAGE';
    $icon   = $typeIcon[$type]  ?? 'bi-image';
    $label  = $typeLabel[$type] ?? $type;
    $status = $p['status'] ?? 'draft';
    [$cls,$slabel] = $statusMap[$status] ?? ['secondary','Rascunho'];
    $urls  = json_decode($p['media_urls'] ?? '[]', true) ?: [];
    $date  = !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '—';
    $cap   = mb_substr($p['caption'] ?? '', 0, 120);
    $camp  = $p['campaign_name'] ?? '';
    $canPub = in_array($status, ['draft','scheduled','failed']);

    $pubBtn    = $canPub ? '<button class="btn btn-sm btn-primary flex-grow-1" onclick="confirmPublish(' . $p['id'] . ')"><i class="bi bi-send me-1"></i>Publicar</button>' : '';
    $permalink = !empty($p['permalink'])
        ? '<a href="' . e($p['permalink']) . '" target="_blank" class="btn btn-sm btn-outline-success w-100 mb-2"><i class="bi bi-box-arrow-up-right me-1"></i>Ver no Instagram</a>'
        : '';
    $campHtml = $camp ? '<div class="small fw-semibold text-muted mb-1">' . e($camp) . '</div>' : '';
    $urlHtml  = !empty($urls[0]) ? '<div class="text-muted small mb-2 text-truncate"><i class="bi bi-link-45deg me-1"></i>' . e($urls[0]) . '</div>' : '';
    $errHtml  = ($status === 'failed' && !empty($p['error_message']))
        ? '<div class="alert alert-danger py-1 px-2 small mt-1 mb-0">' . e(mb_substr($p['error_message'],0,80)) . '</div>'
        : '';

    return "
    <div class=\"col-md-4\" id=\"post-card-{$p['id']}\">
      <div class=\"card border-0 shadow-sm h-100\">
        <div class=\"card-body p-3\">
          <div class=\"d-flex justify-content-between align-items-start mb-2\">
            <span class=\"badge bg-light text-dark border small\"><i class=\"bi {$icon} me-1\"></i>{$label}</span>
            <span class=\"badge bg-{$cls} status-badge\">{$slabel}</span>
          </div>
          {$campHtml}
          <p class=\"small mb-2\" style=\"display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;\">" . e($cap ?: '(sem legenda)') . "</p>
          {$urlHtml}
          {$permalink}
          {$errHtml}
          <div class=\"d-flex gap-2 mt-2\">
            {$pubBtn}
            <button class=\"btn btn-sm btn-outline-danger\" onclick=\"confirmDelete({$p['id']})\"><i class=\"bi bi-trash\"></i></button>
          </div>
          <div class=\"text-muted mt-2\" style=\"font-size:.72rem;\">{$date}</div>
        </div>
      </div>
    </div>";
}
?>
