<?php

/** @var \App\Core\Router $router */

// Auth
$router->get('/login', 'AuthController', 'loginForm');
$router->post('/login', 'AuthController', 'login');
$router->get('/register', 'AuthController', 'registerForm');
$router->post('/register', 'AuthController', 'register');
$router->post('/logout', 'AuthController', 'logout');

// Profile
$router->get('/profile', 'ProfileController', 'show');
$router->post('/profile', 'ProfileController', 'update');
$router->post('/profile/avatar', 'ProfileController', 'uploadAvatar');
$router->post('/profile/avatar/remove', 'ProfileController', 'removeAvatar');

// Dashboard
$router->get('/', 'DashboardController', 'index');

// Projects
$router->get('/projects', 'ProjectController', 'index');
$router->get('/projects/create', 'ProjectController', 'create');
$router->post('/projects', 'ProjectController', 'store');
$router->get('/projects/{id}/edit', 'ProjectController', 'edit');
$router->post('/projects/{id}', 'ProjectController', 'update');
$router->post('/projects/{id}/delete', 'ProjectController', 'delete');

// Tasks (scoped to project)
$router->get('/projects/{projectId}/tasks', 'TaskController', 'index');
$router->get('/projects/{projectId}/tasks/create', 'TaskController', 'create');
$router->post('/projects/{projectId}/tasks', 'TaskController', 'store');
$router->get('/tasks/{id}/edit', 'TaskController', 'edit');
$router->post('/tasks/{id}', 'TaskController', 'update');
$router->post('/tasks/{id}/delete', 'TaskController', 'delete');

// Cubes
$router->get('/cubes', 'CubeConfigController', 'index');
$router->post('/cubes', 'CubeConfigController', 'createCube');
$router->get('/cubes/{id}', 'CubeConfigController', 'edit');
$router->post('/cubes/{id}/delete', 'CubeConfigController', 'deleteCube');
$router->post('/cubes/{id}/mappings', 'CubeConfigController', 'addMapping');
$router->post('/cubes/{id}/mappings/{mappingId}/delete', 'CubeConfigController', 'deleteMapping');

// Settings (API tokens)
$router->get('/settings', 'SettingsController', 'index');
$router->post('/settings/tokens', 'SettingsController', 'createToken');
$router->post('/settings/tokens/{id}/delete', 'SettingsController', 'deleteToken');

// API (single endpoint for ESP32 Time Cube device)
$router->post('/api/cube', 'ApiController', 'cube');

// Time Entries
$router->get('/tasks/{taskId}/time', 'TimeEntryController', 'index');
$router->post('/tasks/{taskId}/time/start', 'TimeEntryController', 'start');
$router->post('/time/{id}/stop', 'TimeEntryController', 'stop');
$router->get('/tasks/{taskId}/time/create', 'TimeEntryController', 'create');
$router->post('/tasks/{taskId}/time', 'TimeEntryController', 'store');
$router->post('/time/{id}/delete', 'TimeEntryController', 'delete');
