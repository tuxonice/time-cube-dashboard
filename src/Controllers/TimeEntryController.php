<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\TimeEntry;

class TimeEntryController extends Controller
{
    private function getTaskForUser(string $taskId): array
    {
        $task = Task::findWithProject((int) $taskId);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Task not found.');
            $this->redirect('/projects');
        }
        return $task;
    }

    private function getEntryForUser(string $id): array
    {
        $entry = TimeEntry::find((int) $id);
        if (!$entry || $entry['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Time entry not found.');
            $this->redirect('/projects');
        }
        return $entry;
    }

    public function index(string $taskId): void
    {
        $this->requireAuth();
        $task = $this->getTaskForUser($taskId);
        $entries = TimeEntry::allForTask((int) $taskId);
        $running = TimeEntry::runningForTask((int) $taskId);
        $total = TimeEntry::totalForTask((int) $taskId);

        $this->render('time_entries/index.twig', [
            'task' => $task,
            'entries' => $entries,
            'running' => $running,
            'total_seconds' => $total,
        ]);
    }

    public function start(string $taskId): void
    {
        $this->requireAuth();
        $task = $this->getTaskForUser($taskId);

        // Stop any currently running timer for this user
        $running = TimeEntry::runningForUser(Auth::userId());
        if ($running) {
            TimeEntry::stop($running['id']);
        }

        TimeEntry::start((int) $taskId, Auth::userId());
        $this->flash('success', 'Timer started.');
        $this->redirect("/tasks/{$taskId}/time");
    }

    public function stop(string $id): void
    {
        $this->requireAuth();
        $entry = $this->getEntryForUser($id);

        TimeEntry::stop((int) $id);
        $this->flash('success', 'Timer stopped.');
        $this->redirect("/tasks/{$entry['task_id']}/time");
    }

    public function create(string $taskId): void
    {
        $this->requireAuth();
        $task = $this->getTaskForUser($taskId);
        $this->render('time_entries/create.twig', ['task' => $task]);
    }

    public function store(string $taskId): void
    {
        $this->requireAuth();
        $task = $this->getTaskForUser($taskId);

        $hours = (int) $this->post('hours', 0);
        $minutes = (int) $this->post('minutes', 0);
        $description = trim($this->post('description', ''));
        $duration = ($hours * 3600) + ($minutes * 60);

        if ($duration <= 0) {
            $this->flash('error', 'Duration must be greater than zero.');
            $this->redirect("/tasks/{$taskId}/time/create");
        }

        TimeEntry::createManual((int) $taskId, Auth::userId(), $duration, $description ?: null);
        $this->flash('success', 'Time entry added.');
        $this->redirect("/tasks/{$taskId}/time");
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $entry = $this->getEntryForUser($id);

        TimeEntry::delete((int) $id);
        $this->flash('success', 'Time entry deleted.');
        $this->redirect("/tasks/{$entry['task_id']}/time");
    }
}
