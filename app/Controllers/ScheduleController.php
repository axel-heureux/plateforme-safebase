<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ScheduleModel.php';
require_once __DIR__ . '/../Models/DbConnectionModel.php';
require_once __DIR__ . '/../Models/LogModel.php';

class ScheduleController extends BaseController
{
    private ScheduleModel $schedules;
    private DbConnectionModel $connections;
    private LogModel $logs;

    public function __construct()
    {
        $this->schedules = new ScheduleModel();
        $this->connections = new DbConnectionModel();
        $this->logs = new LogModel();
    }

    // GET /api/schedules
    public function index(): void
    {
        $this->json($this->schedules->getAll());
    }

    // POST /api/schedules   body: { connection_id, cron_expression }
    public function store(): void
    {
        $data = $this->input();
        $connectionId = $data['connection_id'] ?? null;
        $cron = $data['cron_expression'] ?? null;

        if (!$connectionId || !$cron) {
            $this->error('connection_id et cron_expression sont requis.');
        }

        $connection = $this->connections->getById($connectionId);
        if (!$connection) {
            $this->error('Connexion introuvable.', 404);
        }

        $id = $this->schedules->add([
            'connection_id'   => $connectionId,
            'cron_expression' => $cron,
            'is_active'       => 1,
        ]);

        $this->logs->add([
            'level'   => 'info',
            'message' => "Planification créée pour « {$connection['name']} ».",
        ]);

        $this->json($this->schedules->getById($id), 201);
    }

    // PATCH /api/schedules/{id}   body: { is_active? , cron_expression? }
    public function update($id): void
    {
        $schedule = $this->schedules->getById($id);
        if (!$schedule) {
            $this->error('Planification introuvable.', 404);
        }

        $data = $this->input();
        $fields = [];

        if (array_key_exists('is_active', $data)) {
            $fields['is_active'] = $data['is_active'] ? 1 : 0;
        }
        if (!empty($data['cron_expression'])) {
            $fields['cron_expression'] = $data['cron_expression'];
        }

        if (!empty($fields)) {
            $this->schedules->edit($id, $fields);
        }

        $connection = $this->connections->getById($schedule['connection_id']);
        if (array_key_exists('is_active', $fields)) {
            $state = $fields['is_active'] ? 'activée' : 'désactivée';
            $this->logs->add([
                'level'   => 'info',
                'message' => "Planification {$state} pour « " . ($connection['name'] ?? '?') . " ».",
            ]);
        }

        $this->json($this->schedules->getById($id));
    }

    // DELETE /api/schedules/{id}
    public function destroy($id): void
    {
        $schedule = $this->schedules->getById($id);
        if (!$schedule) {
            $this->error('Planification introuvable.', 404);
        }

        $this->schedules->remove($id);

        $this->logs->add([
            'level'   => 'warning',
            'message' => 'Planification supprimée.',
        ]);

        $this->json(['deleted' => true]);
    }
}
