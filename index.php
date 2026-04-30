<?php
/**
 * 心情便签 - 前端页面
 * 数据通过 api.php 持久化到 MySQL
 */

/* ━━━ 配置区 ━━━ */
define('CSRF_SECRET', 'mood_notes_csrf_2026');

// 便签可选颜色（与 api.php 中 ALLOWED_COLORS 保持一致）
$noteColors = ['#e06850', '#e8963e', '#d4a84e', '#4a90d9', '#a86cc4', '#e05a8a', '#3ab0a0'];

// 主色调候选（页面每次刷新随机选一个）
$accents = $noteColors;
/* ━━━ 配置区结束 ━━━ */

// 生成 CSRF token
$nonce = bin2hex(random_bytes(16));
$ts    = (string) time();
$sig   = hash_hmac('sha256', $nonce . '|' . $ts, CSRF_SECRET);
$csrfToken = $nonce . '|' . $ts . '|' . $sig;

// 随机主色调
$accent = $accents[array_rand($accents)];
$accentRgb = implode(',', sscanf($accent, '#%02x%02x%02x'));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>心情便签</title>
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --bg: #f8f7f4;
    --card: #ffffff;
    --text: #1a1a1a;
    --text-2: #555;
    --text-3: #999;
    --accent: <?= $accent ?>;
    --accent-rgb: <?= $accentRgb ?>;
    --border: #ebe9e4;
    --border-2: #dddad4;
    --shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.04);
    --shadow-hover: 0 4px 20px rgba(0,0,0,.08);
    --radius: 14px;
    --ease: cubic-bezier(.4,0,.2,1);
  }

  body {
    font-family: -apple-system, "SF Pro Text", "Helvetica Neue",
                 "PingFang SC", "Microsoft YaHei", sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
  }

  /* ━━━ header ━━━ */
  header {
    position: sticky; top: 0; z-index: 10;
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 24px;
    background: rgba(248,247,244,.82);
    backdrop-filter: blur(18px) saturate(180%);
    -webkit-backdrop-filter: blur(18px) saturate(180%);
    border-bottom: 1px solid var(--border);
  }

  .logo {
    display: flex; align-items: center; gap: 10px;
    user-select: none;
  }
  .logo-mark {
    width: 30px; height: 30px; border-radius: 8px;
    background: var(--accent);
    display: grid; place-items: center;
    color: #fff; font-size: 14px; font-weight: 700;
  }
  .logo h1 {
    font-size: 16px; font-weight: 650; letter-spacing: -.3px;
  }

  .header-actions {
    display: flex; align-items: center; gap: 8px;
  }

  /* ━━━ search ━━━ */
  .search-box {
    position: relative; display: flex; align-items: center;
  }
  .search-box svg {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    width: 14px; height: 14px; color: var(--text-3);
    pointer-events: none; transition: color .2s var(--ease);
  }
  #search {
    width: 180px; height: 34px;
    padding: 0 12px 0 32px;
    border: 1px solid var(--border); border-radius: 17px;
    background: var(--card); font-size: 13px; color: var(--text);
    outline: none;
    transition: width .25s var(--ease), border-color .2s, box-shadow .2s;
  }
  #search::placeholder { color: var(--text-3); }
  #search:focus {
    width: 220px; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb),.1);
  }
  #search:focus ~ svg { color: var(--accent); }

  /* ━━━ add button ━━━ */
  .btn-add {
    width: 34px; height: 34px; flex-shrink: 0;
    border: none; border-radius: 50%;
    background: var(--accent); color: #fff;
    font-size: 18px; font-weight: 300; cursor: pointer;
    display: grid; place-items: center;
    box-shadow: 0 2px 8px rgba(var(--accent-rgb),.22);
    transition: transform .15s var(--ease), box-shadow .15s, background .15s;
  }
  .btn-add:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 14px rgba(var(--accent-rgb),.32);
    filter: brightness(.88);
  }
  .btn-add:active { transform: scale(.95); }

  /* ━━━ grid ━━━ */
  .grid {
    columns: 3; column-gap: 18px;
    padding: 22px 24px 80px;
    max-width: 1040px; margin: 0 auto;
  }

  /* ━━━ card ━━━ */
  .card {
    break-inside: avoid;
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    margin-bottom: 16px;
    padding: 0 0 14px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .2s var(--ease), transform .2s var(--ease), border-color .2s;
    animation: cardPop .3s var(--ease) both;
  }
  @keyframes cardPop {
    from { opacity: 0; transform: translateY(8px) scale(.98); }
    to   { opacity: 1; transform: none; }
  }
  .card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
    border-color: var(--border-2);
  }

  .card .color-bar { height: 3px; }

  .card .card-body { padding: 14px 18px 0; }

  .card .time {
    font-size: 11px; color: var(--text-3);
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
    letter-spacing: .2px;
  }

  .card .content {
    font-size: 14px; line-height: 1.75;
    color: var(--text);
    white-space: pre-wrap; word-break: break-word;
  }

  .card .actions {
    display: flex; gap: 0;
    position: absolute; top: 10px; right: 10px;
    opacity: 0; transform: translateY(-3px);
    transition: opacity .15s, transform .15s;
  }
  .card:hover .actions { opacity: 1; transform: none; }

  .card .actions button {
    width: 28px; height: 28px;
    border: none; border-radius: 7px;
    background: transparent; color: var(--text-3);
    font-size: 13px; cursor: pointer;
    display: grid; place-items: center;
    transition: background .15s, color .15s;
  }
  .card .actions .btn-edit:hover { background: var(--accent-soft); color: var(--accent); }
  .card .actions .btn-del:hover  { background: #fef0f0; color: #c0392b; }

  /* ━━━ modal overlay ━━━ */
  .overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.2);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none;
    transition: opacity .22s var(--ease);
  }
  .overlay.open { opacity: 1; pointer-events: auto; }

  .modal {
    background: var(--card);
    border-radius: 16px;
    width: 440px; max-width: calc(100vw - 32px);
    padding: 22px 22px 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,.12);
    transform: translateY(14px) scale(.97);
    transition: transform .28s var(--ease);
  }
  .overlay.open .modal { transform: none; }

  .modal textarea {
    width: 100%; min-height: 150px; resize: vertical;
    border: 1.5px solid var(--border); border-radius: 10px;
    padding: 14px 15px; font-size: 14px; line-height: 1.75;
    font-family: inherit; outline: none; color: var(--text);
    transition: border-color .2s, box-shadow .2s;
  }
  .modal textarea::placeholder { color: var(--text-3); }
  .modal textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb),.1);
  }

  .modal-footer {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 12px;
  }

  .palette { display: flex; gap: 6px; }
  .palette span {
    width: 22px; height: 22px; border-radius: 50%;
    cursor: pointer; border: 2px solid transparent;
    transition: transform .15s var(--ease), box-shadow .15s;
  }
  .palette span:hover { transform: scale(1.2); }
  .palette span.active {
    box-shadow: 0 0 0 2px var(--card), 0 0 0 3.5px currentColor;
  }

  .modal-btns { display: flex; gap: 8px; }
  .modal-btns button {
    height: 34px; padding: 0 18px;
    border-radius: 17px; font-size: 13px; font-weight: 500;
    cursor: pointer; border: none;
    transition: all .15s var(--ease);
  }
  .btn-cancel {
    background: #f0efec; color: var(--text-2);
  }
  .btn-cancel:hover { background: #e6e4df; }
  .btn-save {
    background: var(--accent); color: #fff;
    box-shadow: 0 2px 6px rgba(var(--accent-rgb),.2);
  }
  .btn-save:hover { filter: brightness(.88); box-shadow: 0 3px 10px rgba(var(--accent-rgb),.3); }
  .btn-save:disabled { opacity: .55; cursor: not-allowed; }

  /* ━━━ empty state ━━━ */
  .empty {
    text-align: center; padding: 100px 20px 40px;
  }
  .empty-icon { font-size: 44px; margin-bottom: 14px; opacity: .75; }
  .empty-title {
    font-size: 15px; font-weight: 500; color: var(--text-2);
    margin-bottom: 4px;
  }
  .empty-sub { font-size: 13px; color: var(--text-3); }

  /* ━━━ responsive ━━━ */
  @media (max-width: 900px) {
    .grid { columns: 2; column-gap: 14px; padding: 18px 18px 80px; }
  }
  @media (max-width: 600px) {
    header { padding: 10px 14px; }
    .logo h1 { font-size: 15px; }
    #search { width: 130px; }
    #search:focus { width: 155px; }
    .grid { columns: 1; padding: 12px 12px 100px; }

    /* 手机端弹窗从底部滑出 */
    .overlay { align-items: flex-end; }
    .modal {
      width: 100%; max-width: 100vw;
      border-radius: 16px 16px 0 0;
      padding: 18px 16px 28px;
      transform: translateY(100%);
    }
    .overlay.open .modal { transform: none; }
    .modal textarea { min-height: 120px; }

    /* 手机端操作按钮常显 */
    .card .actions { opacity: 1; transform: none; }
  }

  /* ━━━ scrollbar ━━━ */
  ::-webkit-scrollbar { width: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #d5d2cc; border-radius: 3px; }
</style>
</head>
<body>

<header>
  <div class="logo">
    <div class="logo-mark">N</div>
    <h1>心情便签</h1>
  </div>
  <div class="header-actions">
    <div class="search-box">
      <input type="text" id="search" placeholder="搜索便签…">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7.5"/><path d="M20 20l-3.5-3.5"/></svg>
    </div>
    <button class="btn-add" title="新建便签">+</button>
  </div>
</header>

<div class="grid" id="grid"></div>

<div class="overlay" id="overlay">
  <div class="modal">
    <textarea id="editor" placeholder="写下你的心情…"></textarea>
    <div class="modal-footer">
      <div class="palette" id="palette"></div>
      <div class="modal-btns">
        <button class="btn-cancel" id="btnCancel">取消</button>
        <button class="btn-save" id="btnSave">保存</button>
      </div>
    </div>
  </div>
</div>

<script>
const COLORS = <?= json_encode($noteColors) ?>;
const API_URL = 'api.php';
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

let notes = [];
let editingId = null;
let selectedColor = COLORS[0];

/* ── 工具函数 ── */
function esc(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function fmt(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T'));
  const pad = n => String(n).padStart(2,'0');
  return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

/* ── API 请求 ── */
async function apiFetch(method = 'GET', body = null, id = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' }
  };
  if (method !== 'GET') {
    opts.headers['X-Api-Key'] = CSRF_TOKEN;
  }
  if (body) {
    opts.body = JSON.stringify(body);
  }

  let url = API_URL;
  if (id) url += '?id=' + encodeURIComponent(id);

  const res = await fetch(url, opts);
  const data = await res.json();

  if (!res.ok) {
    throw new Error(data.error || '请求失败');
  }

  return data;
}

/* ── 渲染 ── */
const grid = document.getElementById('grid');
const search = document.getElementById('search');

function render(filter = '') {
  const q = filter.toLowerCase();
  const list = notes.filter(n => n.content.toLowerCase().includes(q));
  if (!list.length) {
    grid.innerHTML =
      '<div class="empty">' +
        '<div class="empty-icon">' + (q ? '🔍' : '📝') + '</div>' +
        '<div class="empty-title">' + esc(q ? '没有找到匹配的便签' : '还没有便签') + '</div>' +
        '<div class="empty-sub">' + esc(q ? '试试其他关键词' : '点击右上角 + 开始记录') + '</div>' +
      '</div>';
    return;
  }
  grid.innerHTML = list.map(n =>
    '<div class="card" data-id="' + esc(n.id) + '">' +
      '<div class="color-bar" style="background:' + esc(n.color) + '"></div>' +
      '<div class="actions">' +
        '<button class="btn-edit" title="编辑">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>' +
        '</button>' +
        '<button class="btn-del" title="删除">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
        '</button>' +
      '</div>' +
      '<div class="card-body">' +
        '<div class="time">' + esc(fmt(n.updated_at || n.created_at)) + '</div>' +
        '<div class="content">' + esc(n.content) + '</div>' +
      '</div>' +
    '</div>'
  ).join('');
}

/* ── 调色板 ── */
const paletteEl = document.getElementById('palette');
COLORS.forEach(c => {
  const s = document.createElement('span');
  s.style.background = c;
  s.style.color = c;
  s.dataset.color = c;
  s.addEventListener('click', () => pickColor(c));
  paletteEl.appendChild(s);
});

function pickColor(c) {
  selectedColor = c;
  paletteEl.querySelectorAll('span').forEach(el => el.classList.toggle('active', el.dataset.color === c));
}

/* ── 弹窗 ── */
const overlay = document.getElementById('overlay');
const editor = document.getElementById('editor');

function openModal(text = '', color = COLORS[0], id = null) {
  editingId = id;
  editor.value = text;
  pickColor(color);
  overlay.classList.add('open');
  setTimeout(() => editor.focus(), 120);
}

function closeModal() {
  overlay.classList.remove('open');
  editingId = null;
}

document.querySelector('.btn-add').addEventListener('click', () => openModal());
document.getElementById('btnCancel').addEventListener('click', closeModal);
overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });

/* ── 保存 ── */
document.getElementById('btnSave').addEventListener('click', async () => {
  const text = editor.value.trim();
  if (!text) { editor.focus(); return; }

  const btn = document.getElementById('btnSave');
  btn.disabled = true;
  btn.textContent = '保存中…';

  try {
    if (editingId) {
      await apiFetch('PUT', { content: text, color: selectedColor }, editingId);
    } else {
      await apiFetch('POST', { content: text, color: selectedColor });
    }
    const data = await apiFetch('GET');
    notes = data.notes;
    render(search.value);
    closeModal();
  } catch (e) {
    alert(e.message);
  } finally {
    btn.disabled = false;
    btn.textContent = '保存';
  }
});

/* ── 卡片事件 ── */
grid.addEventListener('click', async e => {
  const card = e.target.closest('.card');
  if (!card) return;
  const id = card.dataset.id;

  if (e.target.closest('.btn-del')) {
    if (!confirm('确定删除这条便签吗？')) return;
    try {
      await apiFetch('DELETE', null, id);
      notes = notes.filter(n => n.id !== id);
      render(search.value);
    } catch (e) {
      alert(e.message);
    }
  } else if (e.target.closest('.btn-edit')) {
    const n = notes.find(n => n.id === id);
    if (n) openModal(n.content, n.color, n.id);
  }
});

/* ── 搜索 ── */
search.addEventListener('input', () => render(search.value));

/* ── 键盘快捷键 ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
  if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) document.getElementById('btnSave').click();
});

/* ── 初始化加载 ── */
(async () => {
  try {
    const data = await apiFetch('GET');
    notes = data.notes;
  } catch (e) {
    grid.innerHTML = '<div class="empty"><div class="empty-icon">⚠️</div><div class="empty-title">加载失败</div><div class="empty-sub">请刷新页面重试</div></div>';
  }
  render();
})();
</script>
</body>
</html>
