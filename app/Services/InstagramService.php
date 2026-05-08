<?php

namespace App\Services;

use App\Models\InstagramSetting;

class InstagramService
{
    private string $baseUrl    = 'https://graph.facebook.com';
    private string $apiVersion;
    private string $accessToken;
    private string $accountId;
    private array  $settings;

    public function __construct(?array $settings = null)
    {
        $this->settings    = $settings ?? InstagramSetting::getActive() ?? [];
        $this->apiVersion  = $this->settings['api_version']          ?? 'v21.0';
        $this->accessToken = $this->settings['access_token']         ?? '';
        $this->accountId   = $this->settings['instagram_account_id'] ?? '';
    }

    // ----------------------------------------------------------------
    // Connection / Account Info
    // ----------------------------------------------------------------

    public function testConnection(): array
    {
        if (empty($this->accessToken)) {
            return ['ok' => false, 'message' => 'Access Token não configurado.', 'data' => []];
        }
        if (empty($this->accountId)) {
            return ['ok' => false, 'message' => 'Instagram Account ID não configurado.', 'data' => []];
        }

        $result = $this->request('GET', "/{$this->accountId}?fields=id,name,username,followers_count,media_count,profile_picture_url,biography,website");

        if (isset($result['error'])) {
            $msg = $result['error']['message'] ?? 'Erro desconhecido.';
            return ['ok' => false, 'message' => $msg, 'data' => $result];
        }

        if (isset($result['id'])) {
            return [
                'ok'      => true,
                'message' => "Conectado! @{$result['username']} — {$result['followers_count']} seguidores",
                'data'    => $result,
            ];
        }

        return ['ok' => false, 'message' => 'Resposta inesperada da API.', 'data' => $result];
    }

    public function getAccountInfo(): array
    {
        return $this->request('GET', "/{$this->accountId}?fields=id,name,username,biography,followers_count,follows_count,media_count,profile_picture_url,website,ig_id");
    }

    // ----------------------------------------------------------------
    // OAuth — exchange code for long-lived token
    // ----------------------------------------------------------------

    public function exchangeToken(string $code, string $redirectUri): array
    {
        $appId     = $this->settings['app_id']     ?? '';
        $appSecret = $this->settings['app_secret'] ?? '';

        $url = "https://graph.facebook.com/{$this->apiVersion}/oauth/access_token";
        $ch  = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ]),
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return json_decode($body, true) ?? [];
    }

    public function getLongLivedToken(string $shortToken): array
    {
        $appId     = $this->settings['app_id']     ?? '';
        $appSecret = $this->settings['app_secret'] ?? '';
        return $this->request('GET', "/oauth/access_token?grant_type=fb_exchange_token&client_id={$appId}&client_secret={$appSecret}&fb_exchange_token={$shortToken}");
    }

    // Get pages and their linked Instagram accounts from an access token
    public function getPagesWithInstagram(string $token): array
    {
        $result = $this->requestWithToken('GET', "/me/accounts?fields=id,name,access_token,instagram_business_account{id,name,username}&limit=20", $token);
        return $result['data'] ?? [];
    }

    // ----------------------------------------------------------------
    // Publishing
    // ----------------------------------------------------------------

    /**
     * Publish a single image post.
     */
    public function publishImage(string $imageUrl, string $caption = ''): array
    {
        // Step 1: Create media container
        $container = $this->request('POST', "/{$this->accountId}/media", [
            'image_url' => $imageUrl,
            'caption'   => $caption,
        ]);

        if (isset($container['error']) || empty($container['id'])) {
            return $container;
        }

        // Step 2: Publish container
        return $this->request('POST', "/{$this->accountId}/media_publish", [
            'creation_id' => $container['id'],
        ]);
    }

    /**
     * Publish a video/reel.
     */
    public function publishVideo(string $videoUrl, string $caption = '', string $mediaType = 'REELS'): array
    {
        $container = $this->request('POST', "/{$this->accountId}/media", [
            'media_type' => $mediaType,
            'video_url'  => $videoUrl,
            'caption'    => $caption,
        ]);

        if (isset($container['error']) || empty($container['id'])) {
            return $container;
        }

        // Wait for video to be ready (poll status)
        $creationId = $container['id'];
        $ready      = false;
        for ($i = 0; $i < 10; $i++) {
            sleep(3);
            $status = $this->request('GET', "/{$creationId}?fields=status_code");
            if (($status['status_code'] ?? '') === 'FINISHED') {
                $ready = true;
                break;
            }
        }

        if (!$ready) {
            return ['error' => ['message' => 'Timeout ao processar vídeo.']];
        }

        return $this->request('POST', "/{$this->accountId}/media_publish", [
            'creation_id' => $creationId,
        ]);
    }

    /**
     * Publish a carousel (multiple images).
     */
    public function publishCarousel(array $imageUrls, string $caption = ''): array
    {
        // Step 1: Create child containers for each image
        $childIds = [];
        foreach ($imageUrls as $url) {
            $child = $this->request('POST', "/{$this->accountId}/media", [
                'image_url'   => $url,
                'is_carousel_item' => true,
            ]);
            if (isset($child['error']) || empty($child['id'])) {
                return $child;
            }
            $childIds[] = $child['id'];
        }

        // Step 2: Create carousel container
        $container = $this->request('POST', "/{$this->accountId}/media", [
            'media_type'    => 'CAROUSEL',
            'children'      => implode(',', $childIds),
            'caption'       => $caption,
        ]);

        if (isset($container['error']) || empty($container['id'])) {
            return $container;
        }

        // Step 3: Publish
        return $this->request('POST', "/{$this->accountId}/media_publish", [
            'creation_id' => $container['id'],
        ]);
    }

    /**
     * Get permalink and details of a published post.
     */
    public function getPostDetails(string $postId): array
    {
        return $this->request('GET', "/{$postId}?fields=id,permalink,timestamp,like_count,comments_count,media_type,thumbnail_url");
    }

    /**
     * List recent published media.
     */
    public function getRecentMedia(int $limit = 20): array
    {
        return $this->request('GET', "/{$this->accountId}/media?fields=id,caption,media_type,permalink,timestamp,like_count,comments_count,thumbnail_url,media_url&limit={$limit}");
    }

    // ----------------------------------------------------------------
    // HTTP Client
    // ----------------------------------------------------------------

    private function request(string $method, string $endpoint, array $payload = []): array
    {
        return $this->requestWithToken($method, $endpoint, $this->accessToken, $payload);
    }

    private function requestWithToken(string $method, string $endpoint, string $token, array $payload = []): array
    {
        $sep = str_contains($endpoint, '?') ? '&' : '?';
        $url = $this->baseUrl . '/' . $this->apiVersion . $endpoint . $sep . 'access_token=' . urlencode($token);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => ['message' => "cURL error: {$error}"]];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['error' => ['message' => "Resposta inválida da API (HTTP {$httpCode})"]];
        }

        if ($httpCode >= 400 && isset($decoded['error'])) {
            logger("Instagram API error (HTTP {$httpCode}): " . json_encode($decoded['error']), 'error');
        }

        return $decoded;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function buildOAuthUrl(string $redirectUri): string
    {
        $appId = $this->settings['app_id'] ?? '';
        $scope = 'instagram_basic,instagram_content_publish,pages_read_engagement,pages_show_list';
        return "https://www.facebook.com/{$this->apiVersion}/dialog/oauth?" . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'scope'         => $scope,
            'response_type' => 'code',
        ]);
    }
}
