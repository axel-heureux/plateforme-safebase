<?php

require_once __DIR__ . '/../../Core/Database.php';

class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct($table, $primaryKey = 'id')
    {
        $this->db = Database::getInstance()->getConnection();
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    public function findAll(array $conditions = [], array $orderBy = [], $limit = null): array
    {
        $sql = 'SELECT * FROM ' . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById($id): ?array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findOne(array $conditions = [], array $orderBy = []): ?array
    {
        $sql = 'SELECT * FROM ' . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Les données de création sont vides.');
        }

        $fields = array_keys($data);
        $placeholders = [];

        foreach ($fields as $field) {
            $placeholders[] = ':' . $field;
        }

        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update($id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $parts = [];
        $params = [];

        foreach ($data as $field => $value) {
            $parts[] = $field . ' = :' . $field;
            $params[$field] = $value;
        }

        $params['id'] = $id;

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $parts) . ' WHERE ' . $this->primaryKey . ' = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete($id): bool
    {
        $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    public function deleteWhere(array $conditions): bool
    {
        if (empty($conditions)) {
            return false;
        }

        $params = [];
        $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->buildWhereClause($conditions, $params);
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function count(array $conditions = []): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM ' . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . $this->buildWhereClause($conditions, $params);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    protected function buildWhereClause(array $conditions, array &$params): string
    {
        $clauses = [];

        foreach ($conditions as $field => $value) {
            $key = ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $field) . '_' . count($params);
            $clauses[] = $field . ' = ' . $key;
            $params[$key] = $value;
        }

        return implode(' AND ', $clauses);
    }
}
