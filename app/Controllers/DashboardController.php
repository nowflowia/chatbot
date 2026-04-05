<?php

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\View;

class DashboardController extends Controller
{
    public function index(Request $request): string
    {
        $db = Database::getInstance();

        // ---- Totais ----
        $totalUsers    = (int)($db->selectOne("SELECT COUNT(*) c FROM users WHERE status = 'active'")['c'] ?? 0);
        $totalContacts = (int)($db->selectOne("SELECT COUNT(*) c FROM contacts")['c'] ?? 0);
        $totalFlows    = (int)($db->selectOne("SELECT COUNT(*) c FROM flows WHERE is_active = 1")['c'] ?? 0);

        $chatsToday = (int)($db->selectOne(
            "SELECT COUNT(*) c FROM chats WHERE DATE(created_at) = CURDATE()"
        )['c'] ?? 0);

        $chatsWaiting = (int)($db->selectOne(
            "SELECT COUNT(*) c FROM chats WHERE status = 'waiting'"
        )['c'] ?? 0);

        $chatsInProgress = (int)($db->selectOne(
            "SELECT COUNT(*) c FROM chats WHERE status = 'in_progress'"
        )['c'] ?? 0);

        $chatsFinished = (int)($db->selectOne(
            "SELECT COUNT(*) c FROM chats WHERE status = 'finished' AND DATE(updated_at) = CURDATE()"
        )['c'] ?? 0);

        $messagesTotal = (int)($db->selectOne("SELECT COUNT(*) c FROM messages")['c'] ?? 0);

        // ---- WhatsApp status ----
        $waSettings = $db->selectOne("SELECT status, name FROM whatsapp_settings ORDER BY id ASC LIMIT 1");

        // ---- Últimos chats ----
        $recentChats = $db->select(
            "SELECT ch.*, co.name as contact_name, co.phone as contact_phone,
                    u.name as agent_name
             FROM chats ch
             LEFT JOIN contacts co ON co.id = ch.contact_id
             LEFT JOIN users u ON u.id = ch.assigned_to
             ORDER BY ch.updated_at DESC
             LIMIT 8"
        );

        // ---- Últimos usuários criados ----
        $recentUsers = $db->select(
            "SELECT u.*, r.name as role_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC
             LIMIT 5"
        );

        // ---- Chats por hora (últimas 24h) ----
        $hourlyRows = $db->select(
            "SELECT HOUR(created_at) as h, COUNT(*) as cnt
             FROM chats
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY HOUR(created_at)
             ORDER BY h ASC"
        );
        $hourlyData = array_fill(0, 24, 0);
        foreach ($hourlyRows as $row) {
            $hourlyData[(int)$row['h']] = (int)$row['cnt'];
        }

        // ---- Chats por dia (últimos 7 dias) ----
        $weeklyRows = $db->select(
            "SELECT DATE(created_at) as d, COUNT(*) as cnt
             FROM chats
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC"
        );
        $weeklyData   = [];
        $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date           = date('Y-m-d', strtotime("-{$i} days"));
            $label          = date('d/m', strtotime($date));
            $weeklyLabels[] = $label;
            $weeklyData[]   = 0;
        }
        foreach ($weeklyRows as $row) {
            $label = date('d/m', strtotime($row['d']));
            $idx   = array_search($label, $weeklyLabels);
            if ($idx !== false) $weeklyData[$idx] = (int)$row['cnt'];
        }

        // ---- Mensagens hoje ----
        $messagesToday = (int)($db->selectOne(
            "SELECT COUNT(*) c FROM messages WHERE DATE(created_at) = CURDATE()"
        )['c'] ?? 0);

        return $this->view('dashboard/index', [
            'totalUsers'      => $totalUsers,
            'totalContacts'   => $totalContacts,
            'totalFlows'      => $totalFlows,
            'chatsToday'      => $chatsToday,
            'chatsWaiting'    => $chatsWaiting,
            'chatsInProgress' => $chatsInProgress,
            'chatsFinished'   => $chatsFinished,
            'messagesTotal'   => $messagesTotal,
            'messagesToday'   => $messagesToday,
            'waSettings'      => $waSettings,
            'recentChats'     => $recentChats,
            'recentUsers'     => $recentUsers,
            'weeklyLabels'    => $weeklyLabels,
            'weeklyData'      => $weeklyData,
        ]);
    }
}
