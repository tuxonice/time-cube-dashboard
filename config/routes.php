<?php

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\GuestMiddleware;
use App\Core\Middleware\ApiTokenMiddleware;

/** @var \App\Core\Router $router */

// Auth routes (guest only)
$router->get('/login', 'AuthController', 'loginForm', [GuestMiddleware::class]);
$router->post('/login', 'AuthController', 'login', [GuestMiddleware::class]);
$router->get('/register', 'AuthController', 'registerForm', [GuestMiddleware::class]);
$router->post('/register', 'AuthController', 'register', [GuestMiddleware::class]);
$router->post('/logout', 'AuthController', 'logout', [AuthMiddleware::class]);

// Profile (requires auth)
$router->get('/profile', 'ProfileController', 'show', [AuthMiddleware::class]);
$router->post('/profile', 'ProfileController', 'update', [AuthMiddleware::class]);
$router->post('/profile/avatar', 'ProfileController', 'uploadAvatar', [AuthMiddleware::class]);
$router->post('/profile/avatar/remove', 'ProfileController', 'removeAvatar', [AuthMiddleware::class]);

// Dashboard (requires auth)
$router->get('/', 'DashboardController', 'index', [AuthMiddleware::class]);

// Projects (requires auth)
$router->get('/projects', 'ProjectController', 'index', [AuthMiddleware::class]);
$router->get('/projects/create', 'ProjectController', 'create', [AuthMiddleware::class]);
$router->post('/projects', 'ProjectController', 'store', [AuthMiddleware::class]);
$router->get('/projects/{id}/edit', 'ProjectController', 'edit', [AuthMiddleware::class]);
$router->post('/projects/{id}', 'ProjectController', 'update', [AuthMiddleware::class]);
$router->post('/projects/{id}/delete', 'ProjectController', 'delete', [AuthMiddleware::class]);

// Tasks (requires auth, scoped to project)
$router->get('/projects/{projectId}/tasks', 'TaskController', 'index', [AuthMiddleware::class]);
$router->get('/projects/{projectId}/tasks/create', 'TaskController', 'create', [AuthMiddleware::class]);
$router->post('/projects/{projectId}/tasks', 'TaskController', 'store', [AuthMiddleware::class]);
$router->get('/tasks/{id}/edit', 'TaskController', 'edit', [AuthMiddleware::class]);
$router->post('/tasks/{id}', 'TaskController', 'update', [AuthMiddleware::class]);
$router->post('/tasks/{id}/delete', 'TaskController', 'delete', [AuthMiddleware::class]);

// Cubes (requires auth)
$router->get('/cubes', 'CubeConfigController', 'index', [AuthMiddleware::class]);
$router->post('/cubes', 'CubeConfigController', 'createCube', [AuthMiddleware::class]);
$router->get('/cubes/{id}', 'CubeConfigController', 'edit', [AuthMiddleware::class]);
$router->post('/cubes/{id}/delete', 'CubeConfigController', 'deleteCube', [AuthMiddleware::class]);
$router->post('/cubes/{id}/mappings', 'CubeConfigController', 'addMapping', [AuthMiddleware::class]);
$router->post('/cubes/{id}/mappings/{mappingId}/delete', 'CubeConfigController', 'deleteMapping', [AuthMiddleware::class]);

// Settings (requires auth - API tokens)
$router->get('/settings', 'SettingsController', 'index', [AuthMiddleware::class]);
$router->post('/settings/tokens', 'SettingsController', 'createToken', [AuthMiddleware::class]);
$router->post('/settings/tokens/{id}/delete', 'SettingsController', 'deleteToken', [AuthMiddleware::class]);

// API (requires API token)
$router->post('/api/cube', 'ApiController', 'cube', [ApiTokenMiddleware::class]);

// Time Entries (requires auth)
$router->get('/tasks/{taskId}/time', 'TimeEntryController', 'index', [AuthMiddleware::class]);
$router->post('/tasks/{taskId}/time/start', 'TimeEntryController', 'start', [AuthMiddleware::class]);
$router->post('/time/{id}/stop', 'TimeEntryController', 'stop', [AuthMiddleware::class]);
$router->get('/tasks/{taskId}/time/create', 'TimeEntryController', 'create', [AuthMiddleware::class]);
$router->post('/tasks/{taskId}/time', 'TimeEntryController', 'store', [AuthMiddleware::class]);
$router->post('/time/{id}/delete', 'TimeEntryController', 'delete', [AuthMiddleware::class]);
