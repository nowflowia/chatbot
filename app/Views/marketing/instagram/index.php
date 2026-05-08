<?php \Core\View::section('title') ?>Instagram — Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Instagram — Marketing<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0 text-dark">Instagram — Posts Orgânicos</h5>
    <small class="text-muted">Crie, agende e publique posts no Instagram Business</small>
  </div>
  <a href="<?= url('admin/marketing/instagram/create') ?>" class="btn btn-primary d-flex align-items-center gap-2">
    <i class="bi bi-plus-lg"></i> Novo Post
  </a>
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
  posts: '<?= url('admin/marketing/instagram/posts') ?>',
  csrf:  '<?= csrf_token() ?>',
};

let publishModal, deleteModal;

document.addEventListener('DOMContentLoaded', () => {
  publishModal = new bootstrap.Modal(document.getElementById('publishModal'));
  deleteModal  = new bootstrap.Modal(document.getElementById('deleteModal'));
});

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
