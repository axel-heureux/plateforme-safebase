<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/RestoreModel.php';
require_once __DIR__ . '/../Models/BackupModel.php';
require_once __DIR__ . '/../Models/DbConnectionModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../../Core/Crypto.php';

class RestoreController extends BaseController
{
    private RestoreModel $restores;
    private BackupModel $backups;
    private DbConnectionModel $connections;
    private LogModel $logs;

    public function __construct()
    {
        $this->restores = new RestoreModel();
        $this->backups = new BackupModel();
        $this->connections = new DbConnectionModel();
        $this->logs = new LogModel();
    }

    // GET /api/restores
    public function index(): void
    {
        $this->json($this->restores->getAll());
    }

    // POST /api/restores   body: { backup_id }
    public function store(): void
    {
        $data = $this->input();
        $backupId = $data['backup_id'] ?? null;

        if (!$backupId) {
            $this->error('backup_id est requis.');
        }

        $backup = $this->backups->getById($backupId);
        if (!$backup || $backup['status'] !== 'success') {
            $this->error('Sauvegarde introuvable ou invalide.', 404);
        }

        $connection = $this->connections->getById($backup['connection_id']);
        if (!$connection) {
            $this->error('Connexion introuvable.', 404);
        }

        $restoreId = $this->restores->add([
            'backup_id'     => $backupId,
            'connection_id' => $connection['id'],
            'status'        => 'running',
        ]);

        $result = $this->runRestore($connection, $backup['file_path']);

        if ($result['success']) {
            $this->restores->edit($restoreId, [
                'status'      => 'success',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $this->logs->add([
                'level'   => 'info',
                'message' => "Restauration réussie sur « {$connection['name']} ».",
            ]);
        } else {
            $this->restores->edit($restoreId, [
                'status'        => 'failed',
                'error_message' => $result['error'],
                'finished_at'   => date('Y-m-d H:i:s'),
            ]);
            $this->logs->add([
                'level'   => 'error',
                'message' => "Échec de la restauration sur « {$connection['name']} » : {$result['error']}",
            ]);
        }

        $this->json($this->restores->getById($restoreId), $result['success'] ? 201 : 500);
    }

    private function runRestore(array $connection, string $filepath): array
    {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Fichier de sauvegarde introuvable sur le serveur.'];
        }

        $password = Crypto::decrypt($connection['password_enc']);

        putenv('MYSQL_PWD=' . $password);
        putenv('PGPASSWORD=' . $password);

        if ($connection['engine'] === 'mysql') {
            $cmd = sprintf(
                'mysql --host=%s --port=%d --user=%s %s < %s 2>&1',
                escapeshellarg($connection['host']),
                (int) $connection['port'],
                escapeshellarg($connection['username']),
                escapeshellarg($connection['db_name']),
                escapeshellarg($filepath)
            );
        } else {
            $cmd = sprintf(
                'psql --host=%s --port=%d --username=%s --dbname=%s --file=%s 2>&1',
                escapeshellarg($connection['host']),
                (int) $connection['port'],
                escapeshellarg($connection['username']),
                escapeshellarg($connection['db_name']),
                escapeshellarg($filepath)
            );
        }

        exec($cmd, $output, $exitCode);

        putenv('MYSQL_PWD');
        putenv('PGPASSWORD');

        if ($exitCode !== 0) {
            return [
                'success' => false,
                'error'   => trim(implode("\n", $output)) ?: 'La commande de restauration a échoué.',
            ];
        }

        return ['success' => true];
    }
}
