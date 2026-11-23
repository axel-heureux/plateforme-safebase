<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Safebase — Console de sauvegarde</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="dashboard.css">

</head>
<body>
  <div class="app-shell d-flex">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <ellipse cx="12" cy="5" rx="7" ry="2.6"></ellipse>
            <path d="M5 5v6c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6V5"></path>
            <path d="M5 11v6c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6v-6"></path>
          </svg>
        </div>
        <div class="brand-name">Safebase</div>
      </div>

      <nav class="nav" id="nav"></nav>

      <div class="sidebar-footer" id="sidebarFooter">
        <span class="dot"></span>
        <span id="sidebarFooterText">Connexion à l'API…</span>
      </div>
    </aside>

    <main class="content">
      <header class="topbar">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-ghost d-lg-none" id="menuBtn" aria-label="Ouvrir le menu">
            <i class="bi bi-list"></i>
          </button>
          <div>
            <h1 id="viewTitle">Connexions</h1>
            <p id="viewSubtitle">Bases de données enregistrées pour la sauvegarde</p>
          </div>
        </div>

        <div id="viewActions"></div>
      </header>

      <section class="view active" id="view-connections">
        <p class="helptext">Chaque connexion pointe vers une base MySQL ou PostgreSQL. Le mot de passe est chiffré avant d'être enregistré.</p>

        <div class="card form-panel" id="formConnection">
          <div class="form-grid">
            <div class="field">
              <label for="c-name">Nom</label>
              <input id="c-name" placeholder="ex. Prod — Commandes">
            </div>
            <div class="field">
              <label for="c-engine">Moteur</label>
              <select id="c-engine">
                <option value="mysql">MySQL</option>
                <option value="postgresql">PostgreSQL</option>
              </select>
            </div>
            <div class="field">
              <label for="c-host">Hôte</label>
              <input id="c-host" placeholder="db01.internal">
            </div>
            <div class="field">
              <label for="c-port">Port</label>
              <input id="c-port" placeholder="3306">
            </div>
            <div class="field">
              <label for="c-dbname">Base</label>
              <input id="c-dbname" placeholder="commandes_prod">
            </div>
            <div class="field">
              <label for="c-user">Utilisateur</label>
              <input id="c-user" placeholder="safebase_ro">
            </div>
            <div class="field">
              <label for="c-password">Mot de passe</label>
              <input id="c-password" type="password" placeholder="••••••••">
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" id="btnSaveConnection" onclick="addConnection()">Enregistrer la connexion</button>
            <button class="btn btn-ghost" onclick="toggleForm('formConnection')">Annuler</button>
          </div>
        </div>

        <div class="card p-3">
          <div class="list" id="connectionsList" style="--cols: 1.6fr 0.9fr 1.3fr 1fr auto;"></div>
        </div>
      </section>

      <section class="view" id="view-backups">
        <div class="stat-grid" id="backupStats"></div>

        <div class="card form-panel" id="formBackup">
          <div class="form-grid">
            <div class="field">
              <label for="b-conn">Connexion</label>
              <select id="b-conn"></select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" id="btnRunBackup" onclick="runBackup()">Lancer la sauvegarde</button>
            <button class="btn btn-ghost" onclick="toggleForm('formBackup')">Annuler</button>
          </div>
        </div>

        <div class="card p-3">
          <div class="panel-head">
            <h2>Historique des sauvegardes</h2>
          </div>
          <div class="list" id="backupsList" style="--cols: 1.4fr 1fr 0.8fr 1fr 0.9fr auto;"></div>
        </div>
      </section>

      <section class="view" id="view-restores">
        <p class="helptext">Restaurer écrase les données actuelles de la base cible par le contenu de la sauvegarde sélectionnée. Cette action est irréversible.</p>

        <div class="card p-3 mb-4">
          <div class="panel-head">
            <h2>Sauvegardes disponibles pour restauration</h2>
          </div>
          <div class="list" id="restoreCandidatesList" style="--cols: 1.4fr 1fr 1fr 1fr auto;"></div>
        </div>

        <div class="card p-3">
          <div class="panel-head">
            <h2>Historique des restaurations</h2>
          </div>
          <div class="list" id="restoresList" style="--cols: 1.4fr 1fr 1fr 1fr;"></div>
        </div>
      </section>

      <section class="view" id="view-schedules">
        <p class="helptext">Les sauvegardes planifiées nécessitent une tâche CRON serveur qui appelle l'API à intervalle régulier (voir note en bas de page).</p>

        <div class="card form-panel" id="formSchedule">
          <div class="form-grid">
            <div class="field">
              <label for="s-conn">Connexion</label>
              <select id="s-conn"></select>
            </div>
            <div class="field">
              <label for="s-freq">Fréquence</label>
              <select id="s-freq">
                <option value="0 3 * * *">Chaque jour à 03:00</option>
                <option value="0 3 * * 0">Chaque dimanche à 03:00</option>
                <option value="0 */6 * * *">Toutes les 6 heures</option>
                <option value="0 3 1 * *">Le 1er du mois à 03:00</option>
              </select>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" onclick="addSchedule()">Créer la planification</button>
            <button class="btn btn-ghost" onclick="toggleForm('formSchedule')">Annuler</button>
          </div>
        </div>

        <div class="card p-3">
          <div class="list" id="schedulesList" style="--cols: 1.4fr 1.1fr 1fr 1fr auto;"></div>
        </div>
      </section>

      <section class="view" id="view-logs">
        <div class="card p-3">
          <div class="panel-head">
            <h2>Activité récente</h2>
            <select class="filter" id="logFilter" onchange="renderLogs()">
              <option value="all">Tous les niveaux</option>
              <option value="info">Info</option>
              <option value="warning">Avertissement</option>
              <option value="error">Erreur</option>
            </select>
          </div>
          <div class="list" id="logsList" style="--cols: 90px 1fr 140px;"></div>
        </div>
      </section>
    </main>
  </div>

  <div class="toast-wrap" id="toastWrap"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>
