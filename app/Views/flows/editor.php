<?php \Core\View::section('title') ?>Editor — <?= e($flow['name']) ?><?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>
  <a href="<?= url('admin/flows') ?>" class="text-decoration-none text-muted me-2">
    <i class="bi bi-arrow-left"></i>
  </a>
  Editor: <strong><?= e($flow['name']) ?></strong>
<?php \Core\View::endSection() ?>
<?php \Core\View::section('content') ?>

<!-- Top toolbar -->
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
  <span class="badge <?= $flow['is_active'] ? 'bg-success' : 'bg-secondary' ?>" id="flow-status-badge">
    <?= $flow['is_active'] ? 'Ativo' : 'Inativo' ?>
  </span>
  <span class="text-muted small">|</span>
  <span class="small text-muted">Gatilho: <strong><?= e($flow['trigger']) ?></strong></span>
  <div class="ms-auto d-flex gap-2">
    <button class="btn btn-sm btn-outline-secondary" id="btn-settings">
      <i class="bi bi-gear me-1"></i>Configurações
    </button>
    <button class="btn btn-sm btn-outline-secondary" id="btn-zoom-out" title="Zoom -"><i class="bi bi-zoom-out"></i></button>
    <button class="btn btn-sm btn-outline-secondary" id="btn-zoom-in"  title="Zoom +"><i class="bi bi-zoom-in"></i></button>
    <button class="btn btn-sm btn-outline-secondary" id="btn-zoom-reset" title="Resetar zoom">100%</button>
    <button class="btn btn-sm btn-success" id="btn-save">
      <span class="spinner-border spinner-border-sm d-none me-1" id="save-spinner"></span>
      <i class="bi bi-floppy me-1"></i>Salvar
    </button>
  </div>
</div>

<!-- Builder layout -->
<div class="flow-builder-wrap">
  <!-- Sidebar palette -->
  <div class="flow-palette">
    <div class="flow-palette-title">Blocos</div>
    <?php
    $nodeTypes = [
      ['type'=>'message',   'icon'=>'chat-left-text',  'label'=>'Mensagem',   'color'=>'#6366f1'],
      ['type'=>'question',  'icon'=>'question-circle', 'label'=>'Pergunta',   'color'=>'#f59e0b'],
      ['type'=>'list',      'icon'=>'list-ul',          'label'=>'Lista',      'color'=>'#3b82f6'],
      ['type'=>'condition', 'icon'=>'signpost-split',   'label'=>'Condição',   'color'=>'#8b5cf6'],
      ['type'=>'transfer',  'icon'=>'person-lines-fill','label'=>'Transferir', 'color'=>'#10b981'],
      ['type'=>'wait',      'icon'=>'hourglass-split',  'label'=>'Aguardar',   'color'=>'#14b8a6'],
      ['type'=>'api_call',  'icon'=>'cloud-arrow-up',   'label'=>'API Call',   'color'=>'#ec4899'],
      ['type'=>'finish',    'icon'=>'check-circle',     'label'=>'Finalizar',  'color'=>'#ef4444'],
    ];
    foreach ($nodeTypes as $t):
    ?>
    <div class="palette-item" draggable="true"
         data-type="<?= $t['type'] ?>"
         data-color="<?= $t['color'] ?>"
         data-label="<?= $t['label'] ?>">
      <i class="bi bi-<?= $t['icon'] ?>" style="color:<?= $t['color'] ?>"></i>
      <span><?= $t['label'] ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Canvas -->
  <div class="flow-canvas-wrap" id="canvas-wrap">
    <svg class="flow-svg" id="flow-svg"></svg>
    <div class="flow-canvas" id="flow-canvas">
      <!-- nodes rendered here by JS -->
    </div>
    <div class="canvas-hint" id="canvas-hint">
      <i class="bi bi-diagram-3 d-block fs-2 mb-2 opacity-50"></i>
      Arraste blocos do painel esquerdo para começar
    </div>
  </div>

  <!-- Inspector panel -->
  <div class="flow-inspector" id="flow-inspector">
    <div class="inspector-placeholder" id="inspector-placeholder">
      <i class="bi bi-cursor d-block fs-2 mb-2 opacity-40"></i>
      Clique em um bloco para editar
    </div>
    <div id="inspector-form" class="d-none">
      <div class="inspector-header">
        <span id="insp-icon" class="me-2"></span>
        <strong id="insp-type-label"></strong>
        <button class="btn-close ms-auto" id="btn-close-inspector" style="font-size:.7rem"></button>
      </div>

      <div class="mb-3">
        <label class="form-label form-label-sm fw-semibold">Título do bloco</label>
        <input type="text" id="insp-title" class="form-control form-control-sm">
      </div>

      <!-- message config -->
      <div id="cfg-message" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Texto da mensagem</label>
        <textarea id="cfg-msg-text" class="form-control form-control-sm" rows="5"
                  placeholder="Digite o texto que será enviado…"></textarea>
        <div class="form-text">Use {{nome}} para variáveis do contato.</div>
      </div>

      <!-- question config -->
      <div id="cfg-question" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Pergunta</label>
        <textarea id="cfg-q-text" class="form-control form-control-sm" rows="3"
                  placeholder="Qual a sua dúvida?"></textarea>
        <label class="form-label form-label-sm fw-semibold mt-2">Salvar resposta em</label>
        <input type="text" id="cfg-q-var" class="form-control form-control-sm"
               placeholder="Ex: resposta_usuario">
      </div>

      <!-- list config -->
      <div id="cfg-list" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Texto do cabeçalho</label>
        <input type="text" id="cfg-list-header" class="form-control form-control-sm mb-2"
               placeholder="Escolha uma opção">
        <label class="form-label form-label-sm fw-semibold">Opções (uma por linha)</label>
        <textarea id="cfg-list-options" class="form-control form-control-sm" rows="5"
                  placeholder="Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
        <div class="form-text">Conecte cada saída ao próximo bloco.</div>
      </div>

      <!-- condition config -->
      <div id="cfg-condition" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Variável</label>
        <input type="text" id="cfg-cond-var" class="form-control form-control-sm mb-2"
               placeholder="Ex: resposta_usuario">
        <label class="form-label form-label-sm fw-semibold">Operador</label>
        <select id="cfg-cond-op" class="form-select form-select-sm mb-2">
          <option value="equals">Igual a</option>
          <option value="contains">Contém</option>
          <option value="starts">Começa com</option>
          <option value="not_empty">Não está vazio</option>
        </select>
        <label class="form-label form-label-sm fw-semibold">Valor</label>
        <input type="text" id="cfg-cond-val" class="form-control form-control-sm"
               placeholder="Ex: sim">
      </div>

      <!-- transfer config -->
      <div id="cfg-transfer" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Mensagem ao transferir</label>
        <textarea id="cfg-transfer-msg" class="form-control form-control-sm" rows="2"
                  placeholder="Transferindo para um atendente…"></textarea>
      </div>

      <!-- wait config -->
      <div id="cfg-wait" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Aguardar (segundos)</label>
        <input type="number" id="cfg-wait-secs" class="form-control form-control-sm"
               min="1" max="3600" value="5">
      </div>

      <!-- api_call config -->
      <div id="cfg-api_call" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">URL</label>
        <input type="url" id="cfg-api-url" class="form-control form-control-sm mb-2"
               placeholder="https://api.exemplo.com/endpoint">
        <label class="form-label form-label-sm fw-semibold">Método</label>
        <select id="cfg-api-method" class="form-select form-select-sm mb-2">
          <option>GET</option><option>POST</option><option>PUT</option>
        </select>
        <label class="form-label form-label-sm fw-semibold">Salvar resposta em</label>
        <input type="text" id="cfg-api-var" class="form-control form-control-sm"
               placeholder="api_response">
      </div>

      <!-- finish config -->
      <div id="cfg-finish" class="cfg-section d-none">
        <label class="form-label form-label-sm fw-semibold">Mensagem de encerramento</label>
        <textarea id="cfg-finish-msg" class="form-control form-control-sm" rows="2"
                  placeholder="Até logo! Qualquer dúvida, é só chamar."></textarea>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-sm btn-primary w-100" id="btn-apply-inspector">Aplicar</button>
        <button class="btn btn-sm btn-outline-danger" id="btn-delete-node" title="Excluir bloco">
          <i class="bi bi-trash"></i>
        </button>
      </div>

      <div class="mt-3 pt-3 border-top">
        <div class="form-check form-switch form-check-sm">
          <input class="form-check-input" type="checkbox" id="insp-is-start">
          <label class="form-check-label form-label-sm" for="insp-is-start">Bloco inicial</label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Settings modal -->
<div class="modal fade" id="flowSettingsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Configurações do Fluxo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Nome</label>
          <input type="text" id="fs-name" class="form-control form-control-sm"
                 value="<?= e($flow['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Gatilho</label>
          <select id="fs-trigger" class="form-select form-select-sm">
            <?php foreach (['keyword','start','always','manual'] as $t): ?>
            <option value="<?= $t ?>" <?= $flow['trigger'] === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3" id="fs-keywords-wrap" <?= $flow['trigger'] !== 'keyword' ? 'style="display:none"' : '' ?>>
          <label class="form-label form-label-sm fw-semibold">Palavras-chave (separadas por vírgula)</label>
          <input type="text" id="fs-keywords" class="form-control form-control-sm"
                 value="<?= e($flow['trigger_keywords'] ?? '') ?>"
                 placeholder="oi, olá, menu">
        </div>
        <div class="mb-3">
          <label class="form-label form-label-sm fw-semibold">Descrição</label>
          <textarea id="fs-desc" class="form-control form-control-sm" rows="2"
          ><?= e($flow['description'] ?? '') ?></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="fs-active"
                 <?= $flow['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="fs-active">Fluxo ativo</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-primary" id="btn-save-settings">
          <span class="spinner-border spinner-border-sm d-none me-1" id="fs-spinner"></span>
          Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Layout ─────────────────────────────── */
.flow-builder-wrap {
  display: flex;
  height: calc(100vh - 180px);
  min-height: 500px;
  border: 1px solid var(--bs-border-color);
  border-radius: .5rem;
  overflow: hidden;
  background: #f8f9fa;
}

/* ── Palette ─────────────────────────────── */
.flow-palette {
  width: 140px;
  min-width: 140px;
  background: #fff;
  border-right: 1px solid var(--bs-border-color);
  overflow-y: auto;
  padding: 8px 0;
}
.flow-palette-title {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #999;
  padding: 4px 12px 8px;
  letter-spacing: .05em;
}
.palette-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  cursor: grab;
  font-size: .8rem;
  border-radius: 0;
  transition: background .15s;
  user-select: none;
}
.palette-item:hover  { background: #f0f4ff; }
.palette-item:active { cursor: grabbing; }
.palette-item i      { font-size: 1rem; flex-shrink:0; }

/* ── Canvas ─────────────────────────────── */
.flow-canvas-wrap {
  flex: 1;
  position: relative;
  overflow: hidden;
  cursor: default;
}
.flow-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  overflow: visible;
}
.flow-svg path {
  fill: none;
  stroke: #6366f1;
  stroke-width: 2;
  pointer-events: stroke;
  cursor: pointer;
}
.flow-svg path:hover { stroke: #ef4444; stroke-width: 3; }
.flow-canvas {
  position: absolute;
  top: 0; left: 0;
  transform-origin: 0 0;
}
.canvas-hint {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #aaa;
  font-size: .9rem;
  pointer-events: none;
}

/* ── Node cards ────────────────────────── */
.flow-node {
  position: absolute;
  width: 180px;
  background: #fff;
  border: 2px solid transparent;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,.10);
  cursor: pointer;
  user-select: none;
  transition: box-shadow .15s, border-color .15s;
}
.flow-node:hover       { box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.flow-node.selected    { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
.flow-node.is-start    { border-color: #10b981; }
.node-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px 6px;
  border-radius: 8px 8px 0 0;
  font-size: .78rem;
  font-weight: 600;
  color: #fff;
}
.node-title {
  padding: 6px 10px 8px;
  font-size: .78rem;
  color: #444;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.node-ports {
  display: flex;
  justify-content: space-between;
  padding: 0 0 6px;
  position: relative;
}
.node-port {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #e2e8f0;
  border: 2px solid #94a3b8;
  cursor: crosshair;
  transition: background .15s;
}
.node-port:hover     { background: #6366f1; border-color: #6366f1; }
.node-port.port-target { background: #10b981; border-color: #10b981; transform: scale(1.4); }
.node-port.port-in   { margin-left: -6px; }
.node-port.port-out  { margin-right: -6px; }
.port-label-out      { font-size: .65rem; color: #94a3b8; margin-right: 4px; align-self: center; }
.port-label-in       { font-size: .65rem; color: #94a3b8; margin-left:  4px; align-self: center; }

/* ── Inspector ──────────────────────────── */
.flow-inspector {
  width: 260px;
  min-width: 260px;
  background: #fff;
  border-left: 1px solid var(--bs-border-color);
  overflow-y: auto;
  padding: 12px;
}
.inspector-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #bbb;
  font-size: .85rem;
  text-align: center;
}
.inspector-header {
  display: flex;
  align-items: center;
  margin-bottom: 14px;
  font-size: .9rem;
}
.cfg-section { margin-top: 0; }

@media (max-width: 768px) {
  .flow-inspector { display: none; }
  .flow-palette   { width: 44px; min-width: 44px; }
  .palette-item span { display: none; }
}
</style>
<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
(function () {
'use strict';

const FLOW_ID = <?= (int)$flow['id'] ?>;
const BASE    = '<?= url('admin') ?>';

// ── State ─────────────────────────────────────────────────────────
let nodes       = [];   // [{id, type, title, config, pos_x, pos_y, is_start}]
let connections = [];   // [{id, source_node_id, target_node_id, source_port, target_port, label}]
let nextId      = 1;    // temp id counter
let scale       = 1;
let panX        = 0;
let panY        = 0;

// interaction state
let draggingNode    = null;
let dragOffX        = 0;
let dragOffY        = 0;
let connectingFrom  = null; // {nodeId, port}
let selectedNodeId  = null;
let isPanning       = false;
let panStartX       = 0;
let panStartY       = 0;
let panStartPanX    = 0;
let panStartPanY    = 0;

const canvas     = document.getElementById('flow-canvas');
const svg        = document.getElementById('flow-svg');
const canvasWrap = document.getElementById('canvas-wrap');
const hint       = document.getElementById('canvas-hint');

const NODE_META = {
  message:   { icon: 'bi-chat-left-text',  label: 'Mensagem',   color: '#6366f1' },
  question:  { icon: 'bi-question-circle', label: 'Pergunta',   color: '#f59e0b' },
  list:      { icon: 'bi-list-ul',         label: 'Lista',      color: '#3b82f6' },
  condition: { icon: 'bi-signpost-split',  label: 'Condição',   color: '#8b5cf6' },
  transfer:  { icon: 'bi-person-lines-fill',label:'Transferir', color: '#10b981' },
  wait:      { icon: 'bi-hourglass-split', label: 'Aguardar',   color: '#14b8a6' },
  api_call:  { icon: 'bi-cloud-arrow-up',  label: 'API Call',   color: '#ec4899' },
  finish:    { icon: 'bi-check-circle',    label: 'Finalizar',  color: '#ef4444' },
};

// ── Render ─────────────────────────────────────────────────────────
function render() {
  // Update canvas transform
  canvas.style.transform = `translate(${panX}px,${panY}px) scale(${scale})`;
  svg.style.transform    = `translate(${panX}px,${panY}px) scale(${scale})`;
  document.getElementById('btn-zoom-reset').textContent = Math.round(scale * 100) + '%';

  // Toggle hint
  hint.style.display = nodes.length ? 'none' : 'flex';

  // Re-render all nodes
  // Remove nodes not in state
  Array.from(canvas.querySelectorAll('.flow-node')).forEach(el => {
    if (!nodes.find(n => n.id == el.dataset.id)) el.remove();
  });

  nodes.forEach(n => renderNode(n));
  renderConnections();
}

function renderNode(n) {
  const meta = NODE_META[n.type] || NODE_META.message;
  let el     = canvas.querySelector(`.flow-node[data-id="${n.id}"]`);

  if (!el) {
    el = document.createElement('div');
    el.className    = 'flow-node';
    el.dataset.id   = n.id;
    el.innerHTML    = nodeHTML(n, meta);
    canvas.appendChild(el);
    bindNodeEvents(el, n);
  } else {
    el.innerHTML = nodeHTML(n, meta);
    bindNodeEvents(el, n);
  }

  el.style.left = n.pos_x + 'px';
  el.style.top  = n.pos_y + 'px';
  el.classList.toggle('selected', n.id === selectedNodeId);
  el.classList.toggle('is-start', !!n.is_start);
}

function nodeHTML(n, meta) {
  const isStart = n.is_start ? '<span class="badge bg-success ms-auto" style="font-size:.55rem">START</span>' : '';
  return `
    <div class="node-header" style="background:${meta.color}">
      <i class="bi ${meta.icon}"></i>
      <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${meta.label}</span>
      ${isStart}
    </div>
    <div class="node-title">${esc(n.title || meta.label)}</div>
    <div class="node-ports">
      <div style="display:flex;align-items:center">
        <div class="node-port port-in" data-node="${n.id}" data-port="input" title="Entrada"></div>
      </div>
      <div style="display:flex;align-items:center">
        <div class="node-port port-out" data-node="${n.id}" data-port="output" title="Saída"></div>
      </div>
    </div>`;
}

function esc(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s)));
  return d.innerHTML;
}

// ── Connections SVG ──────────────────────────────────────────────
function renderConnections() {
  svg.innerHTML = '';
  connections.forEach(c => {
    const srcEl  = canvas.querySelector(`.flow-node[data-id="${c.source_node_id}"]`);
    const tgtEl  = canvas.querySelector(`.flow-node[data-id="${c.target_node_id}"]`);
    if (!srcEl || !tgtEl) return;

    const srcPort = srcEl.querySelector('.port-out');
    const tgtPort = tgtEl.querySelector('.port-in');
    if (!srcPort || !tgtPort) return;

    const sp = portCenter(srcEl, srcPort);
    const tp = portCenter(tgtEl, tgtPort);

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    const cx   = (sp.x + tp.x) / 2;
    path.setAttribute('d', `M${sp.x},${sp.y} C${cx},${sp.y} ${cx},${tp.y} ${tp.x},${tp.y}`);
    path.dataset.connId = c.id;
    path.addEventListener('click', () => deleteConnection(c.id));
    svg.appendChild(path);

    // label
    if (c.label) {
      const txt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      txt.setAttribute('x', cx);
      txt.setAttribute('y', (sp.y + tp.y) / 2 - 4);
      txt.setAttribute('text-anchor', 'middle');
      txt.setAttribute('font-size', '11');
      txt.setAttribute('fill', '#6366f1');
      txt.textContent = c.label;
      svg.appendChild(txt);
    }
  });
}

function portCenter(nodeEl, portEl) {
  const nr = nodeEl.getBoundingClientRect();
  const pr = portEl.getBoundingClientRect();
  const wr = canvasWrap.getBoundingClientRect();
  return {
    x: (pr.left + pr.width  / 2 - wr.left - panX) / scale,
    y: (pr.top  + pr.height / 2 - wr.top  - panY) / scale,
  };
}

function deleteConnection(connId) {
  connections = connections.filter(c => c.id !== connId);
  renderConnections();
}

// ── Node events ───────────────────────────────────────────────────
function bindNodeEvents(el, n) {
  // Drag
  el.addEventListener('mousedown', e => {
    if (e.target.classList.contains('node-port')) return;
    e.stopPropagation();
    draggingNode = n.id;
    const wr     = canvasWrap.getBoundingClientRect();
    dragOffX = (e.clientX - wr.left - panX) / scale - n.pos_x;
    dragOffY = (e.clientY - wr.top  - panY) / scale - n.pos_y;
    selectNode(n.id);
  });

  // Port connect
  el.querySelectorAll('.node-port').forEach(port => {
    port.addEventListener('mousedown', e => {
      e.stopPropagation();
      const nodeId   = port.dataset.node;
      const portType = port.dataset.port;
      if (portType === 'output') {
        connectingFrom = { nodeId, port: 'output' };
      }
    });

    port.addEventListener('mouseup', e => {
      e.stopPropagation();
      const nodeId   = port.dataset.node;
      const portType = port.dataset.port;
      if (connectingFrom && portType === 'input' && connectingFrom.nodeId != nodeId) {
        // avoid duplicate
        const exists = connections.find(c =>
          c.source_node_id == connectingFrom.nodeId && c.target_node_id == nodeId
        );
        if (!exists) {
          connections.push({
            id:             'c' + Date.now(),
            source_node_id: connectingFrom.nodeId,
            target_node_id: nodeId,
            source_port:    'output',
            target_port:    'input',
            label:          null,
            condition_value:null,
            sort_order:     connections.length,
          });
        }
      }
      connectingFrom = null;
      render();
    });
  });

  // Click select
  el.addEventListener('click', e => {
    if (!e.target.classList.contains('node-port')) selectNode(n.id);
  });
}

// ── Canvas mouse events ───────────────────────────────────────────
canvasWrap.addEventListener('mousedown', e => {
  if (e.button === 1 || (e.button === 0 && !e.target.closest('.flow-node'))) {
    isPanning   = true;
    panStartX   = e.clientX;
    panStartY   = e.clientY;
    panStartPanX = panX;
    panStartPanY = panY;
    canvasWrap.style.cursor = 'grabbing';
  }
});

window.addEventListener('mousemove', e => {
  if (draggingNode !== null) {
    const n  = nodes.find(n => n.id == draggingNode);
    const wr = canvasWrap.getBoundingClientRect();
    if (n) {
      n.pos_x = Math.round((e.clientX - wr.left - panX) / scale - dragOffX);
      n.pos_y = Math.round((e.clientY - wr.top  - panY) / scale - dragOffY);
      render();
    }
  } else if (isPanning) {
    panX = panStartPanX + (e.clientX - panStartX);
    panY = panStartPanY + (e.clientY - panStartY);
    render();
  } else if (connectingFrom) {
    // Draw a temporary SVG line from source port to cursor
    const srcEl   = canvas.querySelector(`.flow-node[data-id="${connectingFrom.nodeId}"]`);
    const srcPort = srcEl ? srcEl.querySelector('.port-out') : null;
    if (srcPort) {
      const wr = canvasWrap.getBoundingClientRect();
      const sp = portCenter(srcEl, srcPort);
      const tx = (e.clientX - wr.left - panX) / scale;
      const ty = (e.clientY - wr.top  - panY) / scale;
      // Remove old temp line
      const old = svg.querySelector('#conn-temp');
      if (old) old.remove();
      const cx1 = sp.x + Math.abs(tx - sp.x) * 0.5;
      const cy1 = sp.y;
      const cx2 = tx - Math.abs(tx - sp.x) * 0.5;
      const cy2 = ty;
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('id', 'conn-temp');
      path.setAttribute('d', `M${sp.x},${sp.y} C${cx1},${cy1} ${cx2},${cy2} ${tx},${ty}`);
      path.setAttribute('stroke', '#6366f1');
      path.setAttribute('stroke-width', '2');
      path.setAttribute('stroke-dasharray', '6,3');
      path.setAttribute('fill', 'none');
      path.setAttribute('pointer-events', 'none');
      svg.appendChild(path);
    }
    // Highlight valid target ports
    canvas.querySelectorAll('.port-in').forEach(p => {
      p.classList.toggle('port-target', p.dataset.node != connectingFrom.nodeId);
    });
  }
  if (!connectingFrom) {
    const old = svg.querySelector('#conn-temp');
    if (old) old.remove();
    canvas.querySelectorAll('.port-target').forEach(p => p.classList.remove('port-target'));
  }
});

window.addEventListener('mouseup', e => {
  draggingNode   = null;
  isPanning      = false;
  connectingFrom = null;
  canvasWrap.style.cursor = 'default';
  // Clean up temp line and highlights
  const old = svg.querySelector('#conn-temp');
  if (old) old.remove();
  canvas.querySelectorAll('.port-target').forEach(p => p.classList.remove('port-target'));
});

canvasWrap.addEventListener('wheel', e => {
  e.preventDefault();
  const delta = e.deltaY > 0 ? 0.9 : 1.1;
  scale = Math.min(2, Math.max(0.3, scale * delta));
  render();
}, { passive: false });

// ── Palette drag & drop ───────────────────────────────────────────
let paletteDragType  = null;
let paletteDragColor = null;
let paletteDragLabel = null;

document.querySelectorAll('.palette-item').forEach(item => {
  item.addEventListener('dragstart', e => {
    paletteDragType  = item.dataset.type;
    paletteDragColor = item.dataset.color;
    paletteDragLabel = item.dataset.label;
  });
});

canvasWrap.addEventListener('dragover', e => e.preventDefault());
canvasWrap.addEventListener('drop', e => {
  if (!paletteDragType) return;
  const wr  = canvasWrap.getBoundingClientRect();
  const px  = Math.round((e.clientX - wr.left - panX) / scale);
  const py  = Math.round((e.clientY - wr.top  - panY) / scale);

  const newNode = {
    id:       'n' + (nextId++),
    type:     paletteDragType,
    title:    paletteDragLabel,
    config:   {},
    pos_x:    px,
    pos_y:    py,
    is_start: nodes.length === 0 ? 1 : 0,
  };
  nodes.push(newNode);
  paletteDragType = null;
  render();
  selectNode(newNode.id);
});

// ── Zoom controls ────────────────────────────────────────────────
document.getElementById('btn-zoom-in').addEventListener('click', () => {
  scale = Math.min(2, scale + 0.1); render();
});
document.getElementById('btn-zoom-out').addEventListener('click', () => {
  scale = Math.max(0.3, scale - 0.1); render();
});
document.getElementById('btn-zoom-reset').addEventListener('click', () => {
  scale = 1; panX = 0; panY = 0; render();
});

// ── Inspector ─────────────────────────────────────────────────────
function selectNode(id) {
  selectedNodeId = id;
  render();
  openInspector(id);
}

function openInspector(id) {
  const n = nodes.find(n => n.id == id);
  if (!n) { closeInspector(); return; }

  const meta = NODE_META[n.type] || NODE_META.message;
  document.getElementById('inspector-placeholder').classList.add('d-none');
  document.getElementById('inspector-form').classList.remove('d-none');
  document.getElementById('insp-icon').innerHTML = `<i class="bi ${meta.icon}" style="color:${meta.color}"></i>`;
  document.getElementById('insp-type-label').textContent = meta.label;
  document.getElementById('insp-title').value     = n.title || meta.label;
  document.getElementById('insp-is-start').checked = !!n.is_start;

  // Show/hide cfg sections
  document.querySelectorAll('.cfg-section').forEach(s => s.classList.add('d-none'));
  const cfgEl = document.getElementById('cfg-' + n.type);
  if (cfgEl) cfgEl.classList.remove('d-none');

  // Populate cfg fields
  const cfg = n.config || {};
  if (n.type === 'message')   document.getElementById('cfg-msg-text').value        = cfg.text   || '';
  if (n.type === 'question') {
    document.getElementById('cfg-q-text').value = cfg.text || '';
    document.getElementById('cfg-q-var').value  = cfg.variable || '';
  }
  if (n.type === 'list') {
    document.getElementById('cfg-list-header').value  = cfg.header  || '';
    document.getElementById('cfg-list-options').value = (cfg.options || []).join('\n');
  }
  if (n.type === 'condition') {
    document.getElementById('cfg-cond-var').value = cfg.variable || '';
    document.getElementById('cfg-cond-op').value  = cfg.operator  || 'equals';
    document.getElementById('cfg-cond-val').value = cfg.value     || '';
  }
  if (n.type === 'transfer') document.getElementById('cfg-transfer-msg').value = cfg.message || '';
  if (n.type === 'wait')     document.getElementById('cfg-wait-secs').value    = cfg.seconds || 5;
  if (n.type === 'api_call') {
    document.getElementById('cfg-api-url').value    = cfg.url    || '';
    document.getElementById('cfg-api-method').value = cfg.method || 'GET';
    document.getElementById('cfg-api-var').value    = cfg.variable || '';
  }
  if (n.type === 'finish') document.getElementById('cfg-finish-msg').value = cfg.message || '';
}

function closeInspector() {
  document.getElementById('inspector-placeholder').classList.remove('d-none');
  document.getElementById('inspector-form').classList.add('d-none');
  selectedNodeId = null;
  render();
}

document.getElementById('btn-close-inspector').addEventListener('click', closeInspector);

document.getElementById('btn-apply-inspector').addEventListener('click', () => {
  const n = nodes.find(n => n.id == selectedNodeId);
  if (!n) return;

  n.title    = document.getElementById('insp-title').value.trim() || n.title;
  n.is_start = document.getElementById('insp-is-start').checked ? 1 : 0;

  // If this node is set as start, unset others
  if (n.is_start) nodes.forEach(x => { if (x.id !== n.id) x.is_start = 0; });

  const cfg = {};
  if (n.type === 'message')   cfg.text     = document.getElementById('cfg-msg-text').value;
  if (n.type === 'question') {
    cfg.text     = document.getElementById('cfg-q-text').value;
    cfg.variable = document.getElementById('cfg-q-var').value;
  }
  if (n.type === 'list') {
    cfg.header  = document.getElementById('cfg-list-header').value;
    cfg.options = document.getElementById('cfg-list-options').value.split('\n').map(s=>s.trim()).filter(Boolean);
  }
  if (n.type === 'condition') {
    cfg.variable = document.getElementById('cfg-cond-var').value;
    cfg.operator = document.getElementById('cfg-cond-op').value;
    cfg.value    = document.getElementById('cfg-cond-val').value;
  }
  if (n.type === 'transfer') cfg.message = document.getElementById('cfg-transfer-msg').value;
  if (n.type === 'wait')     cfg.seconds = parseInt(document.getElementById('cfg-wait-secs').value) || 5;
  if (n.type === 'api_call') {
    cfg.url      = document.getElementById('cfg-api-url').value;
    cfg.method   = document.getElementById('cfg-api-method').value;
    cfg.variable = document.getElementById('cfg-api-var').value;
  }
  if (n.type === 'finish') cfg.message = document.getElementById('cfg-finish-msg').value;

  n.config = cfg;
  render();
  Toast.show('Bloco atualizado.', 'success');
});

document.getElementById('btn-delete-node').addEventListener('click', () => {
  if (!selectedNodeId) return;
  if (!confirm('Excluir este bloco e todas as suas conexões?')) return;
  nodes       = nodes.filter(n => n.id != selectedNodeId);
  connections = connections.filter(c => c.source_node_id != selectedNodeId && c.target_node_id != selectedNodeId);
  closeInspector();
});

// ── Save ─────────────────────────────────────────────────────────
document.getElementById('btn-save').addEventListener('click', function () {
  // Auto-apply any open inspector changes before saving
  if (selectedNodeId) {
    document.getElementById('btn-apply-inspector').click();
  }

  const spinner = document.getElementById('save-spinner');
  this.disabled = true;
  spinner.classList.remove('d-none');

  Api.post(BASE + '/flows/' + FLOW_ID + '/save-builder', {
    nodes:       JSON.stringify(nodes),
    connections: JSON.stringify(connections),
  }).then(res => {
    this.disabled = false;
    spinner.classList.add('d-none');
    Toast.show(res.message, res.success ? 'success' : 'danger');
  });
});

// ── Settings modal ────────────────────────────────────────────────
document.getElementById('btn-settings').addEventListener('click', () => {
  new bootstrap.Modal(document.getElementById('flowSettingsModal')).show();
});

document.getElementById('fs-trigger').addEventListener('change', function () {
  document.getElementById('fs-keywords-wrap').style.display =
    this.value === 'keyword' ? '' : 'none';
});

document.getElementById('btn-save-settings').addEventListener('click', function () {
  const spinner = document.getElementById('fs-spinner');
  this.disabled = true;
  spinner.classList.remove('d-none');

  Api.post(BASE + '/flows/' + FLOW_ID, {
    name:             document.getElementById('fs-name').value,
    trigger:          document.getElementById('fs-trigger').value,
    trigger_keywords: document.getElementById('fs-keywords').value,
    description:      document.getElementById('fs-desc').value,
    is_active:        document.getElementById('fs-active').checked ? 1 : 0,
  }).then(res => {
    this.disabled = false;
    spinner.classList.add('d-none');
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('flowSettingsModal')).hide();
      Toast.show(res.message, 'success');
      // update badge
      const active = !!res.data.flow.is_active;
      const badge  = document.getElementById('flow-status-badge');
      badge.textContent  = active ? 'Ativo' : 'Inativo';
      badge.className    = 'badge ' + (active ? 'bg-success' : 'bg-secondary');
    } else {
      Toast.show(res.message, 'danger');
    }
  });
});

// ── Load existing data ────────────────────────────────────────────
Api.get(BASE + '/flows/' + FLOW_ID + '/data').then(res => {
  if (!res.success) return;
  const flow = res.data.flow;

  // Build nodes
  (flow.nodes || []).forEach(n => {
    nodes.push({
      id:       'n' + (nextId++),
      _db_id:   n.id,
      type:     n.type,
      title:    n.title,
      config:   n.config || {},
      pos_x:    n.pos_x,
      pos_y:    n.pos_y,
      is_start: n.is_start,
    });
  });

  // Build a map from db id → temp id
  const dbToTemp = {};
  nodes.forEach(n => { if (n._db_id) dbToTemp[n._db_id] = n.id; });

  // Build connections using temp ids
  (flow.connections || []).forEach(c => {
    const src = dbToTemp[c.source_node_id];
    const tgt = dbToTemp[c.target_node_id];
    if (src && tgt) {
      connections.push({
        id:              'c' + (nextId++),
        source_node_id:  src,
        target_node_id:  tgt,
        source_port:     c.source_port  || 'output',
        target_port:     c.target_port  || 'input',
        label:           c.label        || null,
        condition_value: c.condition_value || null,
        sort_order:      c.sort_order   || 0,
      });
    }
  });

  render();
});

})();
</script>
<?php \Core\View::endSection() ?>

