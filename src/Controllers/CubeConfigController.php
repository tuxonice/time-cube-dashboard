<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CubeConfig;
use Symfony\Component\HttpFoundation\Response;

class CubeConfigController extends Controller
{
    public function index(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $cubes = CubeConfig::allCubesForUser(Auth::userId());
        return $this->render('cubes/index.twig', ['cubes' => $cubes]);
    }

    public function createCube(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $cubeId = trim($this->post('cube_id', ''));
        $name = trim($this->post('name', ''));

        if ($cubeId === '' || $name === '') {
            $this->flash('error', 'Cube ID and name are required.');
            return $this->redirect('/cubes');
        }

        if (CubeConfig::findCubeByIdentifier($cubeId)) {
            $this->flash('error', 'A cube with this ID already exists.');
            return $this->redirect('/cubes');
        }

        CubeConfig::createCube(Auth::userId(), $cubeId, $name);
        $this->flash('success', 'Cube registered.');
        return $this->redirect('/cubes');
    }

    public function deleteCube(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            return $this->redirect('/cubes');
        }

        CubeConfig::deleteCube((int) $id);
        $this->flash('success', 'Cube deleted.');
        return $this->redirect('/cubes');
    }

    public function edit(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            return $this->redirect('/cubes');
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

        return $this->render('cubes/edit.twig', [
            'cube' => $cube,
            'mappings' => $mappings,
            'tasks' => $tasks,
        ]);
    }

    public function addMapping(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            return $this->redirect('/cubes');
        }

        $faceColor = trim($this->post('face_color', ''));
        $taskId = $this->post('task_id');

        if ($faceColor === '' || !$taskId) {
            $this->flash('error', 'Face color and task are required.');
            return $this->redirect("/cubes/{$id}");
        }

        CubeConfig::saveMapping((int) $id, $faceColor, (int) $taskId);
        $this->flash('success', "Face \"{$faceColor}\" mapped.");
        return $this->redirect("/cubes/{$id}");
    }

    public function deleteMapping(string $id, string $mappingId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $cube = CubeConfig::findCube((int) $id);

        if (!$cube || $cube['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Cube not found.');
            return $this->redirect('/cubes');
        }

        CubeConfig::deleteMapping((int) $mappingId, $cube['id']);
        $this->flash('success', 'Mapping removed.');
        return $this->redirect("/cubes/{$id}");
    }
}
