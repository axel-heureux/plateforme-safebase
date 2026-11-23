<?php

require_once __DIR__ . '/../app/Models/ScheduleModel.php';
require_once __DIR__ . '/../app/Models/DbConnectionModel.php';
require_once __DIR__ . '/../app/Models/BackupModel.php';
require_once __DIR__ . '/../app/Models/LogModel.php';
require_once __DIR__ . '/../Core/Crypto.php';
require_once __DIR__ . '/../Core/ConnectionHost.php';

$storagePath = __DIR__ . '/../storage/backups';
$intervalSeconds = 60;

function cronMatches(string $expression, DateTimeInterface $now): bool
{
    $parts = preg_split('/\s+/', trim($expression));
    if (!$parts || count($parts) !== 5) {
        return false;
    }

    [$minute, $hour, $day, $month, $weekday] = $parts;
    $values = [
        (int) $now->format('i'),
        (int) $now->format('G'),
        (int) $now->format('j'),
        (int) $now->format('n'),
        (int) $now->format('w'),
    ];

    foreach ([$minute, $hour, $day, $month, $weekday] as $index => $field) {
        if ($field === '*') {
            continue;
        }

        if (substr($field, 0, 2) === '*/') {
            $step = (int) substr($field, 2);
            if ($step > 0 && $values[$index] % $step === 0) {
                continue;
            }
            return false;
        }

        $allowed = array_map('intval', explode(',', $field));
        if (!in_array($values[$index], $allowed, true)) {
            return false;
        }
    }

    return true;
}

while (true) {
    $schedules = (new ScheduleModel())->getAll(['is_active' => 1]);
    $connections = new DbConnectionModel();
    $backups = new BackupModel();
    $logs = new LogModel();

    foreach ($schedules as $schedule) {
        if (!cronMatches($schedule['cron_expression'], new DateTimeImmutable())) {
            continue;
        }
        $connection = $connections->getById($schedule['connection_id']);
        if (!$connection) {
            continue;
        }

        $databaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $connection['db_name']);
        $filename = sprintf('%s_auto_%s.sql', $databaseName, date('Ymd_His'));
        $filepath = $storagePath . '/' . $filename;
        $errorPath = $filepath . '.err';
        $password = Crypto::decrypt($connection['password_enc']);

        putenv('MYSQL_PWD=' . $password);
        putenv('PGPASSWORD=' . $password);

        if ($connection['engine'] === 'mysql') {
            $cmd = sprintf(
                'mysqldump --host=%s --port=%d --user=%s %s > %s 2> %s',
                escapeshellarg(ConnectionHost::resolve($connection['host'])),
                (int) $connection['port'],
                escapeshellarg($connection['username']),
                escapeshellarg($connection['db_name']),
                escapeshellarg($filepath),
                escapeshellarg($errorPath)
            );
        } else {
            $cmd = sprintf(
                'pg_dump --host=%s --port=%d --username=%s --dbname=%s --file=%s 2> %s',
                escapeshellarg(ConnectionHost::resolve($connection['host'])),
                (int) $connection['port'],
                escapeshellarg($connection['username']),
                escapeshellarg($connection['db_name']),
                escapeshellarg($filepath),
                escapeshellarg($errorPath)
            );
        }

        exec($cmd, $output, $exitCode);
        putenv('MYSQL_PWD');
        putenv('PGPASSWORD');

        $error = is_file($errorPath) ? trim((string) file_get_contents($errorPath)) : '';
        if (is_file($errorPath)) {
            unlink($errorPath);
        }
        $success = $exitCode === 0 && is_file($filepath) && filesize($filepath) > 0;
        if (!$success && is_file($filepath)) {
            unlink($filepath);
        }

        $backupId = $backups->add([
            'connection_id' => $connection['id'],
            'file_path' => $success ? $filepath : '',
            'file_size' => $success ? filesize($filepath) : 0,
            'type' => 'auto',
            'status' => $success ? 'success' : 'failed',
            'error_message' => $success ? null : ($error ?: trim(implode("\n", $output))),
        ]);

        $logs->add([
            'level' => $success ? 'info' : 'error',
            'message' => $success
                ? "Sauvegarde automatique réussie pour « {$connection['name']} »."
                : "Sauvegarde automatique échouée pour « {$connection['name']} ».",
        ]);
    }

    sleep($intervalSeconds);
}
