<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config;
use App\Locale;
use App\Repository\PostRepository;
use App\Response;
use App\Seo;
use App\Support\Str;
use App\View;

final class BlogController
{
    private const PER_PAGE = 12;

    public function index(int $page = 1): Response
    {
        $page = max(1, $page);
        $posts = PostRepository::published(null, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        $total = PostRepository::countPublished();

        $seo = (new Seo(Locale::path('blog')))
            ->title(t('ui.blog.index'))
            ->description(t('ui.blog.index.intro'))
            ->alternates($this->localeAlternates('blog'))
            ->breadcrumbs([
                ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
                ['name' => t('ui.blog.index'), 'path' => Locale::path('blog')],
            ])
            ->addJsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'Blog',
                'name' => t('ui.blog.index') . ' — ' . Config::siteName(),
                'url' => Config::url(Locale::path('blog')),
                'inLanguage' => Locale::hreflang(Locale::active()),
            ]);

        return Response::html(View::page('blog/index', [
            'posts' => $posts,
            'categories' => PostRepository::categories(),
            'page' => $page,
            'pages' => (int) ceil(max(1, $total) / self::PER_PAGE),
            'intro' => t('ui.blog.index.intro'),
        ], $seo));
    }

    public function category(string $slug): ?Response
    {
        $category = PostRepository::category($slug);
        if ($category === null || $category['slug'] === PostRepository::PAGE_CATEGORY) {
            return null;
        }

        $posts = PostRepository::published(null, 50, 0, $slug);
        $path = Locale::path('blog/category/' . $slug);

        $seo = (new Seo($path))
            ->title((string) $category['name'])
            ->description((string) ($category['description'] ?: t('ui.blog.index.intro')))
            ->breadcrumbs([
                ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
                ['name' => t('ui.blog.index'), 'path' => Locale::path('blog')],
                ['name' => (string) $category['name'], 'path' => $path],
            ]);

        return Response::html(View::page('blog/index', [
            'posts' => $posts,
            'categories' => PostRepository::categories(),
            'page' => 1,
            'pages' => 1,
            'intro' => (string) $category['description'],
        ], $seo));
    }

    public function tag(string $slug): ?Response
    {
        $tag = PostRepository::tag($slug);
        if ($tag === null) {
            return null;
        }

        $posts = PostRepository::publishedByTag($slug);
        if ($posts === []) {
            // An empty archive is a thin page, and a thin page indexed is
            // worse than no page.
            return null;
        }

        $path = Locale::path('blog/tag/' . $slug);

        $seo = (new Seo($path))
            ->title((string) $tag['name'])
            ->description(t('ui.blog.tag.intro', ['tag' => (string) $tag['name']]))
            ->breadcrumbs([
                ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
                ['name' => t('ui.blog.index'), 'path' => Locale::path('blog')],
                ['name' => (string) $tag['name'], 'path' => $path],
            ]);

        return Response::html(View::page('blog/index', [
            'posts' => $posts,
            'categories' => PostRepository::categories(),
            'page' => 1,
            'pages' => 1,
            'intro' => t('ui.blog.tag.intro', ['tag' => (string) $tag['name']]),
        ], $seo));
    }

    public function show(string $slug): ?Response
    {
        $post = PostRepository::findBySlug($slug);
        if ($post === null) {
            return null;
        }

        $path = Locale::path('blog/' . $slug);
        $translations = PostRepository::translations($post);
        $alternates = [];
        foreach ($translations as $locale => $translatedSlug) {
            $alternates[$locale] = Locale::path('blog/' . $translatedSlug, $locale);
        }

        $crumbs = [
            ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
            ['name' => t('ui.blog.index'), 'path' => Locale::path('blog')],
        ];
        if (!empty($post['category_slug'])) {
            $crumbs[] = [
                'name' => (string) $post['category_name'],
                'path' => Locale::path('blog/category/' . $post['category_slug']),
            ];
        }
        $crumbs[] = ['name' => (string) $post['title'], 'path' => $path];

        $seo = (new Seo($path))
            ->title((string) $post['title'])
            ->h1((string) $post['h1'])
            ->description((string) $post['meta_description'])
            ->type('article')
            ->image($post['cover_image'] ?: null, $post['image_alt'] ?: null)
            ->dates((string) $post['published_at'], (string) $post['updated_at'])
            ->alternates($alternates)
            ->breadcrumbs($crumbs)
            ->addJsonLd($this->articleSchema($post, $path));

        $faq = $this->extractFaq((string) $post['body']);
        if ($faq !== []) {
            $seo->addJsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faq,
            ]);
        }

        return Response::html(View::page('blog/post', [
            'post' => $post,
            'toc' => $this->tableOfContents((string) $post['body']),
            'body' => $this->addHeadingIds((string) $post['body']),
            'tags' => PostRepository::tagsFor((int) $post['id']),
            'related' => PostRepository::related($post),
        ], $seo));
    }

    private function articleSchema(array $post, string $path): array
    {
        $author = [
            '@type' => 'Person',
            'name' => (string) ($post['author_name'] ?? Config::get('organisation', Config::siteName())),
        ];
        if (!empty($post['author_credentials'])) {
            $author['jobTitle'] = (string) $post['author_credentials'];
        }
        if (!empty($post['author_url'])) {
            $author['url'] = (string) $post['author_url'];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string) $post['title'],
            'description' => (string) $post['meta_description'],
            'inLanguage' => Locale::hreflang((string) $post['locale']),
            'datePublished' => Seo::iso((string) $post['published_at']),
            'dateModified' => Seo::iso((string) $post['updated_at']),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => Config::url($path)],
            'author' => $author,
            'publisher' => [
                '@type' => 'Organization',
                'name' => (string) Config::get('organisation', Config::siteName()),
                'url' => Config::url('/'),
            ],
        ];
    }

    /** @return list<array{level:int,id:string,text:string}> */
    private function tableOfContents(string $html): array
    {
        if (preg_match_all('~<h([23])[^>]*>(.*?)</h\1>~is', $html, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $items = [];
        foreach ($matches as $match) {
            $text = trim(strip_tags($match[2]));
            if ($text === '') {
                continue;
            }
            $items[] = ['level' => (int) $match[1], 'id' => Str::slug($text), 'text' => $text];
        }

        return count($items) >= 3 ? $items : [];
    }

    /** Give every heading a stable id so the contents list and deep links work. */
    private function addHeadingIds(string $html): string
    {
        return (string) preg_replace_callback(
            '~<h([23])(\s[^>]*)?>(.*?)</h\1>~is',
            static function (array $match): string {
                $text = trim(strip_tags($match[3]));
                $attributes = $match[2] ?? '';
                if (stripos($attributes, 'id=') !== false) {
                    return $match[0];
                }

                return sprintf('<h%s%s id="%s">%s</h%s>', $match[1], $attributes, Str::slug($text), $match[3], $match[1]);
            },
            $html
        );
    }

    /**
     * FAQ schema is taken from the article's own "Common questions" section, so
     * the structured data can never describe content that is not on the page.
     *
     * @return list<array<string,mixed>>
     */
    private function extractFaq(string $html): array
    {
        if (preg_match('~<h2[^>]*>\s*(Common questions|عام سوالات)\s*</h2>(.*)$~is', $html, $match) !== 1) {
            return [];
        }

        if (preg_match_all('~<h3[^>]*>(.*?)</h3>\s*(.*?)(?=<h[23]|$)~is', $match[2], $pairs, PREG_SET_ORDER) === false) {
            return [];
        }

        $entities = [];
        foreach ($pairs as $pair) {
            $question = trim(strip_tags($pair[1]));
            $answer = trim(strip_tags($pair[2]));
            if ($question === '' || $answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }

        return $entities;
    }

    /** @return array<string,string> */
    private function localeAlternates(string $path): array
    {
        $alternates = [];
        foreach (Locale::enabled() as $code) {
            $alternates[$code] = Locale::path($path, $code);
        }

        return $alternates;
    }
}
