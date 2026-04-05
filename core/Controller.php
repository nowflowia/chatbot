<?php

namespace Core;

abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->request  = new Request();
        $this->response = new Response();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'layouts/master'): string
    {
        View::clearSections();
        return View::render($view, $data, $layout);
    }

    protected function json(bool $success, string $message = '', mixed $data = null, array $errors = [], int $status = 200): void
    {
        // Discard any buffered output (warnings, notices) that would corrupt JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonSuccess(string $message = 'OK', mixed $data = null, int $status = 200): void
    {
        $this->json(true, $message, $data, [], $status);
    }

    protected function jsonError(string $message = 'Error', array $errors = [], int $status = 400): void
    {
        $this->json(false, $message, null, $errors, $status);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function back(): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? url('/'));
    }

    protected function validate(array $rules): array|false
    {
        $validator = new Validator($this->request->all(), $rules);
        if ($validator->fails()) {
            return false;
        }
        return $validator->validated();
    }

    protected function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    protected function requireAjax(): void
    {
        if (!$this->request->isAjax()) {
            $this->json(false, 'Requisição inválida.', null, [], 400);
        }
    }

    protected function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}
