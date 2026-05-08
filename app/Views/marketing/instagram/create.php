<?php \Core\View::section('title') ?>Novo Post — Instagram<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Novo Post — Instagram<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>
<?php
$formats = [
  ['id'=>'ig_post_portrait',    'name'=>'Post Portrait',      'w'=>1080,'h'=>1350,'platform'=>'instagram','type'=>'IMAGE',   'shape'=>'portrait',          'top'=>true,  'gradient'=>'linear-gradient(135deg,#f9317c,#9b27af)'],
  ['id'=>'ig_carousel_portrait','name'=>'Carrossel Portrait', 'w'=>1080,'h'=>1350,'platform'=>'instagram','type'=>'CAROUSEL','shape'=>'carousel',          'top'=>true,  'gradient'=>'linear-gradient(135deg,#9b27af,#4776e6)'],
  ['id'=>'ig_stories',          'name'=>'Stories Único',      'w'=>1080,'h'=>1920,'platform'=>'instagram','type'=>'STORIES', 'shape'=>'stories',           'top'=>true,  'gradient'=>'linear-gradient(135deg,#f9317c,#ff8c00)'],
  ['id'=>'ig_stories_carousel', 'name'=>'Stories Carrossel',  'w'=>1080,'h'=>1920,'platform'=>'instagram','type'=>'STORIES', 'shape'=>'carousel-stories',  'top'=>true,  'gradient'=>'linear-gradient(135deg,#9b27af,#f9317c)'],
  ['id'=>'ig_reels',            'name'=>'Reels',              'w'=>1080,'h'=>1920,'platform'=>'instagram','type'=>'REELS',   'shape'=>'stories',           'top'=>false, 'gradient'=>'linear-gradient(135deg,#ff8c00,#fd1d1d)'],
  ['id'=>'ig_post_square',      'name'=>'Post Quadrado',      'w'=>1080,'h'=>1080,'platform'=>'instagram','type'=>'IMAGE',   'shape'=>'square',            'top'=>false, 'gradient'=>'linear-gradient(135deg,#11998e,#38ef7d)'],
  ['id'=>'ig_post_landscape',   'name'=>'Post Paisagem',      'w'=>1080,'h'=>566, 'platform'=>'instagram','type'=>'IMAGE',   'shape'=>'landscape',         'top'=>false, 'gradient'=>'linear-gradient(135deg,#667eea,#764ba2)'],
  ['id'=>'fb_post_portrait',    'name'=>'Post Portrait',      'w'=>1080,'h'=>1350,'platform'=>'facebook', 'type'=>'IMAGE',   'shape'=>'portrait',          'top'=>true,  'gradient'=>'linear-gradient(135deg,#4776e6,#8e54e9)'],
  ['id'=>'fb_stories',          'name'=>'Stories',            'w'=>1080,'h'=>1920,'platform'=>'facebook', 'type'=>'STORIES', 'shape'=>'stories',           'top'=>false, 'gradient'=>'linear-gradient(135deg,#1877f2,#4776e6)'],
];

$igIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>';
$fbIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
?>
<style>
.wizard-wrap { max-width: 900px; margin: 0 auto; }

/* Step indicator */
.step-indicator { display:flex; align-items:center; justify-content:center; gap:0; margin-bottom:2rem; }
.step-item { display:flex; flex-direction:column; align-items:center; gap:4px; }
.step-item .dot { width:12px; height:12px; border-radius:50%; background:#dee2e6; transition:background .25s; }
.step-item .dot.active { background:#0d6efd; }
.step-item .dot.done { background:#0d6efd; }
.step-item span { font-size:.7rem; color:#6c757d; }
.step-item span.active { color:#0d6efd; font-weight:600; }
.step-line { width:64px; height:2px; background:#dee2e6; margin:0 4px; margin-bottom:16px; transition:background .25s; }
.step-line.done { background:#0d6efd; }

/* Filter buttons */
.filter-btn { border-radius:20px; font-size:.8rem; padding:5px 16px; border:1.5px solid #dee2e6; background:#fff; color:#495057; cursor:pointer; transition:all .15s; white-space:nowrap; }
.filter-btn:hover:not(:disabled) { border-color:#adb5bd; background:#f8f9fa; }
.filter-btn.active { background:#212529; color:#fff; border-color:#212529; }
.filter-btn:disabled { opacity:.5; cursor:default; }

/* Format cards */
.fmt-grid { display:flex; flex-wrap:wrap; gap:16px; }
.format-card {
  cursor:pointer; border-radius:18px; overflow:hidden; position:relative;
  width:180px; height:225px; flex-shrink:0;
  transition:transform .15s, box-shadow .15s;
  border:3px solid transparent; user-select:none;
}
.format-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.18); }
.format-card.selected { border-color:#0d6efd; box-shadow:0 0 0 4px rgba(13,110,253,.22); transform:translateY(-4px); }
.fmt-platform-icon {
  position:absolute; top:10px; left:10px; width:28px; height:28px; border-radius:50%;
  background:rgba(255,255,255,.2); backdrop-filter:blur(4px);
  display:flex; align-items:center; justify-content:center;
}
.fmt-top-badge {
  position:absolute; top:10px; right:10px;
  background:rgba(255,255,255,.22); backdrop-filter:blur(4px);
  color:#fff; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:20px;
}
.fmt-check {
  position:absolute; bottom:10px; right:10px; display:none;
  width:26px; height:26px; background:#0d6efd; border-radius:50%;
  align-items:center; justify-content:center; color:#fff; font-size:.85rem;
}
.format-card.selected .fmt-check { display:flex; }
.fmt-info {
  position:absolute; bottom:0; left:0; right:0;
  padding:36px 10px 10px;
  background:linear-gradient(transparent,rgba(0,0,0,.5));
}
.fmt-shape-wrap { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; }

/* Shapes */
.shape-portrait         { width:44%; height:64%; background:rgba(255,255,255,.22); border-radius:12px; }
.shape-square           { width:54%; height:54%; background:rgba(255,255,255,.22); border-radius:12px; }
.shape-landscape        { width:76%; height:36%; background:rgba(255,255,255,.22); border-radius:10px; }
.shape-stories          { width:36%; height:74%; background:rgba(255,255,255,.22); border-radius:14px; }
.shape-carousel         { position:relative; width:44%; height:64%; }
.shape-carousel::before,
.shape-carousel::after  { content:''; position:absolute; border-radius:12px; background:rgba(255,255,255,.22); }
.shape-carousel::before { inset:0; }
.shape-carousel::after  { top:7px; left:7px; right:-7px; bottom:-7px; z-index:-1; }
.shape-carousel-stories         { position:relative; width:36%; height:74%; }
.shape-carousel-stories::before,
.shape-carousel-stories::after  { content:''; position:absolute; border-radius:14px; background:rgba(255,255,255,.22); }
.shape-carousel-stories::before { inset:0; }
.shape-carousel-stories::after  { top:7px; left:7px; right:-7px; bottom:-7px; z-index:-1; }
</style>

<div class="wizard-wrap">

  <!-- Step indicator -->
  <div class="step-indicator">
    <div class="step-item">
      <div class="dot active" id="dot-1"></div>
      <span class="active" id="lbl-1">Formato</span>
    </div>
    <div class="step-line" id="line-1"></div>
    <div class="step-item">
      <div class="dot" id="dot-2"></div>
      <span id="lbl-2">Conteúdo</span>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════
       STEP 1 — Format selection
  ════════════════════════════════════════════ -->
  <div id="step-1">
    <h4 class="fw-bold text-center mb-1">Escolha o Formato da Imagem</h4>
    <p class="text-muted text-center small mb-4">Selecione o formato ideal para o seu post nas redes sociais</p>

    <!-- Filter tabs -->
    <div class="d-flex gap-2 flex-wrap mb-4">
      <button class="filter-btn active" onclick="filterBy('all',this)">Todas</button>
      <button class="filter-btn" onclick="filterBy('instagram',this)">
        <?= $igIcon ?> Instagram
      </button>
      <button class="filter-btn" onclick="filterBy('facebook',this)">
        <?= $fbIcon ?> Facebook
      </button>
      <button class="filter-btn" disabled>TikTok <span class="badge bg-secondary ms-1" style="font-size:.6rem">Em breve</span></button>
      <button class="filter-btn" disabled>YouTube <span class="badge bg-secondary ms-1" style="font-size:.6rem">Em breve</span></button>
      <button class="filter-btn" disabled>Twitter/X <span class="badge bg-secondary ms-1" style="font-size:.6rem">Em breve</span></button>
    </div>

    <!-- Format cards -->
    <div class="fmt-grid" id="format-grid">
      <?php foreach ($formats as $f):
        $platIcon = $f['platform'] === 'instagram' ? $igIcon : $fbIcon;
        $shapeClass = 'shape-' . $f['shape'];
      ?>
      <div class="format-card"
           data-id="<?= $f['id'] ?>"
           data-type="<?= $f['type'] ?>"
           data-platform="<?= $f['platform'] ?>"
           data-name="<?= htmlspecialchars($f['name']) ?>"
           data-w="<?= $f['w'] ?>"
           data-h="<?= $f['h'] ?>"
           data-gradient="<?= htmlspecialchars($f['gradient']) ?>"
           style="background:<?= $f['gradient'] ?>;"
           onclick="selectFormat(this)">
        <div class="fmt-platform-icon"><?= $platIcon ?></div>
        <?php if ($f['top']): ?>
        <div class="fmt-top-badge">⚡ TOP</div>
        <?php endif; ?>
        <div class="fmt-shape-wrap">
          <div class="<?= $shapeClass ?>"></div>
        </div>
        <div class="fmt-info">
          <div class="text-white fw-semibold" style="font-size:.8rem;"><?= $f['name'] ?></div>
          <div class="text-white opacity-75" style="font-size:.68rem;"><?= $f['w'] ?>×<?= $f['h'] ?>px</div>
          <div class="mt-1 d-flex gap-1"><?= $platIcon ?></div>
        </div>
        <div class="fmt-check"><i class="bi bi-check"></i></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Bottom nav -->
    <div class="d-flex justify-content-between align-items-center mt-5 pt-2 border-top">
      <a href="<?= url('admin/marketing/instagram') ?>" class="btn btn-link text-muted text-decoration-none ps-0">
        <i class="bi bi-arrow-left me-1"></i> Voltar
      </a>
      <button class="btn btn-primary px-5 fw-semibold" id="btn-continue" disabled onclick="goToStep2()">
        Continuar <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════
       STEP 2 — Content form
  ════════════════════════════════════════════ -->
  <div id="step-2" style="display:none;">

    <!-- Selected format preview header -->
    <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3 border" style="background:#fafafa;">
      <div id="fmt-mini" style="width:54px;height:68px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-image text-white opacity-75"></i>
      </div>
      <div>
        <div class="fw-bold" id="fmt-name">Post Portrait</div>
        <div class="small text-muted" id="fmt-dims">1080×1350px · Instagram</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="goToStep1()">
        <i class="bi bi-pencil me-1"></i> Trocar formato
      </button>
    </div>

    <div id="create-alert"></div>

    <form id="create-form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="media_type" id="f-media_type">

      <div class="row g-3">

        <div class="col-md-6">
          <label class="form-label fw-semibold small">Nome da campanha <span class="text-muted fw-normal">(opcional)</span></label>
          <input type="text" name="campaign_name" class="form-control" placeholder="Ex.: Lançamento Produto X">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">Agendar para <span class="text-muted fw-normal">(opcional)</span></label>
          <input type="datetime-local" name="scheduled_at" class="form-control">
          <div class="form-text">Deixe em branco para salvar como rascunho.</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small" id="f-url-label">
            URL da Mídia <span class="text-danger">*</span>
          </label>
          <textarea name="media_urls" id="f-media_urls" class="form-control font-monospace" rows="2"
                    placeholder="https://exemplo.com/imagem.jpg"></textarea>
          <div class="form-text" id="f-url-hint">URL pública da imagem (JPEG ou PNG).</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">Legenda</label>
          <textarea name="caption" id="f-caption" class="form-control" rows="5"
                    placeholder="Escreva a legenda do post..."
                    oninput="updateCount()"></textarea>
          <div class="d-flex justify-content-between mt-1">
            <div class="form-text">Máx. recomendado: 2.200 caracteres.</div>
            <small class="text-muted"><span id="f-count">0</span>/2200</small>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">Hashtags</label>
          <input type="text" name="hashtags" class="form-control"
                 placeholder="#produto #marca #lancamento">
          <div class="form-text">Serão adicionadas ao final da legenda automaticamente.</div>
        </div>

      </div>

      <!-- Bottom nav -->
      <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <button type="button" class="btn btn-link text-muted text-decoration-none ps-0" onclick="goToStep1()">
          <i class="bi bi-arrow-left me-1"></i> Voltar
        </button>
        <button type="button" class="btn btn-primary px-5 fw-semibold" onclick="submitPost()">
          <span class="spinner-border spinner-border-sm d-none me-1" id="submit-spin"></span>
          <i class="bi bi-floppy me-1" id="submit-icon"></i> Salvar Post
        </button>
      </div>
    </form>
  </div>

</div>
<?php \Core\View::endSection() ?>
<?php \Core\View::section('scripts') ?>
<script>
const IG_CREATE = {
  posts: '<?= url('admin/marketing/instagram/posts') ?>',
  index: '<?= url('admin/marketing/instagram') ?>',
};

let selectedFormat = null;

function filterBy(platform, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.format-card').forEach(card => {
    card.style.display = (platform === 'all' || card.dataset.platform === platform) ? '' : 'none';
  });
}

function selectFormat(card) {
  document.querySelectorAll('.format-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  selectedFormat = {
    id:       card.dataset.id,
    type:     card.dataset.type,
    platform: card.dataset.platform,
    name:     card.dataset.name,
    w:        card.dataset.w,
    h:        card.dataset.h,
    gradient: card.dataset.gradient,
  };
  document.getElementById('btn-continue').disabled = false;
}

function goToStep2() {
  if (!selectedFormat) return;

  // Step indicator
  document.getElementById('dot-1').classList.remove('active');
  document.getElementById('dot-1').classList.add('done');
  document.getElementById('dot-2').classList.add('active');
  document.getElementById('line-1').classList.add('done');
  document.getElementById('lbl-1').classList.remove('active');
  document.getElementById('lbl-2').classList.add('active');

  // Fill format preview
  document.getElementById('fmt-mini').style.background = selectedFormat.gradient;
  document.getElementById('fmt-name').textContent = selectedFormat.name;
  document.getElementById('fmt-dims').textContent =
    selectedFormat.w + '×' + selectedFormat.h + 'px · ' +
    (selectedFormat.platform === 'instagram' ? 'Instagram' : 'Facebook');

  // Set media_type
  document.getElementById('f-media_type').value = selectedFormat.type;

  // URL label/hint per type
  const hints = {
    IMAGE:    ['URL da Imagem',                    'URL pública da imagem (JPEG ou PNG).'],
    CAROUSEL: ['URLs das Imagens (uma por linha)', 'Até 10 imagens. Uma URL por linha.'],
    VIDEO:    ['URL do Vídeo',                     'URL pública do vídeo (MP4).'],
    REELS:    ['URL do Reel (vídeo)',              'URL pública do vídeo vertical (MP4, máx. 90s).'],
    STORIES:  ['URL da Imagem/Vídeo',             'URL da mídia para Stories (proporção 9:16 recomendada).'],
  };
  const [label, hint] = hints[selectedFormat.type] || hints.IMAGE;
  document.getElementById('f-url-label').innerHTML = label + ' <span class="text-danger">*</span>';
  document.getElementById('f-url-hint').textContent = hint;
  document.getElementById('f-media_urls').rows = selectedFormat.type === 'CAROUSEL' ? 5 : 2;
  document.getElementById('f-media_urls').placeholder = selectedFormat.type === 'CAROUSEL'
    ? 'https://cdn.exemplo.com/foto1.jpg\nhttps://cdn.exemplo.com/foto2.jpg'
    : 'https://cdn.exemplo.com/imagem.jpg';

  document.getElementById('step-1').style.display = 'none';
  document.getElementById('step-2').style.display = '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep1() {
  document.getElementById('dot-1').classList.add('active');
  document.getElementById('dot-1').classList.remove('done');
  document.getElementById('dot-2').classList.remove('active');
  document.getElementById('line-1').classList.remove('done');
  document.getElementById('lbl-1').classList.add('active');
  document.getElementById('lbl-2').classList.remove('active');
  document.getElementById('step-1').style.display = '';
  document.getElementById('step-2').style.display = 'none';
}

function updateCount() {
  const len = document.getElementById('f-caption').value.length;
  const el  = document.getElementById('f-count');
  el.textContent = len;
  el.style.color = len > 2000 ? '#dc3545' : '';
}

function submitPost() {
  const spin = document.getElementById('submit-spin');
  const icon = document.getElementById('submit-icon');
  spin.classList.remove('d-none');
  icon.classList.add('d-none');
  document.getElementById('create-alert').innerHTML = '';

  fetch(IG_CREATE.posts, {
    method:  'POST',
    body:    new FormData(document.getElementById('create-form')),
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(res => {
      spin.classList.add('d-none');
      icon.classList.remove('d-none');
      if (res.success) {
        window.location.href = IG_CREATE.index;
      } else {
        document.getElementById('create-alert').innerHTML =
          `<div class="alert alert-danger py-2 small mb-3">${escHtml(res.message)}</div>`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    })
    .catch(() => {
      spin.classList.add('d-none');
      icon.classList.remove('d-none');
      Toast.show('Erro de conexão.', 'error');
    });
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s)));
  return d.innerHTML;
}
</script>
<?php \Core\View::endSection() ?>
