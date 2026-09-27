<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config;
use App\Locale;
use App\Repository\PostRepository;
use App\Response;
use App\Seo;
use App\View;

final class PageController
{
    /** Trust pages: About, Methodology, Sources, Disclaimer, Contact. */
    public function show(string $slug): ?Response
    {
        $page = PostRepository::findBySlug($slug);
        if ($page === null || ($page['category_slug'] ?? null) !== PostRepository::PAGE_CATEGORY) {
            return null;
        }

        $path = Locale::path($slug);
        $alternates = [];
        foreach (PostRepository::translations($page) as $locale => $translatedSlug) {
            $alternates[$locale] = Locale::path($translatedSlug, $locale);
        }

        $seo = (new Seo($path))
            ->title((string) $page['title'])
            ->h1((string) $page['h1'])
            ->description((string) $page['meta_description'])
            ->dates((string) $page['published_at'], (string) $page['updated_at'])
            ->alternates($alternates)
            ->breadcrumbs([
                ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
                ['name' => (string) $page['title'], 'path' => $path],
            ]);

        if ($slug === 'about') {
            $seo->addJsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => (string) Config::get('organisation', Config::siteName()),
                'url' => Config::url('/'),
                'email' => (string) Config::get('contact_email', ''),
            ]);
        }

        return Response::html(View::page('pages/page', ['page' => $page], $seo));
    }

    public function notFound(): Response
    {
        $seo = (new Seo(Locale::path('404')))
            ->title(t('ui.error.404.title'))
            ->description(t('ui.error.404.body'))
            ->noindex();

        return Response::html(
            View::page('pages/message', [
                'message' => t('ui.error.404.body'),
                'link' => ['href' => Locale::path(), 'label' => t('ui.calculator')],
            ], $seo),
            404
        );
    }

    public function serverError(): Response
    {
        $seo = (new Seo(Locale::path('error')))
            ->title(t('ui.error.500.title'))
            ->description(t('ui.error.500.body'))
            ->noindex();

        return Response::html(
            View::page('pages/message', [
                'message' => t('ui.error.500.body'),
                'link' => ['href' => Locale::path(), 'label' => t('ui.calculator')],
            ], $seo),
            500
        );
    }
}
