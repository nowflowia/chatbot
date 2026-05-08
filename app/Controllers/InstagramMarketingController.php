<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Auth;
use Core\Database;
use App\Services\InstagramService;

class InstagramMarketingController extends Controller
{
    private function requireMarketing(): void
    {
        if (!Auth::hasFeature('marketing')) {
            $this->redirect(url('admin/dashboard'));
            exit;
        }
    }

    // ── GET /admin/marketing/instagram ───────────────────────────────

    public function index(Request $request): string
    {
        $this->requireMarketing();

        $db    = Database::getInstance();
        $posts = $db->select(
            "SELECT * FROM instagram_posts ORDER BY created_at DESC LIMIT 100"
        );

        foreach ($posts as &$p) {
            $p['media_urls_decoded'] = !empty($p['media_urls'])
                ? json_decode($p['media_urls'], true) : [];
        }
        unset($p);

        return $this->view('marketing/instagram/index', [
            'posts' => $posts,
        ]);
    }

    // ── POST /admin/marketing/instagram/posts ────────────────────────

    public function storePost(Request $request): void
    {
        $this->requireMarketing();

        $mediaType = strtoupper((string) $request->post('media_type', 'IMAGE'));
        $caption   = trim((string) $request->post('caption', ''));
        $hashtags  = trim((string) $request->post('hashtags', ''));
        $campaign  = trim((string) $request->post('campaign_name', ''));
        $rawUrls   = $request->post('media_urls', '');
        $schedAt   = trim((string) $request->post('scheduled_at', ''));

        $validTypes = ['IMAGE', 'VIDEO', 'CAROUSEL', 'REELS', 'STORIES'];
        if (!in_array($mediaType, $validTypes, true)) {
            $this->jsonError('Tipo de mídia inválido.', [], 422);
        }

        // Parse media URLs (one per line or comma-separated)
        $urls = array_filter(array_map('trim', preg_split('/[\n,]+/', (string)$rawUrls)));
        if (empty($urls)) {
            $this->jsonError('Informe ao menos uma URL de mídia.', [], 422);
        }

        if ($mediaType !== 'CAROUSEL' && count($urls) > 1) {
            $urls = [array_values($urls)[0]];
        }

        $db     = Database::getInstance();
        $ts     = now();
        $userId = (int)(Auth::user()['id'] ?? 0);

        $id = $db->insert(
            "INSERT INTO instagram_posts
                (campaign_name, media_type, caption, media_urls, hashtags, status,
                 scheduled_at, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $campaign   ?: null,
                $mediaType,
                $caption    ?: null,
                json_encode(array_values($urls)),
                $hashtags   ?: null,
                $schedAt    ? 'scheduled' : 'draft',
                $schedAt    ?: null,
                $userId,
                $ts,
                $ts,
            ]
        );

        $post = $db->selectOne("SELECT * FROM instagram_posts WHERE id = ? LIMIT 1", [(int)$id]);

        $this->jsonSuccess('Post criado com sucesso!', ['post' => $post]);
    }

    // ── POST /admin/marketing/instagram/posts/{id}/publish ───────────

    public function publishPost(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db   = Database::getInstance();
        $post = $db->selectOne("SELECT * FROM instagram_posts WHERE id = ? LIMIT 1", [(int)$id]);

        if (!$post) {
            $this->jsonError('Post não encontrado.', [], 404);
        }

        if (!in_array($post['status'], ['draft', 'scheduled', 'failed'], true)) {
            $this->jsonError('Este post não pode ser publicado novamente.', [], 422);
        }

        $db->update(
            "UPDATE instagram_posts SET status = 'publishing', updated_at = ? WHERE id = ?",
            [now(), (int)$id]
        );

        $service   = new InstagramService();
        $urls      = json_decode($post['media_urls'] ?? '[]', true) ?: [];
        $caption   = trim(($post['caption'] ?? '') . ($post['hashtags'] ? "\n\n" . $post['hashtags'] : ''));
        $mediaType = $post['media_type'] ?? 'IMAGE';

        try {
            $result = match ($mediaType) {
                'IMAGE'   => $service->publishImage($urls[0], $caption),
                'REELS'   => $service->publishVideo($urls[0], $caption, 'REELS'),
                'VIDEO'   => $service->publishVideo($urls[0], $caption, 'VIDEO'),
                'STORIES' => $service->publishImage($urls[0], $caption),
                'CAROUSEL'=> $service->publishCarousel($urls, $caption),
                default   => $service->publishImage($urls[0], $caption),
            };

            if (isset($result['error'])) {
                $errMsg = $result['error']['message'] ?? 'Erro na API do Instagram.';
                $db->update(
                    "UPDATE instagram_posts SET status = 'failed', error_message = ?, updated_at = ? WHERE id = ?",
                    [$errMsg, now(), (int)$id]
                );
                $this->jsonError("Falha ao publicar: {$errMsg}");
            }

            $postId    = $result['id'] ?? null;
            $permalink = '';
            if ($postId) {
                $details   = $service->getPostDetails($postId);
                $permalink = $details['permalink'] ?? '';
            }

            $db->update(
                "UPDATE instagram_posts SET status = 'published', instagram_post_id = ?,
                 permalink = ?, published_at = ?, error_message = NULL, updated_at = ? WHERE id = ?",
                [$postId, $permalink, now(), now(), (int)$id]
            );

            $this->jsonSuccess('Post publicado com sucesso!', [
                'permalink'  => $permalink,
                'post_id'    => $postId,
            ]);

        } catch (\Throwable $e) {
            $db->update(
                "UPDATE instagram_posts SET status = 'failed', error_message = ?, updated_at = ? WHERE id = ?",
                [$e->getMessage(), now(), (int)$id]
            );
            $this->jsonError('Erro ao publicar: ' . $e->getMessage());
        }
    }

    // ── POST /admin/marketing/instagram/posts/{id}/delete ────────────

    public function destroyPost(Request $request, string $id): void
    {
        $this->requireMarketing();

        $db   = Database::getInstance();
        $post = $db->selectOne("SELECT status FROM instagram_posts WHERE id = ? LIMIT 1", [(int)$id]);

        if (!$post) {
            $this->jsonError('Post não encontrado.', [], 404);
        }

        if ($post['status'] === 'publishing') {
            $this->jsonError('Não é possível excluir um post em publicação.', [], 422);
        }

        $db->delete("DELETE FROM instagram_posts WHERE id = ?", [(int)$id]);

        $this->jsonSuccess('Post excluído.');
    }
}
