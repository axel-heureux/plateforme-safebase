<?php

require_once __DIR__ . '/BaseModel.php';

class BackupModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('backups', 'id');
    }

    public function getAll(array $conditions = [], array $orderBy = ['created_at DESC'], $limit = null): array
    {
        return $this->findAll($conditions, $orderBy, $limit);
    }

    public function getById($id): ?array
    {
        return $this->findById($id);
    }

    public function getOne(array $conditions = [], array $orderBy = ['created_at DESC']): ?array
    {
        return $this->findOne($conditions, $orderBy);
    }

    public function add(array $data): int
    {
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
}
