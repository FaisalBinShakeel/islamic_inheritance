<?php

declare(strict_types=1);

namespace Faraid\Tests;

use App\Kernel;
use App\Locale;
use App\Repository\PostRepository;
use App\PostValidator;
use Faraid\Tests\Support\SiteFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every published URL is fetched through the real kernel and checked against
 * the rules the specification asked to be enforced in the template layer:
 * exactly one H1, an https canonical, a meta description, no dead internal
 * link, and no untranslated string leaking into the page.
 */
final class SiteTest extends TestCase
{
    protected function setUp(): void
    {
        SiteFixture::boot();
        Locale::setActive('en');
    }

    #[DataProvider('publicUrls')]
    public function testUrlRendersCleanly(string $url): void
    {
        $response = Kernel::handle('GET', $url, [], []);

        self::assertSame(200, $response->status, $url . ' did not return 200');

        $html = $response->body;
        self::assertSame(1, substr_count($html, '<h1'), $url . ' does not have exactly one H1');
        self::assertMatchesRegularExpression('~<link rel="canonical" href="https://~', $html, $url . ' has no https canonical');
        self::assertMatchesRegularExpression('~<meta name="description" content="[^"]{20,}">~', $html, $url . ' has no usable meta description');
        // A missing string renders as its own key, so look for the key shape
        // rather than the word — "for that reason." is ordinary prose.
        self::assertDoesNotMatchRegularExpression(
            '~\b(?:ui|reason|exclusion|warning|heir)\.[a-z_]{3,}\b~',
            strip_tags($html),
            $url . ' leaked an untranslated key'
        );
        self::assertMatchesRegularExpression('~<html lang="[A-Za-z-]+" dir="(ltr|rtl)">~', $html, $url . ' has no lang/dir');
    }

    public function testUrduPagesAreRightToLeft(): void
    {
        $html = Kernel::handle('GET', '/ur/', [], [])->body;

        self::assertStringContainsString('dir="rtl"', $html);
        self::assertStringContainsString('lang="ur-PK"', $html);
    }

    public function testAnUnknownUrlIs404(): void
    {
        self::assertSame(404, Kernel::handle('GET', '/no-such-page', [], [])->status);
    }

    public function testTheCalculatorPostsAndRendersAResult(): void
    {
        $response = Kernel::handle('POST', '/', [], [
            'madhhab' => 'hanafi',
            'deceased_gender' => 'male',
            'wives' => '1',
            'mother' => '1',
            'sons' => '2',
            'daughters' => '3',
            'estate_value' => '5000000',
            'funeral' => '50000',
            'debts' => '200000',
        ]);

        self::assertSame(200, $response->status);
        self::assertStringContainsString('21/168', $response->body);
        self::assertStringContainsString('593,750', $response->body);
        // The disclaimer belongs on the result, not buried in the footer.
        self::assertStringContainsString('not a ruling', $response->body);
    }

    public function testTheApiReturnsJson(): void
    {
        $response = Kernel::handle('POST', '/api/calculate', [], [
            'madhhab' => 'hanafi', 'deceased_gender' => 'male', 'sons' => '1', 'wives' => '1',
        ]);

        $payload = json_decode($response->body, true);
        self::assertTrue($payload['ok']);
        self::assertStringContainsString('7/8', $payload['html']);
    }

    public function testTheSitemapListsEveryPublishedUrlWithARealLastmod(): void
    {
        $xml = Kernel::handle('GET', '/sitemap.xml', [], [])->body;

        self::assertStringStartsWith('<?xml', $xml);
        self::assertStringContainsString('<loc>https://example.test/</loc>', $xml);
        self::assertStringContainsString('<loc>https://example.test/ur/</loc>', $xml);

        foreach (PostRepository::sitemapEntries() as $entry) {
            self::assertStringContainsString('<loc>' . \App\Config::url($entry['loc']) . '</loc>', $xml);
        }

        self::assertDoesNotMatchRegularExpression('~<lastmod>1970~', $xml);
    }

    public function testRobotsPointsAtTheSitemapAndBlocksTheAdmin(): void
    {
        $robots = Kernel::handle('GET', '/robots.txt', [], [])->body;

        self::assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $robots);
        self::assertStringContainsString('Disallow: /admin', $robots);
    }

    public function testNoPublishedPostContainsADeadInternalLink(): void
    {
        $dead = [];

        foreach (PostRepository::adminList(500) as $post) {
            foreach (PostValidator::deadLinks((string) $post['body'], (string) $post['locale']) as $link) {
                $dead[] = $post['locale'] . '/' . $post['slug'] . ' → ' . $link;
            }
        }

        self::assertSame([], $dead);
    }

    public function testEveryGuideLinksToTheCalculator(): void
    {
        $without = [];

        foreach (PostRepository::adminList(500) as $post) {
            if (($post['category_slug'] ?? null) === PostRepository::PAGE_CATEGORY) {
                continue;
            }
            $home = Locale::path('', (string) $post['locale']);
            if (!str_contains((string) $post['body'], 'href="' . $home . '"')) {
                $without[] = $post['locale'] . '/' . $post['slug'];
            }
        }

        self::assertSame([], $without);
    }

    public function testNoTwoPostsTargetTheSamePrimaryKeyword(): void
    {
        $seen = [];

        foreach (PostRepository::adminList(500) as $post) {
            $keyword = (string) ($post['target_keyword'] ?? '');
            if ($keyword === '') {
                continue;
            }
            $key = $post['locale'] . '/' . $keyword;
            self::assertArrayNotHasKey($key, $seen, sprintf('"%s" is targeted twice.', $keyword));
            $seen[$key] = true;
        }
    }

    /**
     * A guard against the bug that made the entire Urdu site render as an
     * empty page: hiding something at left: -9999px is harmless left to right
     * and catastrophic right to left, because left overflow IS scrollable in
     * RTL. The document stretched to eleven thousand pixels wide and readers
     * landed on blank space.
     *
     * Visually-hidden content is clipped, not pushed off the side.
     */
    public function testTheStylesheetHidesNothingOffTheSideOfThePage(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__) . '/public/assets/css/site.css');
        // Strip comments, so the note explaining this rule does not trip it.
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);

        self::assertDoesNotMatchRegularExpression(
            '~(left|right|inset-inline-start|inset-inline-end|text-indent)\s*:\s*-\d{3,}~i',
            $css,
            'A large negative offset creates horizontal overflow on right-to-left pages. Clip instead.'
        );
    }

    /** @return iterable<string,array{string}> */
    public static function publicUrls(): iterable
    {
        SiteFixture::boot();

        $urls = ['/', '/blog', '/ur/', '/ur/blog'];

        foreach (Kernel::TRUST_PAGES as $page) {
            $urls[] = '/' . $page;
        }

        foreach (PostRepository::sitemapEntries() as $entry) {
            $urls[] = $entry['loc'];
        }

        foreach (array_unique($urls) as $url) {
            yield $url => [$url];
        }
    }
}
