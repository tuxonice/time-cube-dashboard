<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CubeConfig;
use App\Models\TimeEntry;

class ApiController extends Controller
{
    public function cube(): void
    {
        $token = $this->requireApiToken();
        $userId = $token['user_id'];

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['cubeId']) || empty($input['face'])) {
            $this->json(['error' => 'Missing cubeId or face'], 400);
        }

        $cubeId = $input['cubeId'];
        $faceColor = $input['face'];

        // Look up which task this cube face maps to
        $mapping = CubeConfig::findTaskByFace($cubeId, $faceColor);
        if (!$mapping) {
            $this->json(['error' => 'No task mapped for this cube/face'], 404);
        }

        if ($mapping['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
        }

        // Stop any running timer for this user
        $running = TimeEntry::runningForUser($userId);
        if ($running) {
            // If already tracking the same task, just confirm
            if ($running['task_id'] === $mapping['task_id']) {
                $this->json([
                    'status' => 'already_running',
                    'task' => $mapping['task_name'],
                    'project' => $mapping['project_name'],
                ]);
            }
            TimeEntry::stop($running['id']);
        }

        // Start new timer
        $entryId = TimeEntry::start($mapping['task_id'], $userId);

        $this->json([
            'status' => 'started',
            'time_entry_id' => $entryId,
            'task' => $mapping['task_name'],
            'project' => $mapping['project_name'],
        ]);
    }
}
