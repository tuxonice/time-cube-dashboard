<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\TimeEntry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TimeEntryController extends Controller
{
    private function getTaskForUser(string $taskId): array|RedirectResponse
    {
        $task = Task::findWithProject((int) $taskId);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Task not found.');
            return $this->redirect('/projects');
        }
        return $task;
    }

    private function getEntryForUser(string $id): array|RedirectResponse
    {
        $entry = TimeEntry::find((int) $id);
        if (!$entry || $entry['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Time entry not found.');
            return $this->redirect('/projects');
        }
        return $entry;
    }

    public function index(string $taskId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTaskForUser($taskId);
        if ($task instanceof RedirectResponse) {
            return $task;
        }
        $entries = TimeEntry::allForTask((int) $taskId);
        $running = TimeEntry::runningForTask((int) $taskId);
        $total = TimeEntry::totalForTask((int) $taskId);

        return $this->render('time_entries/index.twig', [
            'task' => $task,
            'entries' => $entries,
            'running' => $running,
            'total_seconds' => $total,
        ]);
    }

    public function start(string $taskId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTaskForUser($taskId);
        if ($task instanceof RedirectResponse) {
            return $task;
        }

        // Stop any currently running timer for this user
        $running = TimeEntry::runningForUser(Auth::userId());
        if ($running) {
            TimeEntry::stop($running['id']);
        }

        TimeEntry::start((int) $taskId, Auth::userId());
        $this->flash('success', 'Timer started.');
        return $this->redirect("/tasks/{$taskId}/time");
    }

    public function stop(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $entry = $this->getEntryForUser($id);
        if ($entry instanceof RedirectResponse) {
            return $entry;
        }

        TimeEntry::stop((int) $id);
        $this->flash('success', 'Timer stopped.');
        return $this->redirect("/tasks/{$entry['task_id']}/time");
    }

    public function create(string $taskId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTaskForUser($taskId);
        if ($task instanceof RedirectResponse) {
            return $task;
        }
        return $this->render('time_entries/create.twig', ['task' => $task]);
    }

    public function store(string $taskId): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $task = $this->getTaskForUser($taskId);
        if ($task instanceof RedirectResponse) {
            return $task;
        }

        $hours = (int) $this->post('hours', 0);
        $minutes = (int) $this->post('minutes', 0);
        $description = trim($this->post('description', ''));
        $duration = ($hours * 3600) + ($minutes * 60);

        if ($duration <= 0) {
            $this->flash('error', 'Duration must be greater than zero.');
            return $this->redirect("/tasks/{$taskId}/time/create");
        }

        TimeEntry::createManual((int) $taskId, Auth::userId(), $duration, $description ?: null);
        $this->flash('success', 'Time entry added.');
        return $this->redirect("/tasks/{$taskId}/time");
    }

    public function delete(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $entry = $this->getEntryForUser($id);
        if ($entry instanceof RedirectResponse) {
            return $entry;
        }

        TimeEntry::delete((int) $id);
        $this->flash('success', 'Time entry deleted.');
        return $this->redirect("/tasks/{$entry['task_id']}/time");
    }
}
