<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CubeConfig;

class CubeConfigController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $cubes = CubeConfig::allCubesForUser(Auth::userId());
        $this->render('cubes/index.twig', ['cubes' => $cubes]);
    }

    public function createCube(): void
    {
        $this->requireAuth();
        $cubeId = trim($this->post('cube_id', ''));
        $name = trim($this->post('name', ''));

        if ($cubeId === '' || $name === '') {
            $this->flash('error', 'Cube ID and name are required.');
            $this->redirect('/cubes');
        }

        if (CubeConfig::findCubeByIdentifier($cubeId)) {
            $this->flash('error', 'A cube with this ID already exists.');
            $this->redirect('/cubes');
        }

        CubeConfig::createCube(Auth::userId(), $cubeId, $name);
        $this->flash('success', 'Cube registered.');
        $this->redirect('/cubes');
    }

    public function deleteCube(string $id): void
    {
        $this->requireAuth();
        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            $this->redirect('/cubes');
        }

        CubeConfig::deleteCube((int) $id);
        $this->flash('success', 'Cube deleted.');
        $this->redirect('/cubes');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            $this->redirect('/cubes');
        }

        $mappings = CubeConfig::mappingsForCube((int) $id);

        // All tasks for this user (across all projects)
        $tasks = Database::getInstance()->fetchAll(
            'SELECT t.id, t.name, p.name as project_name
             FROM tasks t
             JOIN projects p ON t.project_id = p.id
             WHERE p.user_id = ?
             ORDER BY p.name, t.name',
            [Auth::userId()]
        );

        $this->render('cubes/edit.twig', [
            'cube' => $cube,
            'mappings' => $mappings,
            'tasks' => $tasks,
        ]);
    }

    public function addMapping(string $id): void
    {
        $this->requireAuth();
        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            $this->redirect('/cubes');
        }

        $faceColor = trim($this->post('face_color', ''));
        $taskId = $this->post('task_id');

        if ($faceColor === '' || !$taskId) {
            $this->flash('error', 'Face color and task are required.');
            $this->redirect("/cubes/{$id}");
        }

        CubeConfig::saveMapping((int) $id, $faceColor, (int) $taskId);
        $this->flash('success', "Face \"{$faceColor}\" mapped.");
        $this->redirect("/cubes/{$id}");
    }

    public function deleteMapping(string $id, string $mappingId): void
    {
        $this->requireAuth();
        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            $this->redirect('/cubes');
        }

        CubeConfig::deleteMapping((int) $mappingId);
        $this->flash('success', 'Mapping removed.');
        $this->redirect("/cubes/{$id}");
    }
}
