<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\TimeEntry;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }
        $userId = Auth::userId();
        $db = Database::getInstance();

        // Project totals
        $projects = $db->fetchAll(
            'SELECT p.id, p.name,
                    COALESCE(SUM(te.duration), 0) as total_seconds,
                    COUNT(DISTINCT t.id) as task_count
             FROM projects p
             LEFT JOIN tasks t ON t.project_id = p.id
             LEFT JOIN time_entries te ON te.task_id = t.id
             WHERE p.user_id = ?
             GROUP BY p.id
             ORDER BY total_seconds DESC',
            [$userId]
        );

        // Overall total
        $overall = $db->fetch(
            'SELECT COALESCE(SUM(te.duration), 0) as total
             FROM time_entries te
             JOIN tasks t ON te.task_id = t.id
             JOIN projects p ON t.project_id = p.id
             WHERE p.user_id = ?',
            [$userId]
        );

        // Today total
        $todayTotal = TimeEntry::todayTotalForUser($userId);

        // Active timer
        $running = TimeEntry::runningForUser($userId);

        // Project count
        $projectCount = $db->fetch(
            'SELECT COUNT(*) as count FROM projects WHERE user_id = ?',
            [$userId]
        );

        return $this->render('dashboard/index.twig', [
            'projects' => $projects,
            'total_seconds' => (int) $overall['total'],
            'today_seconds' => $todayTotal,
            'running' => $running,
            'project_count' => (int) $projectCount['count'],
        ]);
    }
}
