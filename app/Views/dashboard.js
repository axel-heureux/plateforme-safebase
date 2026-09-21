// Safebase — dashboard connecté à l'API PHP (App/Controllers/*).
// Aucune donnée n'est simulée ici : chaque action appelle une vraie route /api/...

const API = {
  connections: '/api/connections',
  backups: '/api/backups',
  restores: '/api/restores',
  schedules: '/api/schedules',
  logs: '/api/logs',
};

let data = { connections: [], backups: [], restores: [], schedules: [], logs: [] };
const connStatus = {}; // id -> 'ok' | 'error' (résultat du dernier test, non persisté en base)

async function api(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  let body = null;
  try { body = await res.json(); } catch (e) { /* réponse vide */ }
  if (!res.ok) {
    throw new Error((body && (body.error || body.message)) || `Erreur ${res.status}`);
  }
  return body;
}

async function loadAll() {
  try {
    const [connections, backups, restores, schedules, logs] = await Promise.all([
      api(API.connections),
      api(API.backups),
      api(API.restores),
      api(API.schedules),
      api(API.logs),
    ]);
    data = { connections, backups, restores, schedules, logs };
    setFooter(true);
  } catch (e) {
    setFooter(false, e.message);
    toast('Impossible de joindre l\u2019API : ' + e.message, true);
  }
  renderAll();
  populateSelect('b-conn');
  populateSelect('s-conn');
}

function setFooter(ok, message) {
  const dot = document.querySelector('#sidebarFooter .dot');
  const text = document.getElementById('sidebarFooterText');
  if (ok) {
    dot.className = 'dot';
    text.textContent = `${data.connections.filter(c => connStatus[c.id] !== 'error').length} connexions enregistrées`;
  } else {
    dot.className = 'dot dot-error';
    text.textContent = 'API injoignable';
  }
}

// ---------- UI helpers ----------
function toast(msg, isError) {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = 'toast' + (isError ? ' toast-error' : '');
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 4200);
}
function toggleForm(id) { document.getElementById(id).classList.toggle('open'); }
function emptyRow(text) { return `<div class="row empty">${text}</div>`; }
function connName(id) { const c = data.connections.find(c => c.id == id); return c ? c.name : 'Connexion supprimée'; }
function fmtCron(cron) {
  const map = {
    '0 3 * * *': 'Chaque jour à 03:00',
    '0 3 * * 0': 'Chaque dimanche à 03:00',
    '0 */6 * * *': 'Toutes les 6 heures',
    '0 3 1 * *': 'Le 1er du mois à 03:00',
  };
  return map[cron] || cron;
}
function populateSelect(selectId) {
  const sel = document.getElementById(selectId);
  if (!sel) return;
  sel.innerHTML = data.connections.map(c => `<option value="${c.id}">${c.name}</option>`).join('')
    || '<option disabled>Aucune connexion</option>';
}
async function withLoading(button, label, fn) {
  const original = button.textContent;
  button.disabled = true;
  button.textContent = label;
  try {
    await fn();
  } catch (e) {
    toast(e.message, true);
  } finally {
    button.disabled = false;
    button.textContent = original;
  }
}

// ---------- Icons ----------
const icons = {
  connections: '<path d="M4 7a8 3 0 0 0 16 0A8 3 0 0 0 4 7Z"/><path d="M4 7v5a8 3 0 0 0 16 0V7"/><path d="M4 12v5a8 3 0 0 0 16 0v-5"/>',
  backups: '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/>',
  restores: '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
  schedules: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
  logs: '<path d="M4 5h16"/><path d="M4 10h16"/><path d="M4 15h10"/><path d="M4 20h6"/>',
};

const views = [
  { id: 'connections', label: 'Connexions', subtitle: 'Bases de données enregistrées pour la sauvegarde', action: { label: '+ Ajouter une connexion', fn: () => toggleForm('formConnection') } },
  { id: 'backups', label: 'Sauvegardes', subtitle: 'Historique et déclenchement manuel des sauvegardes', action: { label: '+ Lancer une sauvegarde', fn: () => { populateSelect('b-conn'); toggleForm('formBackup'); } } },
  { id: 'restores', label: 'Restaurations', subtitle: 'Restaurer une base à partir d\u2019une sauvegarde existante', action: null },
  { id: 'schedules', label: 'Planification', subtitle: 'Automatisation des sauvegardes récurrentes (cron)', action: { label: '+ Nouvelle planification', fn: () => { populateSelect('s-conn'); toggleForm('formSchedule'); } } },
  { id: 'logs', label: 'Logs', subtitle: 'Journal d\u2019activité et alertes', action: null },
];

function buildNav() {
  const nav = document.getElementById('nav');
  nav.innerHTML = views.map(v => `
    <button class="nav-item ${v.id === 'connections' ? 'active' : ''}" data-view="${v.id}" onclick="switchView('${v.id}')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${icons[v.id]}</svg>
      <span>${v.label}</span>
    </button>
  `).join('');
}

function switchView(id) {
  document.querySelectorAll('.view').forEach(el => el.classList.remove('active'));
  document.getElementById('view-' + id).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(el => el.classList.toggle('active', el.dataset.view === id));
  const v = views.find(v => v.id === id);
  document.getElementById('viewTitle').textContent = v.label;
  document.getElementById('viewSubtitle').textContent = v.subtitle;
  const actionsEl = document.getElementById('viewActions');
  actionsEl.innerHTML = '';
  if (v.action) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-primary';
    btn.textContent = v.action.label;
    btn.onclick = v.action.fn;
    actionsEl.appendChild(btn);
  }
  document.getElementById('sidebar').classList.remove('open');
}

document.getElementById('menuBtn').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
});

// ---------- Connexions ----------
function renderConnections() {
  const el = document.getElementById('connectionsList');
  const rows = data.connections.map(c => `
    <div class="row">
      <div><span class="cell-label">Nom</span><span class="primary-cell">${c.name}<span class="sub">${c.db_name}</span></span></div>
      <div><span class="cell-label">Moteur</span>${c.engine === 'mysql' ? 'MySQL' : 'PostgreSQL'}</div>
      <div><span class="cell-label">Hôte</span><span class="mono">${c.host}:${c.port}</span></div>
      <div><span class="cell-label">État</span><span id="conn-status-${c.id}">${connStatusBadge(c.id)}</span></div>
      <div class="row-actions">
        <button class="btn btn-ghost" onclick="testConnection(${c.id})">Tester</button>
        <button class="btn btn-ghost" onclick="quickBackup(${c.id})">Sauvegarder</button>
        <button class="btn btn-danger-ghost" onclick="removeConnection(${c.id})">Supprimer</button>
      </div>
    </div>
  `).join('');
  el.innerHTML = `
    <div class="row head"><div>Nom</div><div>Moteur</div><div>Hôte</div><div>État</div><div></div></div>
    ${rows || emptyRow('Aucune connexion enregistrée.')}
  `;
}
function connStatusBadge(id) {
  const s = connStatus[id];
  if (s === 'ok') return `<span class="badge badge-success">Connecté</span>`;
  if (s === 'error') return `<span class="badge badge-danger">Erreur</span>`;
  return `<span class="badge badge-neutral">Non testée</span>`;
}

async function testConnection(id) {
  const target = document.getElementById(`conn-status-${id}`);
  target.innerHTML = `<span class="badge badge-neutral">Test en cours…</span>`;
  try {
    const result = await api(`${API.connections}/${id}/test`);
    connStatus[id] = result.status;
    target.innerHTML = connStatusBadge(id);
    if (result.status === 'error') toast('Connexion échouée : ' + result.message, true);
    else toast('Connexion établie avec succès.');
  } catch (e) {
    connStatus[id] = 'error';
    target.innerHTML = connStatusBadge(id);
    toast(e.message, true);
  }
}

async function addConnection() {
  const name = document.getElementById('c-name').value.trim();
  const engine = document.getElementById('c-engine').value;
  const host = document.getElementById('c-host').value.trim();
  const port = document.getElementById('c-port').value.trim() || (engine === 'mysql' ? '3306' : '5432');
  const db_name = document.getElementById('c-dbname').value.trim();
  const username = document.getElementById('c-user').value.trim();
  const password = document.getElementById('c-password').value;

  if (!name || !host || !db_name || !username) {
    toast('Renseigne au moins le nom, l\u2019hôte, la base et l\u2019utilisateur.', true);
    return;
  }

  await withLoading(document.getElementById('btnSaveConnection'), 'Enregistrement…', async () => {
    await api(API.connections, {
      method: 'POST',
      body: JSON.stringify({ name, engine, host, port, db_name, username, password }),
    });
    ['c-name', 'c-host', 'c-port', 'c-dbname', 'c-user', 'c-password'].forEach(id => document.getElementById(id).value = '');
    toggleForm('formConnection');
    toast(`Connexion « ${name} » enregistrée.`);
    await loadAll();
  });
}

async function removeConnection(id) {
  const c = data.connections.find(c => c.id == id);
  if (!c) return;
  if (!confirm(`Supprimer la connexion « ${c.name} » ? Les sauvegardes associées resteront dans l\u2019historique.`)) return;
  try {
    await api(`${API.connections}/${id}`, { method: 'DELETE' });
    await loadAll();
  } catch (e) { toast(e.message, true); }
}

// ---------- Sauvegardes ----------
function renderBackupStats() {
  const total = data.backups.length;
  const success = data.backups.filter(b => b.status === 'success').length;
  const failed = data.backups.filter(b => b.status === 'failed').length;
  const scheduled = data.schedules.filter(s => Number(s.is_active) === 1).length;
  document.getElementById('backupStats').innerHTML = `
    <div class="stat"><div class="num">${total}</div><div class="label">Sauvegardes au total</div></div>
    <div class="stat"><div class="num" style="color:var(--success)">${success}</div><div class="label">Réussies</div></div>
    <div class="stat"><div class="num" style="color:var(--danger)">${failed}</div><div class="label">Échouées</div></div>
    <div class="stat"><div class="num">${scheduled}</div><div class="label">Planifications actives</div></div>
  `;
}

function renderBackups() {
  const el = document.getElementById('backupsList');
  const sorted = [...data.backups].sort((a, b) => b.id - a.id);
  const rows = sorted.map(b => `
    <div class="row">
      <div><span class="cell-label">Connexion</span><span class="primary-cell">${connName(b.connection_id)}</span></div>
      <div><span class="cell-label">Date</span><span class="mono">${b.started_at || ''}</span></div>
      <div><span class="cell-label">Type</span>${b.type === 'auto' ? 'Auto' : 'Manuel'}</div>
      <div><span class="cell-label">Taille</span><span class="mono">${formatSize(b.file_size)}</span></div>
      <div><span class="cell-label">Statut</span>${backupStatusBadge(b.status)}</div>
      <div class="row-actions">
        ${b.status === 'success' ? `<button class="btn btn-ghost" onclick="switchView('restores')">Restaurer</button>` : ''}
      </div>
    </div>
  `).join('');
  el.innerHTML = `
    <div class="row head"><div>Connexion</div><div>Date</div><div>Type</div><div>Taille</div><div>Statut</div><div></div></div>
    ${rows || emptyRow('Aucune sauvegarde pour le moment.')}
  `;
}
function formatSize(bytes) {
  if (!bytes) return '—';
  const mb = bytes / (1024 * 1024);
  return mb >= 1 ? mb.toFixed(1) + ' MB' : (bytes / 1024).toFixed(1) + ' KB';
}
function backupStatusBadge(status) {
  if (status === 'success') return `<span class="badge badge-success">Réussie</span>`;
  if (status === 'running') return `<span class="badge badge-warning">En cours</span>`;
  return `<span class="badge badge-danger">Échouée</span>`;
}

async function runBackup() {
  const connectionId = Number(document.getElementById('b-conn').value);
  if (!connectionId) { toast('Sélectionne une connexion.', true); return; }
  await withLoading(document.getElementById('btnRunBackup'), 'Sauvegarde en cours…', async () => {
    await api(API.backups, { method: 'POST', body: JSON.stringify({ connection_id: connectionId, type: 'manual' }) });
    toggleForm('formBackup');
    await loadAll();
    toast('Sauvegarde terminée.');
  });
}
function quickBackup(connectionId) {
  switchView('backups');
  toast('Sauvegarde lancée pour « ' + connName(connectionId) + ' »…');
  api(API.backups, { method: 'POST', body: JSON.stringify({ connection_id: connectionId, type: 'manual' }) })
    .then(() => { loadAll(); toast('Sauvegarde terminée.'); })
    .catch(e => { toast(e.message, true); loadAll(); });
}

// ---------- Restaurations ----------
function renderRestores() {
  const candidates = [...data.backups].filter(b => b.status === 'success').sort((a, b) => b.id - a.id);
  const el1 = document.getElementById('restoreCandidatesList');
  el1.innerHTML = `
    <div class="row head"><div>Connexion</div><div>Date</div><div>Type</div><div>Taille</div><div></div></div>
    ${candidates.map(b => `
      <div class="row">
        <div><span class="cell-label">Connexion</span><span class="primary-cell">${connName(b.connection_id)}</span></div>
        <div><span class="cell-label">Date</span><span class="mono">${b.started_at || ''}</span></div>
        <div><span class="cell-label">Type</span>${b.type === 'auto' ? 'Auto' : 'Manuel'}</div>
        <div><span class="cell-label">Taille</span><span class="mono">${formatSize(b.file_size)}</span></div>
        <div class="row-actions"><button class="btn btn-primary" onclick="startRestore(${b.id})">Restaurer</button></div>
      </div>
    `).join('') || emptyRow('Aucune sauvegarde disponible.')}
  `;

  const el2 = document.getElementById('restoresList');
  const sorted = [...data.restores].sort((a, b) => b.id - a.id);
  el2.innerHTML = `
    <div class="row head"><div>Connexion</div><div>Sauvegarde #</div><div>Date de restauration</div><div>Statut</div></div>
    ${sorted.length ? sorted.map(r => `
      <div class="row">
        <div><span class="cell-label">Connexion</span><span class="primary-cell">${connName(r.connection_id)}</span></div>
        <div><span class="cell-label">Sauvegarde #</span><span class="mono">#${r.backup_id}</span></div>
        <div><span class="cell-label">Date</span><span class="mono">${r.started_at || ''}</span></div>
        <div><span class="cell-label">Statut</span>${backupStatusBadge(r.status)}</div>
      </div>
    `).join('') : emptyRow('Aucune restauration effectuée.')}
  `;
}

async function startRestore(backupId) {
  const b = data.backups.find(b => b.id == backupId);
  if (!b) return;
  const name = connName(b.connection_id);
  if (!confirm(`Restaurer « ${name} » à partir de la sauvegarde #${backupId} ? Les données actuelles de cette base seront remplacées.`)) return;
  toast(`Restauration de « ${name} » en cours…`);
  try {
    await api(API.restores, { method: 'POST', body: JSON.stringify({ backup_id: backupId }) });
    toast('Restauration terminée.');
  } catch (e) {
    toast(e.message, true);
  } finally {
    await loadAll();
  }
}

// ---------- Planification ----------
function renderSchedules() {
  const el = document.getElementById('schedulesList');
  const rows = data.schedules.map(s => `
    <div class="row">
      <div><span class="cell-label">Connexion</span><span class="primary-cell">${connName(s.connection_id)}</span></div>
      <div><span class="cell-label">Fréquence</span>${fmtCron(s.cron_expression)}</div>
      <div><span class="cell-label">Expression</span><span class="mono">${s.cron_expression}</span></div>
      <div><span class="cell-label">Active</span>
        <label class="switch">
          <input type="checkbox" ${Number(s.is_active) === 1 ? 'checked' : ''} onchange="toggleSchedule(${s.id}, this.checked)">
          <span class="track"></span>
        </label>
      </div>
      <div class="row-actions"><button class="btn btn-danger-ghost" onclick="removeSchedule(${s.id})">Supprimer</button></div>
    </div>
  `).join('');
  el.innerHTML = `
    <div class="row head"><div>Connexion</div><div>Fréquence</div><div>Expression cron</div><div>Active</div><div></div></div>
    ${rows || emptyRow('Aucune planification configurée.')}
  `;
}

async function addSchedule() {
  const connectionId = Number(document.getElementById('s-conn').value);
  const cron = document.getElementById('s-freq').value;
  if (!connectionId) { toast('Sélectionne une connexion.', true); return; }
  try {
    await api(API.schedules, { method: 'POST', body: JSON.stringify({ connection_id: connectionId, cron_expression: cron }) });
    toggleForm('formSchedule');
    toast('Planification créée.');
    await loadAll();
  } catch (e) { toast(e.message, true); }
}
async function toggleSchedule(id, checked) {
  try {
    await api(`${API.schedules}/${id}`, { method: 'PATCH', body: JSON.stringify({ is_active: checked }) });
    await loadAll();
  } catch (e) { toast(e.message, true); await loadAll(); }
}
async function removeSchedule(id) {
  if (!confirm('Supprimer cette planification ?')) return;
  try {
    await api(`${API.schedules}/${id}`, { method: 'DELETE' });
    await loadAll();
  } catch (e) { toast(e.message, true); }
}

// ---------- Logs ----------
function renderLogs() {
  const filter = document.getElementById('logFilter').value;
  const el = document.getElementById('logsList');
  const sorted = [...data.logs].sort((a, b) => b.id - a.id).filter(l => filter === 'all' || l.level === filter);
  el.innerHTML = sorted.map(l => `
    <div class="row">
      <div>${logBadge(l.level)}</div>
      <div>${l.message}</div>
      <div><span class="mono">${l.created_at || ''}</span></div>
    </div>
  `).join('') || emptyRow('Aucun événement pour ce filtre.');
}
function logBadge(level) {
  if (level === 'error') return `<span class="badge badge-danger">Erreur</span>`;
  if (level === 'warning') return `<span class="badge badge-warning">Alerte</span>`;
  return `<span class="badge badge-success">Info</span>`;
}

// ---------- Render all ----------
function renderAll() {
  renderConnections();
  renderBackupStats();
  renderBackups();
  renderRestores();
  renderSchedules();
  renderLogs();
}

buildNav();
loadAll();
