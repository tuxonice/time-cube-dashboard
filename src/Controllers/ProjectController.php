<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $projects = Project::allForUser(Auth::userId());
        return $this->render('projects/index.twig', ['projects' => $projects]);
    }

    public function create(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        return $this->render('projects/create.twig');
    }

    public function store(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Project name is required.');
            return $this->redirect('/projects/create');
        }

        Project::create(Auth::userId(), $name, $description ?: null);
        $this->flash('success', 'Project created.');
        return $this->redirect('/projects');
    }

    public function edit(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            return $this->redirect('/projects');
        }

        return $this->render('projects/edit.twig', ['project' => $project]);
    }

    public function update(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            return $this->redirect('/projects');
        }

        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));

        if ($name === '') {
            $this->flash('error', 'Project name is required.');
            return $this->redirect("/projects/{$id}/edit");
        }

        Project::update((int) $id, $name, $description ?: null);
        $this->flash('success', 'Project updated.');
        return $this->redirect('/projects');
    }

    public function delete(string $id): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($response = $this->requireCsrf()) {
            return $response;
        }

        $project = Project::find((int) $id);

        if (!$project || $project['user_id'] !== Auth::userId()) {
            $this->flash('error', 'Project not found.');
            return $this->redirect('/projects');
        }

        Project::delete((int) $id);
        $this->flash('success', 'Project deleted.');
        return $this->redirect('/projects');
    }
}
