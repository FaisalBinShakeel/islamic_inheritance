<?php

declare(strict_types=1);

namespace App;

/**
 * Every page's head, built once in the template layer so no page can ship
 * broken.
 *
 * Four rules are structural guarantees here rather than things a person has to
 * remember:
 *
 *  1. The H1 defaults to the title, so the two cannot silently diverge.
 *  2. The canonical and og:url are generated from Config::url(), never stored
 *     per page and never hand-typed, so an http:// canonical on an https://
 *     site is impossible.
 *  3. A missing meta description is a loud failure in debug mode and is
 *     blocked at the point of entry in the admin.
 *  4. Exactly one H1 is rendered, by the layout, from this object.
 */
final class Seo
{
    private string $title = '';
    private ?string $h1 = null;
    private string $description = '';
    private string $path = '/';
    private string $type = 'website';
    private ?string $image = null;
    private ?string $imageAlt = null;
    private string $robots = 'index, follow, max-image-preview:large';
    private ?string $publishedAt = null;
    private ?string $updatedAt = null;

    /** @var array<string,string> locale => path */
    private array $alternates = [];

    /** @var list<array{name:string,path:string}> */
    private array $breadcrumbs = [];

    /** @var list<array<string,mixed>> */
    private array $jsonLd = [];

    /** @var list<string> */
    private array $problems = [];

    public function __construct(string $path = '/')
    {
        $this->path = $path;
    }

    public function title(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function h1(?string $h1): self
    {
        $this->h1 = $h1 === null ? null : trim($h1);

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function image(?string $image, ?string $alt = null): self
    {
        $this->image = $image;
        $this->imageAlt = $alt;

        return $this;
    }

    public function noindex(): self
    {
        $this->robots = 'noindex, follow';

        return $this;
    }

    public function dates(?string $publishedAt, ?string $updatedAt): self
    {
        $this->publishedAt = $publishedAt;
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /** @param array<string,string> $alternates locale => path */
    public function alternates(array $alternates): self
    {
        $this->alternates = $alternates;

        return $this;
    }

    /** @param list<array{name:string,path:string}> $crumbs */
    public function breadcrumbs(array $crumbs): self
    {
        $this->breadcrumbs = $crumbs;

        return $this;
    }

    public function addJsonLd(array $document): self
    {
        $this->jsonLd[] = $document;

        return $this;
    }

    /** @return list<array{name:string,path:string}> */
    public function crumbs(): array
    {
        return $this->breadcrumbs;
    }

    public function heading(): string
    {
        // Default the H1 to the title unless it was explicitly overridden.
        return $this->h1 ?? $this->title;
    }

    public function canonical(): string
    {
        return Config::url($this->path);
    }

    /** Title with the brand suffix, kept under sixty characters where possible. */
    public function documentTitle(): string
    {
        $brand = Config::siteName();
        if ($this->title === '') {
            return $brand;
        }
        if (str_contains($this->title, $brand)) {
            return $this->title;
        }

        $withBrand = $this->title . ' — ' . $brand;

        return mb_strlen($withBrand) <= 60 ? $withBrand : $this->title;
    }

    /** @return list<string> problems worth failing a build or a publish over */
    public function audit(): array
    {
        $problems = $this->problems;

        if ($this->title === '') {
            $problems[] = 'title is empty';
        }
        if (mb_strlen($this->documentTitle()) > 60) {
            $problems[] = sprintf('title is %d characters, over the 60 limit', mb_strlen($this->documentTitle()));
        }
        if ($this->description === '') {
            $problems[] = 'meta description is empty';
        }
        if (mb_strlen($this->description) > 160) {
            $problems[] = sprintf('meta description is %d characters, over the 160 limit', mb_strlen($this->description));
        }
        if (!str_starts_with($this->canonical(), 'https://') && !Config::debug()) {
            $problems[] = 'canonical is not https';
        }

        return $problems;
    }

    public function renderHead(): string
    {
        if (Config::debug() && ($problems = $this->audit()) !== []) {
            error_log('SEO: ' . $this->path . ' — ' . implode('; ', $problems));
        }

        $canonical = $this->canonical();
        $image = $this->image !== null ? Config::url($this->image) : Config::url('/assets/img/og-default.png');
        $out = [];

        $out[] = '<title>' . e($this->documentTitle()) . '</title>';
        $out[] = '<meta name="description" content="' . e($this->description) . '">';
        $out[] = '<link rel="canonical" href="' . e($canonical) . '">';
        $out[] = '<meta name="robots" content="' . e($this->robots) . '">';

        $out[] = '<meta property="og:type" content="' . e($this->type) . '">';
        $out[] = '<meta property="og:title" content="' . e($this->documentTitle()) . '">';
        $out[] = '<meta property="og:description" content="' . e($this->description) . '">';
        $out[] = '<meta property="og:url" content="' . e($canonical) . '">';
        $out[] = '<meta property="og:site_name" content="' . e(Config::siteName()) . '">';
        $out[] = '<meta property="og:image" content="' . e($image) . '">';
        if ($this->imageAlt !== null) {
            $out[] = '<meta property="og:image:alt" content="' . e($this->imageAlt) . '">';
        }
        $out[] = '<meta property="og:locale" content="' . e(str_replace('-', '_', Locale::hreflang(Locale::active()))) . '">';
        $out[] = '<meta name="twitter:card" content="summary_large_image">';
        $out[] = '<meta name="twitter:title" content="' . e($this->documentTitle()) . '">';
        $out[] = '<meta name="twitter:description" content="' . e($this->description) . '">';
        $out[] = '<meta name="twitter:image" content="' . e($image) . '">';

        if ($this->publishedAt !== null) {
            $out[] = '<meta property="article:published_time" content="' . e(self::iso($this->publishedAt)) . '">';
        }
        if ($this->updatedAt !== null) {
            $out[] = '<meta property="article:modified_time" content="' . e(self::iso($this->updatedAt)) . '">';
        }

        foreach ($this->alternates as $locale => $path) {
            $out[] = '<link rel="alternate" hreflang="' . e(Locale::hreflang($locale)) . '" href="' . e(Config::url($path)) . '">';
        }
        if ($this->alternates !== []) {
            $default = $this->alternates['en'] ?? reset($this->alternates);
            $out[] = '<link rel="alternate" hreflang="x-default" href="' . e(Config::url((string) $default)) . '">';
        }

        foreach ($this->documents() as $document) {
            $out[] = '<script type="application/ld+json">'
                . json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>';
        }

        return implode("\n    ", $out);
    }

    /** @return list<array<string,mixed>> */
    public function documents(): array
    {
        $documents = $this->jsonLd;

        if ($this->breadcrumbs !== []) {
            $items = [];
            foreach ($this->breadcrumbs as $position => $crumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => $crumb['name'],
                    'item' => Config::url($crumb['path']),
                ];
            }
            $documents[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        return $documents;
    }

    public static function iso(string $stored): string
    {
        $timestamp = strtotime($stored . ' UTC');

        return $timestamp === false ? $stored : gmdate('c', $timestamp);
    }
}
