<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth;
use App\Config;
use App\Csrf;
use App\CsvImporter;
use App\Database;
use App\Locale;
use App\PostValidator;
use App\Repository\PostRepository;
use App\Response;
use App\Seo;
use App\Support\Str;
use App\View;

final class AdminController
{
    public function dashboard(): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $counts = [
            'published' => (int) Database::value('SELECT COUNT(*) FROM posts WHERE status = ?', ['published']),
            'drafts' => (int) Database::value('SELECT COUNT(*) FROM posts WHERE status <> ?', ['published']),
            'reports' => (int) Database::value('SELECT COUNT(*) FROM error_reports WHERE status = ?', ['open']),
            'calculations' => (int) Database::value('SELECT COUNT(*) FROM calculator_events WHERE event = ?', ['completed']),
        ];

        $popular = Database::all(
            'SELECT heir_signature, COUNT(*) AS uses FROM calculator_events
             WHERE event = ? AND heir_signature <> ?
             GROUP BY heir_signature ORDER BY uses DESC LIMIT 10',
            ['completed', '']
        );

        return $this->render('admin/dashboard', 'Dashboard', [
            'counts' => $counts,
            'popular' => $popular,
            'recent' => PostRepository::adminList(8),
        ]);
    }

    public function loginForm(?string $error = null): Response
    {
        if (Auth::check()) {
            return Response::redirect('/admin');
        }

        $seo = (new Seo('/admin/login'))->title('Sign in')->description('Administrator sign in.')->noindex();

        return Response::html(View::page('admin/login', ['error' => $error], $seo, 'layout/admin'));
    }

    public function login(array $form): Response
    {
        if (!Csrf::check($form['_token'] ?? null)) {
            return $this->loginForm('Your session expired. Try again.');
        }

        $email = trim((string) ($form['email'] ?? ''));
        $password = (string) ($form['password'] ?? '');

        if (!Auth::attempt($email, $password)) {
            return $this->loginForm('Those details did not match an account.');
        }

        return Response::redirect('/admin');
    }

    public function logout(array $form): Response
    {
        if (Csrf::check($form['_token'] ?? null)) {
            Auth::logout();
        }

        return Response::redirect('/admin/login');
    }

    public function posts(array $query): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $locale = isset($query['locale']) && Locale::exists((string) $query['locale']) ? (string) $query['locale'] : null;

        return $this->render('admin/posts', 'Posts', [
            'posts' => PostRepository::adminList(100, 0, $locale),
            'locale' => $locale,
        ]);
    }

    public function edit(?int $id, array $errors = [], array $warnings = [], array $values = []): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $post = $id !== null ? PostRepository::findById($id) : null;
        if ($id !== null && $post === null) {
            return (new PageController())->notFound();
        }

        return $this->render('admin/edit', $post === null ? 'New post' : 'Edit post', [
            'post' => $values !== [] ? $values + (array) $post : $post,
            'id' => $id,
            'errors' => $errors,
            'warnings' => $warnings,
            'categories' => Database::all('SELECT * FROM categories ORDER BY locale, position, name'),
            'authors' => Database::all('SELECT * FROM authors ORDER BY name'),
        ]);
    }

    public function save(?int $id, array $form): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }
        if (!Csrf::check($form['_token'] ?? null)) {
            return $this->edit($id, ['Your session expired. Nothing was saved.'], [], $form);
        }

        $title = trim((string) ($form['title'] ?? ''));
        $body = (string) ($form['body'] ?? '');

        $post = [
            'slug' => trim((string) ($form['slug'] ?? '')) ?: Str::slug($title),
            'locale' => Locale::exists((string) ($form['locale'] ?? 'en')) ? (string) $form['locale'] : 'en',
            'title' => $title,
            // Defaulting the H1 to the title is what stops the two drifting
            // apart without anyone noticing.
            'h1' => trim((string) ($form['h1'] ?? '')) ?: $title,
            'meta_description' => trim((string) ($form['meta_description'] ?? '')),
            'excerpt' => trim((string) ($form['excerpt'] ?? '')) ?: Str::excerpt($body),
            'body' => $body,
            'cover_image' => trim((string) ($form['cover_image'] ?? '')) ?: null,
            'image_alt' => trim((string) ($form['image_alt'] ?? '')) ?: null,
            'category_id' => ((int) ($form['category_id'] ?? 0)) ?: null,
            'author_id' => ((int) ($form['author_id'] ?? 0)) ?: null,
            'status' => in_array($form['status'] ?? 'draft', ['draft', 'scheduled', 'published'], true)
                ? (string) $form['status']
                : 'draft',
            'target_keyword' => trim((string) ($form['target_keyword'] ?? '')) ?: null,
            'translation_group' => trim((string) ($form['translation_group'] ?? '')) ?: null,
            'reading_minutes' => Str::readingMinutes($body),
            'published_at' => trim((string) ($form['published_at'] ?? '')) ?: Database::now(),
        ];

        $check = PostValidator::check($post, $id);

        // A draft can be saved with problems; publishing cannot.
        if ($check['errors'] !== [] && $post['status'] === 'published') {
            return $this->edit($id, $check['errors'], $check['warnings'], $post);
        }

        $newId = PostRepository::save($post, $id);

        return Response::redirect('/admin/posts/' . $newId . '?saved=1');
    }

    public function importForm(array $result = [], ?string $error = null): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        return $this->render('admin/import', 'Import posts', ['result' => $result, 'error' => $error]);
    }

    public function import(array $form, ?array $file): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }
        if (!Csrf::check($form['_token'] ?? null)) {
            return $this->importForm([], 'Your session expired. Nothing was imported.');
        }
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->importForm([], 'Choose a CSV file to upload.');
        }

        $result = CsvImporter::import((string) $file['tmp_name'], !empty($form['dry_run']));

        return $this->importForm($result);
    }

    public function reports(): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        return $this->render('admin/reports', 'Reported errors', [
            'reports' => Database::all('SELECT * FROM error_reports ORDER BY created_at DESC LIMIT 200'),
        ]);
    }

    /**
     * Every SEO problem on the site, on one page.
     *
     * The same rules that block a publish are re-run across everything already
     * published, so a problem introduced by an edit elsewhere — a renamed
     * slug leaving a dead link behind — surfaces here rather than sitting
     * unnoticed.
     */
    public function audit(): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $findings = [];
        foreach (PostRepository::adminList(500) as $post) {
            $check = PostValidator::check($post, (int) $post['id']);
            if ($check['errors'] === [] && $check['warnings'] === []) {
                continue;
            }
            $findings[] = [
                'post' => $post,
                'errors' => $check['errors'],
                'warnings' => $check['warnings'],
            ];
        }

        return $this->render('admin/audit', 'SEO audit', [
            'findings' => $findings,
            'checked' => count(PostRepository::adminList(500)),
        ]);
    }

    private function guard(): ?Response
    {
        return Auth::check() ? null : Response::redirect('/admin/login');
    }

    private function render(string $template, string $title, array $data): Response
    {
        $seo = (new Seo('/admin'))->title($title)->description('Administration')->noindex();

        return Response::html(View::page($template, $data, $seo, 'layout/admin'));
    }
}
