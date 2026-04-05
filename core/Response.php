<?php

namespace Core;

class Response
{
    private int $statusCode = 200;
    private array $headers  = [];
    private string $body    = '';

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function body(string $content): static
    {
        $this->body = $content;
        return $this;
    }

    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function success(string $message = 'OK', mixed $data = null, int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => [],
        ], $status);
    }

    public function error(string $message = 'Error', array $errors = [], int $status = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $status);
    }

    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
        exit;
    }

    public function view(string $view, array $data = []): void
    {
        $content = View::render($view, $data);
        $this->body($content)->send();
    }

    public function notFound(): void
    {
        http_response_code(404);
        echo View::render('errors/404', []);
        exit;
    }

    public function serverError(string $message = ''): void
    {
        http_response_code(500);
        echo View::render('errors/500', ['message' => $message]);
        exit;
    }
}
