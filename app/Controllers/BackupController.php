<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/BackupModel.php';
require_once __DIR__ . '/../Models/DbConnectionModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../../Core/Crypto.php';

class BackupController extends BaseController
{
    private BackupModel $backups;
    private DbConnectionModel $connections;
    private LogModel $logs;
    private string $storagePath;

    public function __construct()
    {
        $this->backups = new BackupModel();
        $this->connections = new DbConnectionModel();
        $this->logs = new LogModel();
        $this->storagePath = __DIR__ . '/../../storage/backups';

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }

    // GET /api/backups
    public function index(): void
    {
        $this->json($this->backups->getAll());
    }

    // POST /api/backups   body: { connection_id, type: 'manual'|'auto' }
    public function store(): void
    {
        $data = $this->input();
        $connectionId = $data['connection_id'] ?? null;

        if (!$connectionId) {
            $this->error('connection_id est requis.');
        }

        $connection = $this->connections->getById($connectionId);
        if (!$connection) {
            $this->error('Connexion introuvable.', 404);
        }

        $backupId = $this->backups->add([
            'connection_id' => $connectionId,
            'file_path'     => '',
            'type'          => $data['type'] ?? 'manual',
            'status'        => 'running',
        ]);

        $result = $this->runDump($connection);

        if ($result['success']) {
            $this->backups->edit($backupId, [
                'file_path'   => $result['file'],
                'file_size'   => filesize($result['file']),
                'status'      => 'success',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $this->logs->add([
                'level'   => 'info',
                'message' => "Sauvegarde réussie pour « {$connection['name']} ».",
            ]);
        } else {
            $this->backups->edit($backupId, [
                'status'        => 'failed',
                'error_message' => $result['error'],
                'finished_at'   => date('Y-m-d H:i:s'),
            ]);
            $this->logs->add([
                'level'   => 'error',
                'message' => "Échec de la sauvegarde pour « {$connection['name']} » : {$result['error']}",
            ]);
        }

        $this->json($this->backups->getById($backupId), $result['success'] ? 201 : 500);
    }

    private function runDump(array $connection): array
    {
        $password = Crypto::decrypt($connection['password_enc']);
        $filename = sprintf('%s_%s.sql', $connection['db_name'], date('Ymd_His'));
        $filepath = $this->storagePath . '/' . $filename;

        putenv('MYSQL_PWD=' . $password);
        putenv('PGPASSWORD=' . $password);

        if ($connection['engine'] === 'mysql') {
            $cmd = sprintf(
                'mysqldump --host=%s --port=%d --user=%s %s > %s 2>&1',
                escapeshellarg($connection['host']),
                (int) $connection['port'],
                escapeshellarg($connection['username']),
                escapeshellarg($connection['db_name']),
                escapeshellarg($filepath)
            );
        } else {
            $cmd = sprintf(
                'pg_dump --host=%s --port=%d --username=%s --dbname=%s --file=%s 2>&1',
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

        if ($exitCode !== 0 || !file_exists($filepath)) {
            return [
                'success' => false,
                'error'   => trim(implode("\n", $output)) ?: 'La commande de sauvegarde a échoué.',
            ];
        }

        return ['success' => true, 'file' => $filepath];
    }
}
