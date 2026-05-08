<?php

namespace App\Services;

use App\Models\AiSetting;

class ImageGenerationService
{
    private const API_URL = 'https://api.openai.com/v1/images/generations';
    private const MODEL   = 'gpt-image-1';

    // Supported sizes for gpt-image-1
    public const SIZES = [
        '1024x1024' => 'Quadrado (1:1) — Feed',
        '1024x1536' => 'Portrait (2:3) — Stories / Reels',
        '1536x1024' => 'Landscape (3:2) — Banner / Facebook',
    ];

    private string $apiKey;

    public function __construct()
    {
        $row          = AiSetting::get('openai');
        $this->apiKey = $row['api_key'] ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generate(string $prompt, string $size = '1024x1024'): array
    {
        if (!$this->isConfigured()) {
            return ['error' => 'OpenAI API Key não configurada. Configure em Administração → META → Geração de Imagens.'];
        }

        if (!array_key_exists($size, self::SIZES)) {
            $size = '1024x1024';
        }

        $payload = [
            'model'  => self::MODEL,
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $size,
        ];

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
            return ['error' => "Erro de conexão: {$err}"];
        }

        $data = json_decode($body, true) ?? [];

        if ($code !== 200 || isset($data['error'])) {
            $msg = $data['error']['message'] ?? "Erro HTTP {$code}";
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
            return ['error' => 'Nenhuma imagem retornada pela API.'];
        }

        return ['images' => $urls];
    }
}
