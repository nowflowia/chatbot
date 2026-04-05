<?php

namespace App\Services;

use Core\Database;

/**
 * FlowEngine — executes flow nodes in response to incoming WhatsApp messages.
 *
 * Flow lifecycle:
 *   1. Incoming message arrives.
 *   2. If chat.is_bot_active = 0, skip (human agent is handling).
 *   3. If chat.current_node_id is set, resume from that node with user input.
 *   4. Otherwise, try to trigger a new flow (keyword, start, always).
 *   5. Execute nodes until a wait-for-input node (question/list) or terminal node (finish/transfer).
 */
class FlowEngine
{
    private Database $db;
    private MetaWhatsAppService $whatsapp;
    private array $settings;
    private array $chat;
    private array $vars; // runtime variables
    private int $stepLimit = 20; // prevent infinite loops

    public function __construct(array $settings)
    {
        $this->settings  = $settings;
        $this->db        = Database::getInstance();
        $this->whatsapp  = new MetaWhatsAppService($settings);
    }

    // ── Public entry point ─────────────────────────────────────────

    /**
     * Process an incoming message for a chat.
     * Returns true if the engine handled it, false if skipped.
     */
    public function processMessage(array $chat, string $text): bool
    {
        $this->chat = $chat;
        $this->vars = $this->loadVars($chat);

        // Agent is handling — skip
        if (!$chat['is_bot_active']) {
            return false;
        }

        // Resume from waiting node
        if (!empty($chat['current_node_id'])) {
            $node = $this->getNode((int)$chat['current_node_id']);
            if ($node) {
                $this->vars['_last_input'] = $text;
                $this->saveInput($node, $text);
                $this->advance($node, $text);
                return true;
            }
            // Node no longer exists — clear state and re-trigger
            $this->clearFlowState();
        }

        // Try to trigger a flow
        $flow = $this->matchFlow($text);
        if (!$flow) {
            return false;
        }

        $startNode = $this->getStartNode((int)$flow['id']);
        if (!$startNode) {
            return false;
        }

        // Attach flow to chat
        $this->db->update(
            "UPDATE chats SET flow_id = ?, is_bot_active = 1, updated_at = ? WHERE id = ?",
            [$flow['id'], now(), $this->chat['id']]
        );
        $this->chat['flow_id'] = $flow['id'];

        // Execute start node
        $this->executeNode($startNode);
        return true;
    }

    // ── Flow matching ──────────────────────────────────────────────

    private function matchFlow(string $text): ?array
    {
        $text = mb_strtolower(trim($text));

        $flows = $this->db->select(
            "SELECT * FROM flows WHERE is_active = 1 ORDER BY is_default DESC, id ASC"
        );

        foreach ($flows as $flow) {
            $trigger = $flow['trigger'] ?? 'keyword';

            if ($trigger === 'always') {
                return $flow;
            }

            if ($trigger === 'start' && empty($this->chat['flow_id'])) {
                return $flow;
            }

            if ($trigger === 'keyword' && !empty($flow['trigger_keywords'])) {
                $keywords = array_map('trim', explode(',', mb_strtolower($flow['trigger_keywords'])));
                foreach ($keywords as $kw) {
                    if ($kw !== '' && str_contains($text, $kw)) {
                        return $flow;
                    }
                }
            }
        }

        return null;
    }

    private function getStartNode(int $flowId): ?array
    {
        // Prefer is_start = 1
        $node = $this->db->selectOne(
            "SELECT * FROM flow_nodes WHERE flow_id = ? AND is_start = 1 AND status = 'active' LIMIT 1",
            [$flowId]
        );
        if ($node) return $this->decodeNode($node);

        // Fallback: lowest id
        $node = $this->db->selectOne(
            "SELECT * FROM flow_nodes WHERE flow_id = ? AND status = 'active' ORDER BY id ASC LIMIT 1",
            [$flowId]
        );
        return $node ? $this->decodeNode($node) : null;
    }

    private function getNode(int $nodeId): ?array
    {
        $row = $this->db->selectOne(
            "SELECT * FROM flow_nodes WHERE id = ? AND status = 'active' LIMIT 1", [$nodeId]
        );
        return $row ? $this->decodeNode($row) : null;
    }

    private function decodeNode(array $row): array
    {
        $row['config'] = !empty($row['config_json'])
            ? (json_decode($row['config_json'], true) ?? [])
            : [];
        return $row;
    }

    // ── Node execution ─────────────────────────────────────────────

    private function executeNode(array $node, int $depth = 0): void
    {
        if ($depth >= $this->stepLimit) {
            logger('FlowEngine: step limit reached for chat ' . $this->chat['id'], 'warning');
            return;
        }

        $cfg = $node['config'] ?? [];
        $type = $node['type'];

        switch ($type) {

            case 'message':
                $text = $this->interpolate($cfg['text'] ?? '');
                if ($text) {
                    $this->send($text);
                    $this->saveOutboundMessage($text);
                }
                $this->clearCurrentNode();
                $next = $this->getNextNode($node['id'], null);
                if ($next) $this->executeNode($next, $depth + 1);
                break;

            case 'question':
                $text = $this->interpolate($cfg['text'] ?? '');
                if ($text) {
                    $this->send($text);
                    $this->saveOutboundMessage($text);
                }
                // Park here — wait for next user message
                $this->setCurrentNode($node['id']);
                break;

            case 'list':
                $header  = $this->interpolate($cfg['header'] ?? 'Escolha uma opção');
                $options = $cfg['options'] ?? [];
                if ($options) {
                    $body = $header . "\n\n";
                    foreach ($options as $i => $opt) {
                        $body .= ($i + 1) . ". {$opt}\n";
                    }
                    $this->send(trim($body));
                    $this->saveOutboundMessage($header . ' [lista]');
                }
                // Park — wait for user to reply with option number or text
                $this->setCurrentNode($node['id']);
                break;

            case 'condition':
                $var      = $cfg['variable'] ?? '_last_input';
                $op       = $cfg['operator']  ?? 'equals';
                $expected = mb_strtolower(trim($cfg['value'] ?? ''));
                $actual   = mb_strtolower(trim($this->vars[$var] ?? ''));

                $match = match($op) {
                    'equals'    => $actual === $expected,
                    'contains'  => str_contains($actual, $expected),
                    'starts'    => str_starts_with($actual, $expected),
                    'not_empty' => $actual !== '',
                    default     => false,
                };

                $this->clearCurrentNode();
                $port = $match ? 'output_true' : 'output_false';
                // Try labeled port first, fallback to generic output
                $next = $this->getNextNode($node['id'], $port)
                     ?? $this->getNextNode($node['id'], 'output');
                if ($next) $this->executeNode($next, $depth + 1);
                break;

            case 'transfer':
                $msg = $this->interpolate($cfg['message'] ?? 'Transferindo para um atendente…');
                if ($msg) {
                    $this->send($msg);
                    $this->saveOutboundMessage($msg);
                }
                // Transfer to queue
                $this->db->update(
                    "UPDATE chats SET is_bot_active = 0, status = 'waiting', current_node_id = NULL, updated_at = ? WHERE id = ?",
                    [now(), $this->chat['id']]
                );
                break;

            case 'wait':
                $secs = (int)($cfg['seconds'] ?? 5);
                // In production use a queue; here we use sleep (max 30s for safety)
                if ($secs > 0 && $secs <= 30) sleep($secs);
                $this->clearCurrentNode();
                $next = $this->getNextNode($node['id'], null);
                if ($next) $this->executeNode($next, $depth + 1);
                break;

            case 'api_call':
                $url    = $this->interpolate($cfg['url']    ?? '');
                $method = strtoupper($cfg['method'] ?? 'GET');
                $varName= $cfg['variable'] ?? 'api_response';

                if ($url) {
                    $response = $this->httpCall($url, $method);
                    $this->vars[$varName] = $response;
                    $this->saveVars();
                }
                $this->clearCurrentNode();
                $next = $this->getNextNode($node['id'], null);
                if ($next) $this->executeNode($next, $depth + 1);
                break;

            case 'finish':
                $msg = $this->interpolate($cfg['message'] ?? 'Atendimento encerrado. Até logo!');
                if ($msg) {
                    $this->send($msg);
                    $this->saveOutboundMessage($msg);
                }
                $this->db->update(
                    "UPDATE chats SET status = 'finished', is_bot_active = 0, current_node_id = NULL, updated_at = ? WHERE id = ?",
                    [now(), $this->chat['id']]
                );
                break;

            default:
                logger("FlowEngine: unknown node type [{$type}]", 'warning');
                $this->clearCurrentNode();
                break;
        }
    }

    // ── Advance from waiting node ──────────────────────────────────

    private function advance(array $waitNode, string $input): void
    {
        $type = $waitNode['type'];
        $cfg  = $waitNode['config'] ?? [];

        if ($type === 'question') {
            $var = $cfg['variable'] ?? '_last_input';
            $this->vars[$var]          = $input;
            $this->vars['_last_input'] = $input;
            $this->saveVars();
        }

        if ($type === 'list') {
            $options = $cfg['options'] ?? [];
            $choice  = $this->resolveListChoice($input, $options);
            $this->vars['_last_input']     = $choice ?: $input;
            $this->vars['_list_selection'] = $choice ?: $input;
            $this->saveVars();
        }

        $this->clearCurrentNode();
        $next = $this->getNextNode($waitNode['id'], null);
        if ($next) $this->executeNode($next);
    }

    private function resolveListChoice(string $input, array $options): string
    {
        $input = trim($input);
        // numeric selection
        if (is_numeric($input)) {
            $idx = (int)$input - 1;
            return $options[$idx] ?? $input;
        }
        // text match
        $lower = mb_strtolower($input);
        foreach ($options as $opt) {
            if (mb_strtolower($opt) === $lower) return $opt;
        }
        return $input;
    }

    // ── Connections ────────────────────────────────────────────────

    private function getNextNode(int $sourceNodeId, ?string $port): ?array
    {
        $conn = $port
            ? $this->db->selectOne(
                "SELECT * FROM flow_connections WHERE source_node_id = ? AND source_port = ? ORDER BY sort_order ASC LIMIT 1",
                [$sourceNodeId, $port]
              )
            : $this->db->selectOne(
                "SELECT * FROM flow_connections WHERE source_node_id = ? ORDER BY sort_order ASC LIMIT 1",
                [$sourceNodeId]
              );

        if (!$conn) return null;
        return $this->getNode((int)$conn['target_node_id']);
    }

    // ── State helpers ──────────────────────────────────────────────

    private function setCurrentNode(int $nodeId): void
    {
        $this->db->update(
            "UPDATE chats SET current_node_id = ?, updated_at = ? WHERE id = ?",
            [$nodeId, now(), $this->chat['id']]
        );
    }

    private function clearCurrentNode(): void
    {
        $this->db->update(
            "UPDATE chats SET current_node_id = NULL, updated_at = ? WHERE id = ?",
            [now(), $this->chat['id']]
        );
    }

    private function clearFlowState(): void
    {
        $this->db->update(
            "UPDATE chats SET current_node_id = NULL, flow_vars = NULL, flow_id = NULL, updated_at = ? WHERE id = ?",
            [now(), $this->chat['id']]
        );
    }

    private function loadVars(array $chat): array
    {
        if (!empty($chat['flow_vars'])) {
            $decoded = json_decode($chat['flow_vars'], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function saveVars(): void
    {
        $this->db->update(
            "UPDATE chats SET flow_vars = ?, updated_at = ? WHERE id = ?",
            [json_encode($this->vars, JSON_UNESCAPED_UNICODE), now(), $this->chat['id']]
        );
    }

    private function saveInput(array $node, string $text): void
    {
        $cfg = $node['config'] ?? [];
        $var = $cfg['variable'] ?? '_last_input';
        $this->vars[$var]          = $text;
        $this->vars['_last_input'] = $text;
        $this->saveVars();
    }

    // ── Variable interpolation ─────────────────────────────────────

    private function interpolate(string $text): string
    {
        foreach ($this->vars as $key => $val) {
            $text = str_replace('{{' . $key . '}}', (string)$val, $text);
        }
        return $text;
    }

    // ── WhatsApp send ──────────────────────────────────────────────

    private function send(string $text): void
    {
        if (!$this->settings || empty($this->chat['contact_phone'])) {
            // Try to fetch phone from contact
            $contact = $this->db->selectOne(
                "SELECT phone FROM contacts WHERE id = ? LIMIT 1",
                [$this->chat['contact_id']]
            );
            if (!$contact) return;
            $phone = $contact['phone'];
        } else {
            $phone = $this->chat['contact_phone'];
        }

        try {
            $this->whatsapp->sendText($phone, $text);
        } catch (\Throwable $e) {
            logger('FlowEngine send error: ' . $e->getMessage(), 'error');
        }
    }

    private function saveOutboundMessage(string $text): void
    {
        try {
            $this->db->insert(
                "INSERT INTO messages (chat_id, direction, type, content, status, created_at, updated_at)
                 VALUES (?, 'outbound', 'text', ?, 'sent', ?, ?)",
                [$this->chat['id'], $text, now(), now()]
            );
            $this->db->update(
                "UPDATE chats SET last_message = ?, last_message_at = ?, updated_at = ? WHERE id = ?",
                [mb_substr($text, 0, 200), now(), now(), $this->chat['id']]
            );
        } catch (\Throwable $e) {
            logger('FlowEngine saveOutbound error: ' . $e->getMessage(), 'error');
        }
    }

    // ── HTTP helper ────────────────────────────────────────────────

    private function httpCall(string $url, string $method = 'GET'): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ChatBot-FlowEngine/1.0',
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return is_string($result) ? $result : '';
    }
}
