<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Project;
use App\Models\Task;

class TaskController extends Controller
{
    private function getProject(string $projectId): array
    {
        $project = Project::find((int) $projectId);
        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            $this->redirect('/projects');
        }
        return $project;
    }

    private function getTask(string $id): array
    {
        $task = Task::findWithProject((int) $id);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Task not found.');
            $this->redirect('/projects');
        }
        return $task;
    }

    public function index(string $projectId): void
    {
        $this->requireAuth();
        $project = $this->getProject($projectId);
        $tasks = Task::allForProject((int) $projectId);
        $this->render('tasks/index.twig', ['project' => $project, 'tasks' => $tasks]);
    }

    public function create(string $projectId): void
    {
        $this->requireAuth();
        $project = $this->getProject($projectId);
        $this->render('tasks/create.twig', ['project' => $project]);
    }

    public function store(string $projectId): void
    {
        $this->requireAuth();
        $project = $this->getProject($projectId);

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Task name is required.');
            $this->redirect("/projects/{$projectId}/tasks/create");
        }

        Task::create((int) $projectId, $name, $description ?: null);
        $this->flash('success', 'Task created.');
        $this->redirect("/projects/{$projectId}/tasks");
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $task = $this->getTask($id);
        $this->render('tasks/edit.twig', ['task' => $task]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $task = $this->getTask($id);

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Task name is required.');
            $this->redirect("/tasks/{$id}/edit");
        }

        Task::update((int) $id, $name, $description ?: null);
        $this->flash('success', 'Task updated.');
        $this->redirect("/projects/{$task['project_id']}/tasks");
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $task = $this->getTask($id);

        Task::delete((int) $id);
        $this->flash('success', 'Task deleted.');
        $this->redirect("/projects/{$task['project_id']}/tasks");
    }
}
