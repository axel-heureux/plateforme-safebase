<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/DbConnectionModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../../Core/Crypto.php';

class DbConnectionController extends BaseController
{
    private DbConnectionModel $connections;
    private LogModel $logs;

    public function __construct()
    {
        $this->connections = new DbConnectionModel();
        $this->logs = new LogModel();
    }

    // GET /api/connections
    public function index(): void
    {
        $rows = $this->connections->getAll();
        foreach ($rows as &$row) {
            unset($row['password_enc']);
        }
        $this->json($rows);
    }

    // POST /api/connections
    public function store(): void
    {
        $data = $this->input();

        foreach (['name', 'engine', 'host', 'port', 'db_name', 'username'] as $field) {
            if (empty($data[$field])) {
                $this->error("Le champ « {$field} » est requis.");
            }
        }

        if (!in_array($data['engine'], ['mysql', 'postgresql'], true)) {
            $this->error('Moteur invalide (mysql ou postgresql attendu).');
        }

        $id = $this->connections->add([
            'name'         => $data['name'],
            'engine'       => $data['engine'],
            'host'         => $data['host'],
            'port'         => (int) $data['port'],
            'db_name'      => $data['db_name'],
            'username'     => $data['username'],
            'password_enc' => Crypto::encrypt($data['password'] ?? ''),
        ]);

        $this->logs->add([
            'level'   => 'info',
            'message' => "Connexion « {$data['name']} » ajoutée ({$data['engine']}).",
        ]);

        $connection = $this->connections->getById($id);
        unset($connection['password_enc']);

        $this->json($connection, 201);
    }

    // DELETE /api/connections/{id}
    public function destroy($id): void
    {
        $connection = $this->connections->getById($id);
        if (!$connection) {
            $this->error('Connexion introuvable.', 404);
        }

        $this->connections->remove($id);

        $this->logs->add([
            'level'   => 'warning',
            'message' => "Connexion « {$connection['name']} » supprimée.",
        ]);

        $this->json(['deleted' => true]);
    }

    // GET /api/connections/{id}/test
    public function test($id): void
    {
        $connection = $this->connections->getById($id);
        if (!$connection) {
            $this->error('Connexion introuvable.', 404);
        }

        try {
            $password = Crypto::decrypt($connection['password_enc']);
            $driver = $connection['engine'] === 'mysql' ? 'mysql' : 'pgsql';
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $driver,
                $connection['host'],
                (int) $connection['port'],
                $connection['db_name']
            );

            new PDO($dsn, $connection['username'], $password, [PDO::ATTR_TIMEOUT => 3]);

            $this->json(['status' => 'ok']);
        } catch (PDOException $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
