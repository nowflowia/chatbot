<?php

namespace App\Services;

use App\Models\AiSetting;

class ImageGenerationService
{
    private const API_URL      = 'https://api.openai.com/v1/images/generations';
    private const DEFAULT_MODEL = 'gpt-image-1';

    public const MODELS = [
        'gpt-image-1' => 'GPT Image 1 — Mais recente (recomendado)',
        'dall-e-3'    => 'DALL·E 3 — Alta qualidade',
        'dall-e-2'    => 'DALL·E 2 — Mais rápido / econômico',
    ];

    // Sizes vary by model: gpt-image-1 and dall-e-3 support these
    public const SIZES = [
        '1024x1024' => 'Quadrado (1:1) — Feed',
        '1024x1536' => 'Portrait (2:3) — Stories / Reels',
        '1536x1024' => 'Landscape (3:2) — Banner / Facebook',
    ];

    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $row          = AiSetting::get('openai');
        $this->apiKey = $row['api_key'] ?? '';

        // Image model is META-specific — kept in meta_ad_settings.image_model
        // so it does NOT overwrite the chat model in ai_settings.openai.model
        $metaRow      = \App\Models\MetaAdSetting::getActive();
        $imageModel   = $metaRow['image_model'] ?? self::DEFAULT_MODEL;
        if (!array_key_exists($imageModel, self::MODELS)) $imageModel = self::DEFAULT_MODEL;
        $this->model  = $imageModel;
    }

    public function getModel(): string { return $this->model; }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generate(string $prompt, string $size = '1024x1024'): array
    {
        if (!$this->isConfigured()) {
            logger('ImageGenerationService: API Key não configurada', 'error');
            return ['error' => 'OpenAI API Key não configurada. Configure em Administração → META → Geração de Imagens.'];
        }

        if (!array_key_exists($size, self::SIZES)) {
            $size = '1024x1024';
        }

        // dall-e-2 only supports square sizes
        if ($this->model === 'dall-e-2' && $size !== '1024x1024') {
            $size = '1024x1024';
        }

        $payload = [
            'model'  => $this->model,
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $size,
        ];

        logger('ImageGenerationService: iniciando geração — model=' . $this->model . ' size=' . $size . ' prompt=' . mb_substr($prompt, 0, 120));

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            logger('ImageGenerationService: cURL error — ' . $err, 'error');
            return ['error' => "Erro de conexão: {$err}"];
        }

        $data = json_decode($body, true) ?? [];

        if ($code !== 200 || isset($data['error'])) {
            $msg = $data['error']['message'] ?? "Erro HTTP {$code}";
            logger('ImageGenerationService: API error HTTP ' . $code . ' — ' . $msg, 'error');
            return ['error' => "Erro da API OpenAI: {$msg}"];
        }

        $urls      = [];
        $uploadDir = defined('ROOT_PATH') ? ROOT_PATH . '/public/uploads/meta-images' : __DIR__ . '/../../public/uploads/meta-images';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($data['data'] ?? [] as $item) {
            if (!empty($item['b64_json'])) {
                $filename = 'meta-' . uniqid('', true) . '.png';
                $path     = $uploadDir . '/' . $filename;
                file_put_contents($path, base64_decode($item['b64_json']));
                $urls[] = url('uploads/meta-images/' . $filename);
            } elseif (!empty($item['url'])) {
                $urls[] = $item['url'];
            }
        }

        if (empty($urls)) {
            logger('ImageGenerationService: API retornou resposta vazia — body=' . mb_substr($body, 0, 300), 'error');
            return ['error' => 'Nenhuma imagem retornada pela API.'];
        }

        logger('ImageGenerationService: ' . count($urls) . ' imagem(ns) gerada(s) com sucesso — ' . implode(', ', $urls));
        return ['images' => $urls];
    }
}
