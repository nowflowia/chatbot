<?php \Core\View::section('title') ?>Atendimento<?php \Core\View::endSection() ?>
<?php \Core\View::section('page-title') ?>Chat<?php \Core\View::endSection() ?>

<?php \Core\View::section('styles') ?>
<style>
  /* Override main content padding/scroll for full-height chat layout */
  #main-content {
    padding: 0 !important;
    overflow: hidden;
    height: calc(100vh - var(--topbar-height));
    display: flex;
    flex-direction: column;
  }

  /* ---- Chat Wrapper ---- */
  .chat-wrapper {
    display: flex;
    height: 100%;
    overflow: hidden;
  }

  /* ---- LEFT PANEL: conversation list ---- */
  .chat-sidebar {
    width: 350px;
    min-width: 300px;
    max-width: 350px;
    border-right: 1px solid #e2e8f0;
    background: #fff;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    height: 100%;
    overflow: hidden;
  }

  .chat-sidebar-header {
    padding: .85rem 1rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
  }

  .chat-sidebar-header h6 {
    font-weight: 700;
    font-size: .9rem;
    color: #0f172a;
    margin: 0 0 .65rem;
  }

  .chat-search {
    position: relative;
  }
  .chat-search input {
    padding-left: 2.2rem;
    font-size: .82rem;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    height: 34px;
  }
  .chat-search input:focus {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
  }
  .chat-search .search-icon {
    position: absolute;
    left: .7rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: .85rem;
    pointer-events: none;
  }

  /* Status tabs */
  .chat-tabs {
    display: flex;
    gap: 2px;
    padding: .5rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .chat-tabs::-webkit-scrollbar { display: none; }

  .chat-tab {
    background: none;
    border: none;
    padding: .28rem .6rem;
    font-size: .75rem;
    font-weight: 600;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, color .15s;
    display: flex;
    align-items: center;
    gap: .3rem;
  }
  .chat-tab:hover { background: #f1f5f9; color: #0f172a; }
  .chat-tab.active { background: #eff6ff; color: var(--primary); }
  .chat-tab .tab-count {
    background: #e2e8f0;
    color: #475569;
    font-size: .65rem;
    font-weight: 700;
    padding: 0 5px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
  }
  .chat-tab.active .tab-count { background: #bfdbfe; color: #1d4ed8; }

  /* Conversation list */
  .chat-list {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .chat-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    cursor: pointer;
    transition: background .12s;
    border-bottom: 1px solid #f8fafc;
    position: relative;
    text-decoration: none;
    color: inherit;
  }
  .chat-item:hover { background: #f8fafc; }
  .chat-item.active { background: #eff6ff; }
  .chat-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--primary);
    border-radius: 0 3px 3px 0;
  }

  .chat-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    position: relative;
  }

  .chat-item-body { flex: 1; min-width: 0; }
  .chat-item-name {
    font-size: .83rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
  }
  .chat-item-preview {
    font-size: .75rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .chat-item-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
  }
  .chat-item-time {
    font-size: .68rem;
    color: #94a3b8;
    white-space: nowrap;
  }
  .chat-unread {
    background: var(--primary);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
  }

  /* Status dot on avatar */
  .status-dot {
    position: absolute;
    bottom: 1px; right: 1px;
    width: 10px; height: 10px;
    border-radius: 50%;
    border: 2px solid #fff;
  }
  .status-dot.waiting     { background: var(--warning); }
  .status-dot.in_progress { background: var(--success); }
  .status-dot.finished    { background: #94a3b8; }
  .status-dot.bot         { background: var(--purple); }

  /* ---- RIGHT PANEL: chat area ---- */
  .chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    background: #f8fafc;
  }

  /* Welcome screen */
  .chat-welcome {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: #94a3b8;
    gap: .75rem;
    user-select: none;
  }
  .chat-welcome i { font-size: 3.5rem; opacity: .35; }
  .chat-welcome p { font-size: .9rem; margin: 0; }

  /* Chat header */
  .chat-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: .65rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    flex-shrink: 0;
    min-height: 62px;
  }
  .chat-header-info { flex: 1; min-width: 0; }
  .chat-header-name {
    font-weight: 700;
    font-size: .9rem;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .chat-header-sub {
    font-size: .73rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .chat-status-pill {
    font-size: .65rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: .4px;
  }
  .status-pill-waiting     { background: #fef3c7; color: #92400e; }
  .status-pill-in_progress { background: #dcfce7; color: #166534; }
  .status-pill-finished    { background: #f1f5f9; color: #64748b; }
  .status-pill-bot         { background: #ede9fe; color: #5b21b6; }

  /* Messages area */
  .chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    overscroll-behavior: contain;
  }

  /* Load more */
  .load-more-area {
    text-align: center;
    padding: .5rem 0 .25rem;
    flex-shrink: 0;
  }

  /* Message bubbles */
  .msg-group {
    display: flex;
    flex-direction: column;
    max-width: 72%;
  }
  .msg-group.inbound  { align-self: flex-start; align-items: flex-start; }
  .msg-group.outbound { align-self: flex-end;   align-items: flex-end; }

  .msg-bubble {
    padding: .55rem .8rem;
    border-radius: 12px;
    font-size: .83rem;
    line-height: 1.45;
    word-wrap: break-word;
    white-space: pre-wrap;
    max-width: 100%;
    position: relative;
  }
  .msg-group.inbound  .msg-bubble {
    background: #fff;
    color: #1e293b;
    border-bottom-left-radius: 3px;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
  }
  .msg-group.outbound .msg-bubble {
    background: #3b82f6;
    color: #fff;
    border-bottom-right-radius: 3px;
  }

  .msg-meta {
    display: flex;
    align-items: center;
    gap: .3rem;
    margin-top: 3px;
    padding: 0 2px;
  }
  .msg-time { font-size: .67rem; color: #94a3b8; }
  .msg-group.outbound .msg-time { color: rgba(255,255,255,.65); }

  .msg-status { font-size: .75rem; }
  .msg-status.sent      { color: rgba(255,255,255,.55); }
  .msg-status.delivered { color: rgba(255,255,255,.75); }
  .msg-status.read      { color: #93c5fd; }
  .msg-status.failed    { color: #fca5a5; }
  .msg-status.pending   { color: rgba(255,255,255,.45); }

  /* Media bubbles */
  .msg-bubble.msg-media { padding: .3rem; background: transparent !important; box-shadow: none; }
  .msg-img  { max-width: 260px; max-height: 300px; border-radius: 10px; display: block; cursor: pointer; object-fit: cover; }
  .msg-video { max-width: 280px; border-radius: 10px; display: block; }
  .msg-caption { padding: .4rem .2rem 0; font-size: .8rem; }
  .msg-group.inbound  .msg-caption { color: #334155; }
  .msg-group.outbound .msg-caption { color: rgba(255,255,255,.9); }
  .msg-bubble.msg-audio { padding: .5rem .7rem; min-width: 220px; }
  .msg-audio-player { display: flex; align-items: center; gap: .5rem; }
  .msg-audio-icon { font-size: 1.2rem; flex-shrink: 0; }
  .msg-group.inbound  .msg-audio-icon { color: #3b82f6; }
  .msg-group.outbound .msg-audio-icon { color: rgba(255,255,255,.9); }
  .msg-group.outbound .msg-bubble.msg-audio audio { filter: invert(1) brightness(1.8) saturate(0); }
  .msg-bubble.msg-doc { padding: .5rem .8rem; }
  .msg-doc-link { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
  .msg-group.inbound  .msg-doc-link { color: #3b82f6; }
  .msg-group.outbound .msg-doc-link { color: #fff; }
  .msg-doc-link span { font-size: .82rem; word-break: break-all; }

  /* Date separator */
  .msg-date-sep {
    display: flex;
    align-items: center;
    gap: .5rem;
    color: #94a3b8;
    font-size: .72rem;
    font-weight: 600;
    margin: .5rem 0;
    flex-shrink: 0;
    align-self: stretch;
  }
  .msg-date-sep::before,
  .msg-date-sep::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
  }

  /* Chat footer */
  .chat-footer {
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: .75rem 1.25rem;
    display: flex;
    align-items: flex-end;
    gap: .75rem;
    flex-shrink: 0;
  }
  .chat-input-wrap {
    flex: 1;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    padding: .5rem .9rem;
    min-height: 42px;
    max-height: 120px;
    overflow-y: auto;
    font-size: .875rem;
    color: #1e293b;
    outline: none;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
    transition: border-color .2s, box-shadow .2s;
  }
  .chat-input-wrap:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    background: #fff;
  }
  .chat-input-wrap:empty::before {
    content: attr(data-placeholder);
    color: #94a3b8;
    pointer-events: none;
  }

  .btn-send {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border: none;
    border-radius: 50%;
    color: #fff;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: opacity .15s, transform .1s;
  }
  .btn-send:hover  { opacity: .9; }
  .btn-send:active { transform: scale(.93); }
  .btn-send:disabled { opacity: .45; cursor: not-allowed; }

  /* Finished notice */
  .chat-finished-notice {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: .75rem 1.25rem;
    text-align: center;
    font-size: .8rem;
    color: #64748b;
    flex-shrink: 0;
  }

  /* Skeleton loaders */
  .chat-skeleton {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    border-bottom: 1px solid #f8fafc;
    animation: skPulse 1.4s ease-in-out infinite;
  }
  .skeleton-avatar { width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; flex-shrink: 0; }
  .skeleton-lines  { flex: 1; }
  .skeleton-line   { height: 10px; border-radius: 5px; background: #e2e8f0; margin-bottom: 6px; }
  .skeleton-line.short { width: 60%; }
  @keyframes skPulse { 0%,100%{opacity:1} 50%{opacity:.45} }
</style>
<?php \Core\View::endSection() ?>

<?php \Core\View::section('content') ?>

<?php
$activeChatId = isset($activeChat) && $activeChat ? (int)$activeChat['id'] : 0;
$counts       = $counts ?? ['all' => 0, 'waiting' => 0, 'in_progress' => 0, 'finished' => 0, 'bot' => 0];
?>

<div class="chat-wrapper" id="chat-wrapper">

  <!-- ============================================================
       LEFT PANEL
  ============================================================ -->
  <aside class="chat-sidebar" id="chat-sidebar">

    <div class="chat-sidebar-header">
      <h6><i class="bi bi-chat-dots-fill me-2" style="color:var(--primary)"></i>Atendimento</h6>
      <div class="chat-search">
        <i class="bi bi-search search-icon"></i>
        <input
          type="text"
          class="form-control"
          id="chat-search-input"
          placeholder="Buscar contato ou número..."
          autocomplete="off"
        >
      </div>
    </div>

    <div class="chat-tabs" id="chat-tabs">
      <button class="chat-tab active" data-status="all">
        Todos <span class="tab-count" id="count-all"><?= (int)($counts['all'] ?? 0) ?></span>
      </button>
      <button class="chat-tab" data-status="waiting">
        <i class="bi bi-clock-history" style="font-size:.75rem"></i>
        Fila <span class="tab-count" id="count-waiting"><?= (int)($counts['waiting'] ?? 0) ?></span>
      </button>
      <button class="chat-tab" data-status="in_progress">
        <i class="bi bi-headset" style="font-size:.75rem"></i>
        Em atend. <span class="tab-count" id="count-in_progress"><?= (int)($counts['in_progress'] ?? 0) ?></span>
      </button>
      <button class="chat-tab" data-status="bot">
        <i class="bi bi-robot" style="font-size:.75rem"></i>
        Bot <span class="tab-count" id="count-bot"><?= (int)($counts['bot'] ?? 0) ?></span>
      </button>
      <button class="chat-tab" data-status="finished">
        <i class="bi bi-check2-all" style="font-size:.75rem"></i>
        Fin. <span class="tab-count" id="count-finished"><?= (int)($counts['finished'] ?? 0) ?></span>
      </button>
    </div>

    <div class="chat-list" id="chat-list">
      <?php for ($i = 0; $i < 5; $i++): ?>
      <div class="chat-skeleton">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-lines">
          <div class="skeleton-line" style="width:55%"></div>
          <div class="skeleton-line short"></div>
        </div>
      </div>
      <?php endfor; ?>
    </div>

  </aside>

  <!-- ============================================================
       RIGHT PANEL
  ============================================================ -->
  <div class="chat-main" id="chat-main">

    <!-- Welcome -->
    <div class="chat-welcome" id="chat-welcome" <?= $activeChatId ? 'style="display:none"' : '' ?>>
      <i class="bi bi-chat-square-dots"></i>
      <p>Selecione uma conversa para iniciar o atendimento</p>
      <small style="color:#cbd5e1;font-size:.78rem">As conversas são atualizadas automaticamente a cada 3 segundos</small>
    </div>

    <!-- Active chat -->
    <div
      id="chat-active-area"
      style="<?= $activeChatId ? '' : 'display:none;' ?>flex-direction:column;height:100%;overflow:hidden;<?= $activeChatId ? 'display:flex' : '' ?>"
    >
      <!-- Header -->
      <div class="chat-header" id="chat-header">
        <div class="chat-avatar" id="chat-header-avatar" style="background:#6366f1;width:38px;height:38px;font-size:.8rem;">
          <span id="chat-header-initials">?</span>
        </div>
        <div class="chat-header-info">
          <div class="chat-header-name" id="chat-header-name">—</div>
          <div class="chat-header-sub">
            <span id="chat-header-phone"></span>
            <span class="chat-status-pill" id="chat-header-status-pill"></span>
            <span id="chat-header-assigned" style="font-size:.7rem;color:#94a3b8"></span>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-shrink-0">
          <button class="btn btn-sm btn-outline-primary" id="btn-assign" onclick="ChatUI.assignChat()" title="Assumir atendimento">
            <i class="bi bi-headset me-1"></i>Assumir
          </button>
          <button class="btn btn-sm btn-outline-danger" id="btn-finish" onclick="ChatUI.finishChat()" title="Finalizar conversa">
            <i class="bi bi-check2-all me-1"></i>Finalizar
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div class="chat-messages" id="chat-messages">
        <div class="load-more-area" id="load-more-area" style="display:none">
          <button class="btn btn-sm btn-outline-secondary" id="btn-load-more" onclick="ChatUI.loadMoreMessages()">
            <i class="bi bi-arrow-up-circle me-1"></i>Carregar mensagens anteriores
          </button>
        </div>
        <div id="messages-container"></div>
        <div id="messages-loading" class="text-center py-3" style="display:none">
          <div class="spinner-border spinner-border-sm text-secondary"></div>
        </div>
      </div>

      <!-- Footer (input or finished notice) -->
      <div id="chat-footer-area"></div>

    </div>

  </div>

</div>

<?php \Core\View::endSection() ?>

<?php \Core\View::section('scripts') ?>
<script>
(function () {
  'use strict';

  /* ----------------------------------------------------------------
     State
  ---------------------------------------------------------------- */
  var state = {
    activeChatId:    <?= $activeChatId ?: 'null' ?>,
    currentStatus:   'all',
    searchQuery:     '',
    listPollTimer:   null,
    msgPollTimer:    null,
    oldestMsgId:     null,
    hasMoreMessages: false,
    isSending:       false,
    isLoadingMsgs:   false,
    renderedMsgIds:  {},
    chatCache:       {},
  };

  /* ----------------------------------------------------------------
     URLs
  ---------------------------------------------------------------- */
  var BASE = '<?= rtrim(url('admin/chat'), '/') ?>';

  function urlList()       { return BASE + '/list/active'; }
  function urlMsgs(id)     { return BASE + '/' + id + '/messages'; }
  function urlData(id)     { return BASE + '/' + id + '/data'; }
  function urlSend(id)     { return BASE + '/' + id + '/message'; }
  function urlAssign(id)   { return BASE + '/' + id + '/assign'; }
  function urlFinish(id)   { return BASE + '/' + id + '/finish'; }
  function urlPage(id)     { return BASE + '/' + id; }

  /* ----------------------------------------------------------------
     Helpers
  ---------------------------------------------------------------- */
  function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function statusLabel(s) {
    return { waiting: 'Fila', in_progress: 'Em atendimento', finished: 'Finalizado', bot: 'Bot' }[s] || s;
  }

  function statusPillClass(s) {
    var map = {
      waiting:     'status-pill-waiting',
      in_progress: 'status-pill-in_progress',
      finished:    'status-pill-finished',
      bot:         'status-pill-bot',
    };
    return 'chat-status-pill ' + (map[s] || '');
  }

  function msgStatusIcon(status, direction) {
    if (direction !== 'outbound') return '';
    var icons = {
      pending:   '<i class="bi bi-clock        msg-status pending"   title="Enviando..."></i>',
      sent:      '<i class="bi bi-check        msg-status sent"      title="Enviado"></i>',
      delivered: '<i class="bi bi-check2       msg-status delivered" title="Entregue"></i>',
      read:      '<i class="bi bi-check2-all   msg-status read"      title="Lido"></i>',
      failed:    '<i class="bi bi-x-circle     msg-status failed"    title="Falhou"></i>',
    };
    return icons[status] || icons['sent'];
  }

  function dateLabel(dateStr) {
    if (!dateStr) return '';
    try {
      var d     = new Date(String(dateStr).replace(' ', 'T'));
      var now   = new Date();
      var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      var dt    = new Date(d.getFullYear(), d.getMonth(), d.getDate());
      var diff  = Math.round((today - dt) / 86400000);
      if (diff === 0) return 'Hoje';
      if (diff === 1) return 'Ontem';
      return d.toLocaleDateString('pt-BR');
    } catch (e) { return ''; }
  }

  function timeLabel(dateStr) {
    if (!dateStr) return '';
    try {
      return new Date(String(dateStr).replace(' ', 'T'))
        .toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) { return ''; }
  }

  /* ----------------------------------------------------------------
     Render chat list item
  ---------------------------------------------------------------- */
  function renderChatItem(chat) {
    var c       = chat.contact || {};
    var isActive = (state.activeChatId !== null && chat.id === state.activeChatId);
    var unread  = parseInt(chat.unread_count, 10) || 0;

    return '<div class="chat-item' + (isActive ? ' active' : '') + '" '
         + 'data-chat-id="' + chat.id + '" '
         + 'onclick="ChatUI.openChat(' + chat.id + ')">'
         + '<div class="chat-avatar" style="background:' + esc(c.color || '#6366f1') + '">'
         +   esc(c.initials || '?')
         +   '<span class="status-dot ' + esc(chat.status) + '"></span>'
         + '</div>'
         + '<div class="chat-item-body">'
         +   '<div class="chat-item-name">' + esc(c.name || 'Desconhecido') + '</div>'
         +   '<div class="chat-item-preview">' + esc(chat.last_message || 'Sem mensagens') + '</div>'
         + '</div>'
         + '<div class="chat-item-meta">'
         +   '<span class="chat-item-time">' + esc(chat.last_message_human || '') + '</span>'
         +   (unread > 0 ? '<span class="chat-unread">' + unread + '</span>' : '')
         + '</div>'
         + '</div>';
  }

  /* ----------------------------------------------------------------
     Render message bubble
  ---------------------------------------------------------------- */
  function renderMessage(msg, prevDateLabel) {
    var dir      = msg.direction === 'outbound' ? 'outbound' : 'inbound';
    var time     = timeLabel(msg.created_at);
    var thisDate = dateLabel(msg.created_at);
    var html     = '';

    if (thisDate !== prevDateLabel) {
      html += '<div class="msg-date-sep">' + esc(thisDate) + '</div>';
    }

    var bubble = renderBubble(msg);

    html += '<div class="msg-group ' + dir + '" id="msg-' + msg.id + '">'
          +   bubble
          +   '<div class="msg-meta">'
          +     '<span class="msg-time">' + esc(time) + '</span>'
          +     msgStatusIcon(msg.status, msg.direction)
          +   '</div>'
          + '</div>';

    return html;
  }

  function renderBubble(msg) {
    var type    = msg.type    || 'text';
    var url     = msg.media_url      || null;
    var mime    = msg.media_mime     || '';
    var fname   = msg.media_filename || null;
    var caption = msg.content ? esc(msg.content).replace(/\n/g,'<br>') : '';

    switch (type) {
      case 'image':
        if (url) {
          return '<div class="msg-bubble msg-media">'
            + '<a href="' + esc(url) + '" target="_blank" rel="noopener">'
            + '<img src="' + esc(url) + '" alt="Imagem" class="msg-img" onerror="this.closest(\'.msg-bubble\').innerHTML=\'<span>[Imagem]</span>\'">'
            + '</a>'
            + (caption ? '<div class="msg-caption">' + caption + '</div>' : '')
            + '</div>';
        }
        return '<div class="msg-bubble"><i class="bi bi-image me-1"></i>' + (caption || '[Imagem]') + '</div>';

      case 'gif':
        if (url) {
          return '<div class="msg-bubble msg-media">'
            + '<video autoplay loop muted playsinline class="msg-img" style="cursor:default">'
            + '<source src="' + esc(url) + '" type="video/mp4">'
            + '</video>'
            + (caption ? '<div class="msg-caption">' + caption + '</div>' : '')
            + '</div>';
        }
        return '<div class="msg-bubble"><i class="bi bi-film me-1"></i>[GIF]</div>';

      case 'video':
        if (url) {
          return '<div class="msg-bubble msg-media">'
            + '<video controls class="msg-video" preload="metadata">'
            + '<source src="' + esc(url) + '" type="' + esc(mime || 'video/mp4') + '">'
            + '</video>'
            + (caption ? '<div class="msg-caption">' + caption + '</div>' : '')
            + '</div>';
        }
        return '<div class="msg-bubble"><i class="bi bi-camera-video me-1"></i>' + (caption || '[Vídeo]') + '</div>';

      case 'audio':
        if (url) {
          return '<div class="msg-bubble msg-audio">'
            + '<div class="msg-audio-player">'
            + '<i class="bi bi-mic-fill msg-audio-icon"></i>'
            + '<audio controls preload="metadata" style="flex:1;min-width:0;height:32px">'
            + '<source src="' + esc(url) + '" type="' + esc(mime || 'audio/ogg') + '">'
            + '</audio>'
            + '</div>'
            + '</div>';
        }
        return '<div class="msg-bubble"><i class="bi bi-mic me-1"></i>[Áudio]</div>';

      case 'document':
        if (url) {
          var label = fname ? esc(fname) : 'Documento';
          return '<div class="msg-bubble msg-doc">'
            + '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="msg-doc-link">'
            + '<i class="bi bi-file-earmark-arrow-down fs-4"></i>'
            + '<span>' + label + '</span>'
            + '</a>'
            + '</div>';
        }
        return '<div class="msg-bubble"><i class="bi bi-file-earmark me-1"></i>' + (caption || '[Documento]') + '</div>';

      case 'sticker':
        if (url) {
          return '<div class="msg-bubble msg-media" style="background:transparent;box-shadow:none;padding:0">'
            + '<img src="' + esc(url) + '" alt="Figurinha" style="max-width:120px">'
            + '</div>';
        }
        return '<div class="msg-bubble">🏷️ [Figurinha]</div>';

      case 'location':
        var parts   = (msg.content || '').replace('[Localização] ','').split(',');
        var lat     = parts[0] || '';
        var lng     = parts[1] || '';
        var mapsUrl = 'https://maps.google.com/?q=' + encodeURIComponent(lat + ',' + lng);
        return '<div class="msg-bubble">'
          + '<a href="' + mapsUrl + '" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none">'
          + '<i class="bi bi-geo-alt-fill text-danger fs-5"></i>'
          + '<span>Ver localização</span>'
          + '</a></div>';

      default:
        // text or unknown
        return '<div class="msg-bubble">' + (caption || esc(msg.content || '').replace(/\n/g,'<br>')) + '</div>';
    }
  }

  /* ----------------------------------------------------------------
     Update right-panel header
  ---------------------------------------------------------------- */
  function updateHeader(chat) {
    var c = chat.contact || {};

    document.getElementById('chat-header-initials').textContent       = c.initials || '?';
    document.getElementById('chat-header-avatar').style.background    = c.color || '#6366f1';
    document.getElementById('chat-header-name').textContent           = c.name || 'Desconhecido';
    document.getElementById('chat-header-phone').textContent          = c.phone || '';

    var pill = document.getElementById('chat-header-status-pill');
    pill.className   = statusPillClass(chat.status);
    pill.textContent = statusLabel(chat.status);

    var assignedEl = document.getElementById('chat-header-assigned');
    assignedEl.textContent = chat.assigned_user_name ? 'Agente: ' + chat.assigned_user_name : '';

    var btnAssign = document.getElementById('btn-assign');
    var btnFinish = document.getElementById('btn-finish');

    if (chat.status === 'finished') {
      btnAssign.style.display = 'none';
      btnFinish.style.display = 'none';
    } else {
      btnFinish.style.display = '';
      btnAssign.style.display = (chat.status === 'in_progress' && chat.assigned_to) ? 'none' : '';
    }

    renderFooter(chat);
  }

  /* ----------------------------------------------------------------
     Footer: input or finished notice
  ---------------------------------------------------------------- */
  function renderFooter(chat) {
    var area = document.getElementById('chat-footer-area');
    if (!area) return;

    if (chat.status === 'finished') {
      // Only replace if not already showing the finished notice
      if (!area.querySelector('.chat-finished-notice')) {
        area.innerHTML = '<div class="chat-finished-notice">'
          + '<i class="bi bi-lock-fill me-1"></i>'
          + 'Esta conversa foi finalizada e não aceita novas mensagens.'
          + '</div>';
      }
      return;
    }

    // If input already exists, do NOT re-render — preserves what the user is typing
    if (document.getElementById('chat-input')) return;

    area.innerHTML = '<div class="chat-footer">'
      + '<div class="chat-input-wrap" id="chat-input" contenteditable="true" '
      + 'data-placeholder="Digite uma mensagem... (Enter para enviar)" '
      + 'spellcheck="true" role="textbox" aria-multiline="false"></div>'
      + '<button class="btn-send" id="btn-send" onclick="ChatUI.sendMessage()" title="Enviar (Enter)">'
      + '<i class="bi bi-send-fill"></i>'
      + '</button>'
      + '</div>';

    var input = document.getElementById('chat-input');
    if (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          ChatUI.sendMessage();
        }
      });
      setTimeout(function () { input.focus(); }, 50);
    }
  }

  /* ----------------------------------------------------------------
     Scroll messages to bottom
  ---------------------------------------------------------------- */
  function scrollToBottom(force) {
    var el = document.getElementById('chat-messages');
    if (!el) return;
    if (force) { el.scrollTop = el.scrollHeight; return; }
    var dist = el.scrollHeight - el.scrollTop - el.clientHeight;
    if (dist < 150) el.scrollTop = el.scrollHeight;
  }

  /* ----------------------------------------------------------------
     Fetch chat list
  ---------------------------------------------------------------- */
  function fetchChatList() {
    var qs = '?status=' + encodeURIComponent(state.currentStatus)
           + '&search=' + encodeURIComponent(state.searchQuery);

    Api.get(urlList() + qs).then(function (res) {
      if (!res || !res.success) return;

      var c = res.data.counts || {};
      ['all', 'waiting', 'in_progress', 'finished', 'bot'].forEach(function (s) {
        var el = document.getElementById('count-' + s);
        if (el) el.textContent = c[s] || 0;
      });

      var chats = res.data.chats || [];
      chats.forEach(function (ch) { state.chatCache[ch.id] = ch; });

      var listEl = document.getElementById('chat-list');
      if (!listEl) return;

      if (chats.length === 0) {
        listEl.innerHTML = '<div class="empty-state"><i class="bi bi-chat-slash"></i><p>Nenhuma conversa encontrada</p></div>';
        return;
      }

      listEl.innerHTML = chats.map(renderChatItem).join('');

      // Refresh header if active chat updated
      if (state.activeChatId && state.chatCache[state.activeChatId]) {
        updateHeader(state.chatCache[state.activeChatId]);
      }
    });
  }

  /* ----------------------------------------------------------------
     Fetch messages (initial or load-more)
  ---------------------------------------------------------------- */
  function fetchMessages(chatId, prepend, beforeId) {
    if (state.isLoadingMsgs) return;
    state.isLoadingMsgs = true;

    var qs = '?limit=40' + (beforeId ? '&before_id=' + beforeId : '');

    var loadEl = document.getElementById('messages-loading');
    if (loadEl && !prepend) loadEl.style.display = 'block';

    Api.get(urlMsgs(chatId) + qs).then(function (res) {
      state.isLoadingMsgs = false;
      if (loadEl) loadEl.style.display = 'none';
      if (!res || !res.success) return;

      var msgs = res.data.messages || [];
      state.hasMoreMessages = !!res.data.has_more;

      var container = document.getElementById('messages-container');
      if (!container) return;

      if (prepend) {
        var msgsArea     = document.getElementById('chat-messages');
        var prevHeight   = msgsArea ? msgsArea.scrollHeight : 0;
        var prevDateLbl  = null;
        var html = '';
        msgs.forEach(function (msg) {
          html += renderMessage(msg, prevDateLbl);
          prevDateLbl = dateLabel(msg.created_at);
          state.renderedMsgIds[msg.id] = true;
        });
        var frag = document.createElement('div');
        frag.innerHTML = html;
        while (frag.lastChild) {
          container.insertBefore(frag.lastChild, container.firstChild);
        }
        if (msgsArea) msgsArea.scrollTop = msgsArea.scrollHeight - prevHeight;
      } else {
        state.renderedMsgIds = {};
        var prevDateLbl = null;
        var html = '';
        msgs.forEach(function (msg) {
          html += renderMessage(msg, prevDateLbl);
          prevDateLbl = dateLabel(msg.created_at);
          state.renderedMsgIds[msg.id] = true;
        });
        container.innerHTML = html;
        scrollToBottom(true);
      }

      if (msgs.length > 0) state.oldestMsgId = msgs[0].id;

      var lmArea = document.getElementById('load-more-area');
      if (lmArea) lmArea.style.display = state.hasMoreMessages ? 'block' : 'none';
    });
  }

  /* ----------------------------------------------------------------
     Poll for new messages (silent, append only)
  ---------------------------------------------------------------- */
  function pollMessages() {
    if (!state.activeChatId) return;
    var container = document.getElementById('messages-container');
    if (!container) return;

    Api.get(urlMsgs(state.activeChatId) + '?limit=20').then(function (res) {
      if (!res || !res.success) return;

      var msgs    = res.data.messages || [];
      var newMsgs = msgs.filter(function (m) { return !state.renderedMsgIds[m.id]; });
      if (newMsgs.length === 0) return;

      var allGroups = container.querySelectorAll('.msg-group');
      var lastGroup = allGroups.length > 0 ? allGroups[allGroups.length - 1] : null;

      // Determine last rendered date for separator logic
      var prevDateLbl = null;
      if (lastGroup) {
        // Find the date separator that precedes the last group
        var prev = lastGroup.previousElementSibling;
        while (prev) {
          if (prev.classList && prev.classList.contains('msg-date-sep')) {
            prevDateLbl = prev.textContent.trim();
            break;
          }
          prev = prev.previousElementSibling;
        }
        // Re-map prevDateLbl back to check against today/yesterday strings
        // We'll just pass null to force sep check by comparing raw date strings
      }

      // Determine the created_at of the last rendered message
      var lastRenderedDate = null;
      if (lastGroup) {
        var lastMsgId = lastGroup.id.replace('msg-', '');
        // Can't easily get date back, so use null to always check separator
      }

      var html = '';
      newMsgs.forEach(function (msg) {
        html += renderMessage(msg, null);
        state.renderedMsgIds[msg.id] = true;
      });

      var tmp = document.createElement('div');
      tmp.innerHTML = html;
      while (tmp.firstChild) container.appendChild(tmp.firstChild);

      scrollToBottom(false);
    });
  }

  /* ----------------------------------------------------------------
     ChatUI public API
  ---------------------------------------------------------------- */
  window.ChatUI = {

    openChat: function (chatId) {
      chatId = parseInt(chatId, 10);
      if (state.activeChatId === chatId) return;

      if (state.msgPollTimer) { clearInterval(state.msgPollTimer); state.msgPollTimer = null; }

      state.activeChatId   = chatId;
      state.renderedMsgIds = {};
      state.oldestMsgId    = null;
      state.hasMoreMessages = false;

      history.pushState({ chatId: chatId }, '', urlPage(chatId));

      document.getElementById('chat-welcome').style.display     = 'none';
      document.getElementById('chat-active-area').style.display = 'flex';

      var container = document.getElementById('messages-container');
      if (container) container.innerHTML = '';
      var footerArea = document.getElementById('chat-footer-area');
      if (footerArea) footerArea.innerHTML = '';

      document.querySelectorAll('.chat-item').forEach(function (el) {
        el.classList.toggle('active', parseInt(el.dataset.chatId, 10) === chatId);
      });

      function afterChatData(chat) {
        state.chatCache[chatId] = chat;
        updateHeader(chat);
        fetchMessages(chatId, false, null);
        state.msgPollTimer = setInterval(pollMessages, 2000);
      }

      if (state.chatCache[chatId]) {
        afterChatData(state.chatCache[chatId]);
      } else {
        Api.get(urlData(chatId)).then(function (res) {
          if (res && res.success && res.data && res.data.chat) {
            afterChatData(res.data.chat);
          } else {
            fetchMessages(chatId, false, null);
            state.msgPollTimer = setInterval(pollMessages, 2000);
          }
        });
      }
    },

    loadMoreMessages: function () {
      if (!state.activeChatId || !state.oldestMsgId || !state.hasMoreMessages) return;
      fetchMessages(state.activeChatId, true, state.oldestMsgId);
    },

    sendMessage: function () {
      if (state.isSending || !state.activeChatId) return;

      var inputEl = document.getElementById('chat-input');
      if (!inputEl) return;

      var content = (inputEl.innerText || '').trim();
      if (!content) return;

      state.isSending = true;
      var btnSend = document.getElementById('btn-send');
      if (btnSend) btnSend.disabled = true;

      // Optimistic bubble
      var tempId  = 'tmp' + Date.now();
      var tempMsg = {
        id: tempId,
        direction: 'outbound',
        content: content,
        status: 'pending',
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
      };
      var container = document.getElementById('messages-container');
      if (container) {
        var tmp = document.createElement('div');
        tmp.innerHTML = renderMessage(tempMsg, null);
        while (tmp.firstChild) container.appendChild(tmp.firstChild);
      }
      inputEl.innerText = '';
      scrollToBottom(true);

      Api.post(urlSend(state.activeChatId), { content: content }).then(function (res) {
        state.isSending = false;
        if (btnSend) btnSend.disabled = false;

        var tmpEl = document.getElementById('msg-' + tempId);
        if (tmpEl) tmpEl.remove();

        if (!res || !res.success) {
          Toast.show((res && res.message) ? res.message : 'Erro ao enviar mensagem.', 'error');
          return;
        }

        var msg = res.data && res.data.message;
        if (msg && !state.renderedMsgIds[msg.id]) {
          state.renderedMsgIds[msg.id] = true;
          if (container) {
            var tmp2 = document.createElement('div');
            tmp2.innerHTML = renderMessage(msg, null);
            while (tmp2.firstChild) container.appendChild(tmp2.firstChild);
          }
          scrollToBottom(true);
        }

        fetchChatList();
      });
    },

    assignChat: function () {
      if (!state.activeChatId) return;
      var btnAssign = document.getElementById('btn-assign');
      if (btnAssign) { btnAssign.disabled = true; btnAssign.textContent = 'Aguarde...'; }

      Api.post(urlAssign(state.activeChatId), {}).then(function (res) {
        if (btnAssign) { btnAssign.disabled = false; btnAssign.innerHTML = '<i class="bi bi-headset me-1"></i>Assumir'; }

        if (!res || !res.success) {
          Toast.show((res && res.message) ? res.message : 'Erro ao assumir conversa.', 'error');
          return;
        }
        Toast.show('Atendimento assumido!', 'success');
        if (res.data && res.data.chat) {
          state.chatCache[res.data.chat.id] = res.data.chat;
          updateHeader(res.data.chat);
        }
        fetchChatList();
      });
    },

    finishChat: function () {
      if (!state.activeChatId) return;
      if (!window.confirm('Finalizar esta conversa?')) return;

      var btnFinish = document.getElementById('btn-finish');
      if (btnFinish) { btnFinish.disabled = true; btnFinish.textContent = 'Finalizando...'; }

      Api.post(urlFinish(state.activeChatId), {}).then(function (res) {
        if (btnFinish) { btnFinish.disabled = false; btnFinish.innerHTML = '<i class="bi bi-check2-all me-1"></i>Finalizar'; }

        if (!res || !res.success) {
          Toast.show((res && res.message) ? res.message : 'Erro ao finalizar.', 'error');
          return;
        }
        Toast.show('Conversa finalizada.', 'success');
        if (res.data && res.data.chat) {
          state.chatCache[res.data.chat.id] = res.data.chat;
          updateHeader(res.data.chat);
        }
        if (state.msgPollTimer) { clearInterval(state.msgPollTimer); state.msgPollTimer = null; }
        fetchChatList();
      });
    },

  };

  /* ----------------------------------------------------------------
     Tab switching
  ---------------------------------------------------------------- */
  document.getElementById('chat-tabs').addEventListener('click', function (e) {
    var tab = e.target.closest('.chat-tab');
    if (!tab) return;
    document.querySelectorAll('.chat-tab').forEach(function (t) { t.classList.remove('active'); });
    tab.classList.add('active');
    state.currentStatus = tab.dataset.status;
    fetchChatList();
  });

  /* ----------------------------------------------------------------
     Search (debounced 350ms)
  ---------------------------------------------------------------- */
  var searchTimer = null;
  document.getElementById('chat-search-input').addEventListener('input', function () {
    clearTimeout(searchTimer);
    var val = this.value;
    searchTimer = setTimeout(function () {
      state.searchQuery = val.trim();
      fetchChatList();
    }, 350);
  });

  /* ----------------------------------------------------------------
     Infinite scroll upward to load older messages
  ---------------------------------------------------------------- */
  document.getElementById('chat-messages').addEventListener('scroll', function () {
    if (this.scrollTop < 80 && state.hasMoreMessages && !state.isLoadingMsgs && state.activeChatId) {
      ChatUI.loadMoreMessages();
    }
  });

  /* ----------------------------------------------------------------
     Browser back/forward
  ---------------------------------------------------------------- */
  window.addEventListener('popstate', function (e) {
    if (e.state && e.state.chatId) ChatUI.openChat(e.state.chatId);
  });

  /* ----------------------------------------------------------------
     Boot
  ---------------------------------------------------------------- */
  document.addEventListener('DOMContentLoaded', function () {
    fetchChatList();
    state.listPollTimer = setInterval(fetchChatList, 3000);

    <?php if ($activeChatId): ?>
    // Page loaded directly with a chat open — state.activeChatId is already set,
    // so openChat() would bail early. Boot the chat directly instead.
    (function () {
      var chatId = <?= $activeChatId ?>;
      state.renderedMsgIds  = {};
      state.oldestMsgId     = null;
      state.hasMoreMessages = false;

      document.getElementById('chat-welcome').style.display     = 'none';
      document.getElementById('chat-active-area').style.display = 'flex';

      // Load chat data then messages
      Api.get('<?= url('admin/chat') ?>/' + chatId + '/data').then(function (res) {
        if (res && res.success && res.data && res.data.chat) {
          var chat = res.data.chat;
          state.chatCache[chatId] = chat;
          updateHeader(chat);
        }
        fetchMessages(chatId, false, null);
        state.msgPollTimer = setInterval(pollMessages, 2000);
      });
    })();
    <?php endif; ?>
  });

})();
</script>
<?php \Core\View::endSection() ?>
