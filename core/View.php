<?php

namespace Core;

class View
{
    private static string $layoutPath = '';
    private static array $sections    = [];
    private static ?string $currentSection = null;

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/master'): string
    {
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = VIEW_PATH . '/' . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutFile)) {
                ob_start();
                require $layoutFile;
                return ob_get_clean();
            }
        }

        return $content;
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        return static::render($view, $data, null);
    }

    public static function section(string $name): void
    {
        static::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (static::$currentSection === null) {
            return;
        }
        static::$sections[static::$currentSection] = ob_get_clean();
        static::$currentSection = null;
    }

    public static function yield(string $name, string $default = ''): string
    {
        return static::$sections[$name] ?? $default;
    }

    public static function include(string $view, array $data = []): void
    {
        echo static::renderPartial($view, $data);
    }

    public static function clearSections(): void
    {
        static::$sections      = [];
        static::$currentSection = null;
    }
}
