<?php

namespace App\Services;

use App\Models\MetaAdSetting;

/**
 * Wrapper for the Facebook Marketing API (Meta Ads).
 */
class MetaAdsService
{
    private string $base    = 'https://graph.facebook.com';
    private string $version;
    private string $token;
    private string $adAccount;
    private array  $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings   = $settings ?? MetaAdSetting::getActive() ?? [];
        $this->version    = $this->settings['api_version']    ?? 'v21.0';
        $this->token      = $this->settings['access_token']   ?? '';
        $this->adAccount  = $this->settings['ad_account_id']  ?? '';
    }

    // ── Connection ──────────────────────────────────────────────────

    public function testConnection(): array
    {
        if (empty($this->token))      return ['ok' => false, 'message' => 'Access Token não configurado.'];
        if (empty($this->adAccount))  return ['ok' => false, 'message' => 'Ad Account ID não configurado.'];

        $res = $this->get("/{$this->adAccount}?fields=id,name,account_status,currency,timezone_name");

        if (isset($res['error'])) {
            return ['ok' => false, 'message' => $res['error']['message'] ?? 'Erro desconhecido.', 'data' => $res];
        }

        if (isset($res['id'])) {
            $status = match ((int)($res['account_status'] ?? 0)) {
                1 => 'Ativa', 2 => 'Desativada', 3 => 'Sem pagamento', 9 => 'Em análise', default => 'Desconhecido'
            };
            return [
                'ok'      => true,
                'message' => "Conectado! Conta: {$res['name']} ({$res['currency']}) — Status: {$status}",
                'data'    => $res,
            ];
        }

        return ['ok' => false, 'message' => 'Resposta inesperada da API.', 'data' => $res];
    }

    // ── Campaign CRUD ────────────────────────────────────────────────

    public function createCampaign(array $data): array
    {
        return $this->post("/{$this->adAccount}/campaigns", [
            'name'                            => $data['name'],
            'objective'                       => $data['objective'] ?? 'OUTCOME_TRAFFIC',
            'status'                          => $data['status']    ?? 'PAUSED',
            'special_ad_categories'           => [],
            'is_adset_budget_sharing_enabled' => false,
        ]);
    }

    public function createAdSet(array $data): array
    {
        if (empty($data['campaign_id'])) {
            return ['error' => ['message' => 'campaign_id é obrigatório. Use o ID retornado por create_campaign.']];
        }

        $targeting = $data['targeting'] ?? ['geo_locations' => ['countries' => ['BR']]];

        // Strip invalid interests (Meta requires numeric IDs from its taxonomy)
        if (isset($targeting['interests']) && is_array($targeting['interests'])) {
            $valid = array_filter($targeting['interests'], function ($i) {
                if (is_array($i)) return !empty($i['id']) && ctype_digit((string)$i['id']);
                return ctype_digit((string)$i);
            });
            if (empty($valid)) {
                unset($targeting['interests']);
            } else {
                $targeting['interests'] = array_values($valid);
            }
        }

        // Same for behaviors / detailed_targeting
        foreach (['behaviors', 'detailed_targeting'] as $field) {
            if (isset($targeting[$field]) && is_array($targeting[$field])) {
                $valid = array_filter($targeting[$field], function ($i) {
                    if (is_array($i)) return !empty($i['id']) && ctype_digit((string)$i['id']);
                    return ctype_digit((string)$i);
                });
                if (empty($valid)) unset($targeting[$field]);
                else $targeting[$field] = array_values($valid);
            }
        }

        // Meta requires advantage_audience flag (1 = enabled / 0 = disabled)
        if (!isset($targeting['targeting_automation']['advantage_audience'])) {
            $targeting['targeting_automation'] = ['advantage_audience' => 0];
        }

        $payload = [
            'name'              => $data['name'],
            'campaign_id'       => $data['campaign_id'],
            'billing_event'     => 'IMPRESSIONS',
            'optimization_goal' => $data['optimization_goal'] ?? 'REACH',
            'bid_strategy'      => 'LOWEST_COST_WITHOUT_CAP',
            'status'            => $data['status'] ?? 'PAUSED',
            'targeting'         => $targeting,
        ];

        if (!empty($data['daily_budget'])) {
            $payload['daily_budget'] = (int)(floatval($data['daily_budget']) * 100);
        } elseif (!empty($data['lifetime_budget'])) {
            $payload['lifetime_budget'] = (int)(floatval($data['lifetime_budget']) * 100);
        }

        if (!empty($data['start_time'])) $payload['start_time'] = $data['start_time'];
        if (!empty($data['end_time']))   $payload['end_time']   = $data['end_time'];

        return $this->post("/{$this->adAccount}/adsets", $payload);
    }

    public function createAdCreative(array $data): array
    {
        $objectStory = [];

        if (!empty($this->settings['page_id'])) {
            $objectStory['page_id'] = $this->settings['page_id'];
        }

        if (!empty($data['image_url'])) {
            $objectStory['link_data'] = [
                'image_hash' => $data['image_hash'] ?? null,
                'link'       => $data['link']        ?? 'https://facebook.com',
                'message'    => $data['body']        ?? '',
                'name'       => $data['headline']    ?? '',
                'call_to_action' => [
                    'type'  => $data['cta'] ?? 'LEARN_MORE',
                    'value' => ['link' => $data['link'] ?? 'https://facebook.com'],
                ],
            ];
            if (!empty($data['image_url'])) {
                $objectStory['link_data']['picture'] = $data['image_url'];
            }
        }

        return $this->post("/{$this->adAccount}/adcreatives", [
            'name'          => $data['name']  ?? 'Creative',
            'object_story_spec' => $objectStory,
        ]);
    }

    public function createAd(array $data): array
    {
        return $this->post("/{$this->adAccount}/ads", [
            'name'       => $data['name'],
            'adset_id'   => $data['adset_id'],
            'creative'   => ['creative_id' => $data['creative_id']],
            'status'     => $data['status'] ?? 'PAUSED',
        ]);
    }

    public function pauseCampaign(string $campaignId): array
    {
        return $this->post("/{$campaignId}", ['status' => 'PAUSED']);
    }

    public function activateCampaign(string $campaignId): array
    {
        return $this->post("/{$campaignId}", ['status' => 'ACTIVE']);
    }

    // ── Insights / Analytics ─────────────────────────────────────────

    public function getCampaignInsights(string $campaignId): array
    {
        return $this->get("/{$campaignId}/insights?fields=impressions,reach,clicks,spend,cpm,cpc,ctr,actions&date_preset=last_7d");
    }

    public function getAdAccountInsights(): array
    {
        return $this->get("/{$this->adAccount}/insights?fields=impressions,reach,clicks,spend,cpm,cpc,ctr&date_preset=last_30d");
    }

    public function listCampaigns(): array
    {
        $res = $this->get("/{$this->adAccount}/campaigns?fields=id,name,objective,status,daily_budget,lifetime_budget,created_time&limit=50");
        return $res['data'] ?? [];
    }

    // ── Image Upload ─────────────────────────────────────────────────

    public function uploadImageFromUrl(string $imageUrl): array
    {
        return $this->post("/{$this->adAccount}/adimages", ['url' => $imageUrl]);
    }

    // ── HTTP helpers ──────────────────────────────────────────────────

    private function get(string $endpoint): array
    {
        $sep = str_contains($endpoint, '?') ? '&' : '?';
        $url = "{$this->base}/{$this->version}{$endpoint}{$sep}access_token=" . urlencode($this->token);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) return ['error' => ['message' => "cURL: {$err}"]];
        return json_decode($body, true) ?? ['error' => ['message' => 'Resposta inválida']];
    }

    private function post(string $endpoint, array $payload): array
    {
        // Meta Marketing API accepts JSON bodies — required for arrays/objects
        // (http_build_query silently drops empty arrays like special_ad_categories:[])
        $sep = str_contains($endpoint, '?') ? '&' : '?';
        $url = "{$this->base}/{$this->version}{$endpoint}{$sep}access_token=" . urlencode($this->token);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err) return ['error' => ['message' => "cURL: {$err}"]];
        $decoded = json_decode($body, true) ?? [];
        if ($code >= 400 && isset($decoded['error'])) {
            logger("MetaAds API error (HTTP {$code}): " . json_encode($decoded['error']), 'error');
        }
        return $decoded;
    }

    public function getSettings(): array { return $this->settings; }
}
