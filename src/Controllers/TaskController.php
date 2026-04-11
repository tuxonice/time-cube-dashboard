<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Project;
use App\Models\Task;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TaskController extends Controller
{
    private function getProject(string $projectId): array|RedirectResponse
    {
        $project = Project::find((int) $projectId);
        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            return $this->redirect('/projects');
        }
        return $project;
    }

    private function getTask(string $id): array|RedirectResponse
    {
        $task = Task::findWithProject((int) $id);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Task not found.');
            return $this->redirect('/projects');
        }
        return $task;
    }

    public function index(string $projectId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $project = $this->getProject($projectId);
        if ($project instanceof RedirectResponse) {
            return $project;
        }
        $tasks = Task::allForProject((int) $projectId);
        return $this->render('tasks/index.twig', ['project' => $project, 'tasks' => $tasks]);
    }

    public function create(string $projectId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $project = $this->getProject($projectId);
        if ($project instanceof RedirectResponse) {
            return $project;
        }
        return $this->render('tasks/create.twig', ['project' => $project]);
    }

    public function store(string $projectId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $project = $this->getProject($projectId);
        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Task name is required.');
            return $this->redirect("/projects/{$projectId}/tasks/create");
        }

        Task::create((int) $projectId, $name, $description ?: null);
        $this->flash('success', 'Task created.');
        return $this->redirect("/projects/{$projectId}/tasks");
    }

    public function edit(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTask($id);
        if ($task instanceof RedirectResponse) {
            return $task;
        }
        return $this->render('tasks/edit.twig', ['task' => $task]);
    }

    public function update(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTask($id);
        if ($task instanceof RedirectResponse) {
            return $task;
        }

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Task name is required.');
            return $this->redirect("/tasks/{$id}/edit");
        }

        Task::update((int) $id, $name, $description ?: null);
        $this->flash('success', 'Task updated.');
        return $this->redirect("/projects/{$task['project_id']}/tasks");
    }

    public function delete(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTask($id);
        if ($task instanceof RedirectResponse) {
            return $task;
        }

        Task::delete((int) $id);
        $this->flash('success', 'Task deleted.');
        return $this->redirect("/projects/{$task['project_id']}/tasks");
    }
}
