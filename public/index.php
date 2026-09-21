<?php

require_once __DIR__ . '/../Core/Router.php';

$router = new Router();

// Vue principale (dashboard HTML)
$router->get('/', ['DashboardController', 'index']);

// Connexions
$router->get('/api/connections', ['DbConnectionController', 'index']);
$router->post('/api/connections', ['DbConnectionController', 'store']);
$router->delete('/api/connections/{id}', ['DbConnectionController', 'destroy']);
$router->get('/api/connections/{id}/test', ['DbConnectionController', 'test']);

// Sauvegardes
$router->get('/api/backups', ['BackupController', 'index']);
$router->post('/api/backups', ['BackupController', 'store']);

// Restaurations
$router->get('/api/restores', ['RestoreController', 'index']);
$router->post('/api/restores', ['RestoreController', 'store']);

// Planifications
$router->get('/api/schedules', ['ScheduleController', 'index']);
$router->post('/api/schedules', ['ScheduleController', 'store']);
$router->patch('/api/schedules/{id}', ['ScheduleController', 'update']);
$router->delete('/api/schedules/{id}', ['ScheduleController', 'destroy']);

// Logs
$router->get('/api/logs', ['LogController', 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
