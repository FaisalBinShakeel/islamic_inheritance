<?php

declare(strict_types=1);

namespace Faraid\Tests;

use App\Locale;
use App\ResultPresenter;
use App\Translator;
use Faraid\Calculator;
use Faraid\Tests\Support\CaseRegistry;
use Faraid\Tests\Support\SiteFixture;
use PHPUnit\Framework\TestCase;

/**
 * The engine speaks only in reason keys. If one of those keys has no
 * translation, a reader sees a raw identifier where an explanation should be —
 * so every key the engine can actually produce is checked against every live
 * locale, by running the whole sourced case suite through the presenter.
 */
final class TranslationTest extends TestCase
{
    protected function setUp(): void
    {
        SiteFixture::boot();
    }

    public function testEveryReasonKeyTheEngineProducesHasATranslation(): void
    {
        $calculator = new Calculator();
        $missing = [];

        foreach (Locale::enabled() as $locale) {
            Locale::setActive($locale);

            foreach (CaseRegistry::all() as $case) {
                if ($case->expectError !== null) {
                    continue;
                }

                $result = $calculator->calculate($case->input);

                foreach ($result->shares as $share) {
                    if (!Translator::has('reason.' . $share->reasonKey, $locale)) {
                        $missing[] = $locale . ': reason.' . $share->reasonKey;
                    }
                }
                foreach ($result->excluded as $exclusion) {
                    if (!Translator::has('exclusion.' . $exclusion->reasonKey, $locale)) {
                        $missing[] = $locale . ': exclusion.' . $exclusion->reasonKey;
                    }
                }
                foreach ($result->warnings as $warning) {
                    if (!Translator::has('warning.' . $warning, $locale)) {
                        $missing[] = $locale . ': warning.' . $warning;
                    }
                }
            }
        }

        self::assertSame([], array_values(array_unique($missing)));
    }

    public function testEveryHeirHasANameInEveryLocale(): void
    {
        $missing = [];

        foreach (Locale::enabled() as $locale) {
            foreach (\Faraid\HeirType::cases() as $heir) {
                if (!Translator::has('heir.' . $heir->value, $locale)) {
                    $missing[] = $locale . ': heir.' . $heir->value;
                }
            }
        }

        self::assertSame([], $missing);
    }

    public function testTheLocalesCarryTheSameKeys(): void
    {
        $english = array_keys(Translator::load('en'));

        foreach (Locale::enabled() as $locale) {
            if ($locale === 'en') {
                continue;
            }
            $missing = array_diff($english, array_keys(Translator::load($locale)));
            self::assertSame([], array_values($missing), sprintf('%s is missing strings.', $locale));
        }
    }

    public function testAResultRendersWithRealSentencesInUrdu(): void
    {
        Locale::setActive('ur');

        $result = (new Calculator())->calculate([
            'madhhab' => 'hanafi',
            'deceased_gender' => 'male',
            'heirs' => ['wives' => 1, 'sons' => 2, 'daughters' => 3, 'mother' => true],
        ]);

        $presented = ResultPresenter::present($result, 'ur');

        foreach ($presented['shares'] as $row) {
            self::assertNotSame('', $row['reason']);
            self::assertStringNotContainsString('reason.', $row['reason']);
            self::assertStringNotContainsString('heir.', $row['label']);
        }
    }
}
