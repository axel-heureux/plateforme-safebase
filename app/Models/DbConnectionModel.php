<?php

require_once __DIR__ . '/BaseModel.php';

class DbConnectionModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('db_connections', 'id');
        $this->ensureSessionColumn();
    }

    public function getAll(array $conditions = [], array $orderBy = ['created_at DESC'], $limit = null): array
    {
        return $this->findAll($conditions, $orderBy, $limit);
    }

    public function getById($id): ?array
    {
        return $this->findById($id);
    }

    public function getAllForSession(string $sessionKey): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . $this->table . ' WHERE session_id = :session_id ORDER BY created_at DESC'
        );
        $stmt->execute(['session_id' => $sessionKey]);

        return $stmt->fetchAll();
    }

    public function getByIdForSession($id, string $sessionKey): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . $this->table . ' WHERE id = :id AND session_id = :session_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'session_id' => $sessionKey]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getOne(array $conditions = [], array $orderBy = ['created_at DESC']): ?array
    {
        return $this->findOne($conditions, $orderBy);
    }

    public function add(array $data): int
    {
        return $this->create($data);
    }

    public function addForSession(array $data, string $sessionKey): int
    {
        $data['session_id'] = $sessionKey;
        return $this->create($data);
    }

    public function edit($id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function remove($id): bool
    {
        return $this->delete($id);
    }

    public function removeForSession($id, string $sessionKey): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM ' . $this->table . ' WHERE id = :id AND session_id = :session_id'
        );

        return $stmt->execute(['id' => $id, 'session_id' => $sessionKey]);
    }

    private function ensureSessionColumn(): void
    {
        if (!$this->hasSessionColumn()) {
            try {
                $this->db->exec("ALTER TABLE {$this->table} ADD session_id CHAR(64) NULL");
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 1060 || !$this->hasSessionColumn()) {
                    throw $exception;
                }
            }
        }

        if (!$this->hasSessionIndex()) {
            try {
                $this->db->exec(
                    "ALTER TABLE {$this->table} ADD INDEX idx_db_connections_session_id (session_id)"
                );
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 1061 || !$this->hasSessionIndex()) {
                    throw $exception;
                }
            }
        }
    }

    private function hasSessionColumn(): bool
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'session_id'");
        return (bool) $stmt->fetch();
    }

    private function hasSessionIndex(): bool
    {
        $stmt = $this->db->query(
            "SHOW INDEX FROM {$this->table} WHERE Key_name = 'idx_db_connections_session_id'"
        );
        return (bool) $stmt->fetch();
    }
}
