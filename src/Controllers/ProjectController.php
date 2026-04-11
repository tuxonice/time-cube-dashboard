<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $projects = Project::allForUser(Auth::userId());
        $this->render('projects/index.twig', ['projects' => $projects]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->render('projects/create.twig');
    }

    public function store(): void
    {
        $this->requireAuth();
        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Project name is required.');
            $this->redirect('/projects/create');
        }

        Project::create(Auth::userId(), $name, $description ?: null);
        $this->flash('success', 'Project created.');
        $this->redirect('/projects');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            $this->redirect('/projects');
        }

        $this->render('projects/edit.twig', ['project' => $project]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            $this->redirect('/projects');
        }

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Project name is required.');
            $this->redirect("/projects/{$id}/edit");
        }

        Project::update((int) $id, $name, $description ?: null);
        $this->flash('success', 'Project updated.');
        $this->redirect('/projects');
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            $this->redirect('/projects');
        }

        Project::delete((int) $id);
        $this->flash('success', 'Project deleted.');
        $this->redirect('/projects');
    }
}
