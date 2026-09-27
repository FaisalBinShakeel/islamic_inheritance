<?php

declare(strict_types=1);

namespace Faraid\Tests;

use App\Config;
use App\Locale;
use App\Seo;
use Faraid\Tests\Support\SiteFixture;
use PHPUnit\Framework\TestCase;

/**
 * The four bugs the specification asked to be made structurally impossible,
 * checked as invariants rather than left to a publishing checklist.
 */
final class SeoTest extends TestCase
{
    protected function setUp(): void
    {
        SiteFixture::boot();
        Locale::setActive('en');
    }

    public function testCanonicalIsAlwaysBuiltFromTheConfiguredOrigin(): void
    {
        $seo = (new Seo('/blog/anything'))->title('T')->description('D');

        self::assertSame('https://example.test/blog/anything', $seo->canonical());
        self::assertStringContainsString('<link rel="canonical" href="https://example.test/blog/anything">', $seo->renderHead());
    }

    public function testCanonicalAndOgUrlAlwaysAgree(): void
    {
        $head = (new Seo('/about'))->title('About')->description('D')->renderHead();

        preg_match('~<link rel="canonical" href="([^"]+)">~', $head, $canonical);
        preg_match('~<meta property="og:url" content="([^"]+)">~', $head, $ogUrl);

        self::assertSame($canonical[1], $ogUrl[1]);
    }

    public function testHeadingDefaultsToTheTitle(): void
    {
        self::assertSame('Some title', (new Seo('/'))->title('Some title')->heading());
        self::assertSame('A fuller heading', (new Seo('/'))->title('Some title')->h1('A fuller heading')->heading());
    }

    public function testAuditCatchesAMissingDescription(): void
    {
        $problems = (new Seo('/'))->title('Fine')->description('')->audit();

        self::assertContains('meta description is empty', $problems);
    }

    public function testAuditCatchesAnOverlongTitle(): void
    {
        $problems = (new Seo('/'))
            ->title(str_repeat('long title ', 8))
            ->description('Fine')
            ->audit();

        self::assertNotSame([], array_filter($problems, static fn ($p) => str_contains($p, 'over the 60 limit')));
    }

    public function testBrandSuffixIsDroppedRatherThanBreakingTheLimit(): void
    {
        $long = 'A daughter\'s share in Islamic inheritance explained in full';
        $documentTitle = (new Seo('/'))->title($long)->description('D')->documentTitle();

        self::assertSame($long, $documentTitle);
        self::assertStringNotContainsString(Config::siteName(), $documentTitle);
    }

    public function testHreflangIsReciprocalAndCarriesAnXDefault(): void
    {
        $head = (new Seo('/'))
            ->title('T')->description('D')
            ->alternates(['en' => '/', 'ur' => '/ur/'])
            ->renderHead();

        self::assertStringContainsString('hreflang="en" href="https://example.test/"', $head);
        self::assertStringContainsString('hreflang="ur-PK" href="https://example.test/ur/"', $head);
        self::assertStringContainsString('hreflang="x-default"', $head);
    }

    public function testBreadcrumbsProduceBreadcrumbListSchema(): void
    {
        $documents = (new Seo('/blog/x'))
            ->title('T')->description('D')
            ->breadcrumbs([
                ['name' => 'Home', 'path' => '/'],
                ['name' => 'Guides', 'path' => '/blog'],
            ])
            ->documents();

        $types = array_column($documents, '@type');
        self::assertContains('BreadcrumbList', $types);

        $breadcrumbs = $documents[array_search('BreadcrumbList', $types, true)];
        self::assertSame('https://example.test/', $breadcrumbs['itemListElement'][0]['item']);
        self::assertSame(2, $breadcrumbs['itemListElement'][1]['position']);
    }
}
