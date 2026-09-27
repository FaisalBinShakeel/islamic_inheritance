<?php

declare(strict_types=1);

namespace App;

/** Plain PHP templates, rendered into one layout. No template engine, no compile step. */
final class View
{
    private static string $root = '';

    public static function root(): string
    {
        return self::$root !== '' ? self::$root : dirname(__DIR__, 2) . '/resources/views';
    }

    public static function setRoot(string $root): void
    {
        self::$root = $root;
    }

    /** Render a template on its own. */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::root() . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /** Render a template inside the site layout. */
    public static function page(string $template, array $data, Seo $seo, string $layout = 'layout/site'): string
    {
        $content = self::partial($template, $data + ['seo' => $seo]);

        return self::partial($layout, [
            'seo' => $seo,
            'content' => $content,
            'bodyClass' => $data['bodyClass'] ?? '',
            'inlineScript' => $data['inlineScript'] ?? '',
            'pageScript' => $data['pageScript'] ?? null,
        ]);
    }
}
