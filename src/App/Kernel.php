<?php

declare(strict_types=1);

namespace App;

use App\Controller\AdminController;
use App\Controller\BlogController;
use App\Controller\CalculatorController;
use App\Controller\FeedController;
use App\Controller\PageController;

/** Boots the application and holds the route table. */
final class Kernel
{
    /** Trust pages, served from the reserved "pages" category. */
    public const TRUST_PAGES = ['about', 'methodology', 'sources', 'disclaimer', 'contact'];

    public static function boot(?string $configPath = null): void
    {
        Config::load($configPath);
        date_default_timezone_set('UTC');

        if (Config::debug()) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }
    }

    public static function handle(string $method, string $uri, array $query, array $post): Response
    {
        if (!Config::isInstalled()) {
            return Response::redirect('/install.php');
        }

        $router = self::routes($query, $post);

        try {
            $response = $router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            error_log('Request failed: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

            if (Config::debug()) {
                throw $e;
            }

            return (new PageController())->serverError();
        }

        if ($response instanceof Response) {
            return $response;
        }

        return (new PageController())->notFound();
    }

    private static function routes(array $query, array $post): Router
    {
        $router = new Router();
        $calculator = new CalculatorController();
        $blog = new BlogController();
        $pages = new PageController();
        $feed = new FeedController();
        $admin = new AdminController();

        $router->get('/', static fn () => $calculator->index());
        $router->post('/', static fn () => $calculator->index($post, true));
        $router->post('/api/calculate', static fn () => $calculator->api($post));
        $router->post('/api/event', static fn () => $calculator->recordInterfaceEvent($post));

        $router->get('/report', static fn () => $calculator->reportForm());
        $router->post('/report', static fn () => $calculator->submitReport($post));

        $router->get('/blog', static fn () => $blog->index((int) ($query['page'] ?? 1)));
        $router->get('/blog/category/{slug}', static fn (string $slug) => $blog->category($slug));
        $router->get('/blog/tag/{slug}', static fn (string $slug) => $blog->tag($slug));
        $router->get('/blog/{slug}', static fn (string $slug) => $blog->show($slug));

        foreach (self::TRUST_PAGES as $slug) {
            $router->get('/' . $slug, static fn () => $pages->show($slug));
        }

        $router->get('/sitemap.xml', static fn () => $feed->sitemap());
        $router->get('/robots.txt', static fn () => $feed->robots());

        $router->get('/admin', static fn () => $admin->dashboard());
        $router->get('/admin/login', static fn () => $admin->loginForm());
        $router->post('/admin/login', static fn () => $admin->login($post));
        $router->post('/admin/logout', static fn () => $admin->logout($post));
        $router->get('/admin/posts', static fn () => $admin->posts($query));
        $router->get('/admin/posts/new', static fn () => $admin->edit(null));
        $router->get('/admin/posts/{id}', static fn (string $id) => $admin->edit((int) $id));
        $router->post('/admin/posts/{id}', static fn (string $id) => $admin->save((int) $id, $post));
        $router->post('/admin/posts', static fn () => $admin->save(null, $post));
        $router->get('/admin/import', static fn () => $admin->importForm());
        $router->post('/admin/import', static fn () => $admin->import($post, $_FILES['file'] ?? null));
        $router->get('/admin/reports', static fn () => $admin->reports());
        $router->get('/admin/audit', static fn () => $admin->audit());
        $router->get('/admin/keywords', static fn () => $admin->keywords());
        $router->post('/admin/posts/{id}/delete', static fn (string $id) => $admin->delete((int) $id, $post));

        return $router;
    }
}
