<?php

declare(strict_types=1);

namespace Faraid\Tests;

use App\Locale;
use App\SchoolComparison;
use Faraid\Madhhab\MadhhabRules;
use Faraid\Tests\Support\CaseRegistry;
use Faraid\Tests\Support\SiteFixture;
use PHPUnit\Framework\TestCase;

/**
 * The comparison panel is what lets the calculator report positions instead of
 * issuing a ruling, so what it groups together and what it marks as disputed
 * has to be exactly right. Calling a genuine disagreement "unanimous" would be
 * worse than not showing the panel at all.
 */
final class ComparisonTest extends TestCase
{
    protected function setUp(): void
    {
        SiteFixture::boot();
        Locale::setActive('en');
    }

    public function testAnOrdinaryEstateIsUnanimous(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'male',
            'heirs' => ['wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3],
        ]);

        self::assertTrue($comparison['unanimous']);
        self::assertCount(1, $comparison['groups']);
        self::assertSame(MadhhabRules::keys(), $comparison['groups'][0]['schools']);

        foreach ($comparison['rows'] as $row) {
            self::assertFalse($row['differs'], $row['heir'] . ' should not differ');
        }
    }

    public function testTheGrandfatherQuestionSplitsThemTwoWays(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'male',
            'heirs' => ['paternal_grandfather' => true, 'full_brothers' => 2],
        ]);

        self::assertFalse($comparison['unanimous']);
        self::assertCount(2, $comparison['groups']);

        $groups = array_map(static fn (array $g) => $g['schools'], $comparison['groups']);
        self::assertContains(['hanafi', 'ahl_e_hadith'], $groups);
        self::assertContains(['shafii', 'maliki', 'hanbali'], $groups);

        $grandfather = self::row($comparison, 'paternal_grandfather');
        self::assertTrue($grandfather['differs']);
        self::assertSame('1', $grandfather['by_school']['hanafi']);
        self::assertSame('1/3', $grandfather['by_school']['shafii']);
    }

    public function testMushtarakaSplitsThemThreeToTwo(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'female',
            'heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2],
        ]);

        self::assertFalse($comparison['unanimous']);

        $groups = array_map(static fn (array $g) => $g['schools'], $comparison['groups']);
        self::assertContains(['hanafi', 'hanbali', 'ahl_e_hadith'], $groups);
        self::assertContains(['shafii', 'maliki'], $groups);

        // An heir who inherits under only some positions must still appear,
        // with "nothing" recorded against the rest.
        $brothers = self::row($comparison, 'full_brother');
        self::assertNull($brothers['by_school']['hanafi']);
        self::assertSame('1/6', $brothers['by_school']['shafii']);
        self::assertTrue($brothers['differs']);
    }

    public function testASoleSurvivingSpouseTakesOnlyTheFixedShareEverywhere(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'male',
            'heirs' => ['wives' => 1],
        ]);

        self::assertTrue($comparison['unanimous']);

        $wife = self::row($comparison, 'wife');
        foreach (MadhhabRules::keys() as $position) {
            self::assertSame('1/4', $wife['by_school'][$position], $position);
        }
    }

    /**
     * On every point this engine models, the Ahl-e-Hadith option now lands on
     * the same figures as Hanafi. That is a finding, not a coincidence to be
     * papered over: an earlier version differed on radd to a sole spouse, and
     * correcting it against Ibn Qudamah removed the last divergence.
     *
     * If a reviewer changes any of the four choices in AhlAlHadith, this test
     * fails and the claim in the guide has to be rewritten with it.
     */
    public function testAhlEHadithCurrentlyCoincidesWithHanafiThroughout(): void
    {
        $calculator = new \Faraid\Calculator();
        $divergent = [];

        foreach (CaseRegistry::all() as $case) {
            if ($case->expectError !== null) {
                continue;
            }

            $input = $case->input;
            unset($input['madhhab']);

            $hanafi = $calculator->calculate(['madhhab' => 'hanafi'] + $input);
            $ahl = $calculator->calculate(['madhhab' => 'ahl_e_hadith'] + $input);

            if (self::fingerprint($hanafi) !== self::fingerprint($ahl)) {
                $divergent[] = $case->id;
            }
        }

        self::assertSame([], $divergent);
    }

    private static function fingerprint(\Faraid\Result $result): string
    {
        $parts = [];
        foreach ($result->shares as $share) {
            $parts[] = $share->heir->value . '=' . (string) $share->share;
        }
        sort($parts);

        return implode('|', $parts) . '#' . (string) $result->undistributed;
    }

    public function testEveryRowCoversEveryPosition(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'female',
            'heirs' => ['husband' => 1, 'paternal_grandfather' => true, 'full_sisters' => 1],
        ]);

        foreach ($comparison['rows'] as $row) {
            self::assertSame(
                MadhhabRules::keys(),
                array_keys($row['by_school']),
                $row['heir'] . ' is missing a position'
            );
        }
    }

    public function testAhlEHadithAlwaysCarriesItsUnverifiedNotice(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'male',
            'heirs' => ['sons' => 1, 'wives' => 1],
        ]);

        self::assertArrayHasKey('ahl_e_hadith', $comparison['notes']);
        self::assertNotSame([], $comparison['notes']['ahl_e_hadith']);

        $notice = implode(' ', $comparison['notes']['ahl_e_hadith']);
        self::assertStringContainsString('NOT been checked', $notice);
    }

    public function testBadInputIsReportedPerPositionRatherThanCrashing(): void
    {
        $comparison = SchoolComparison::compare([
            'deceased_gender' => 'male',
            'heirs' => ['husband' => 1, 'wives' => 1],
        ]);

        self::assertSame([], $comparison['rows']);
        self::assertCount(count(MadhhabRules::keys()), $comparison['errors']);
    }

    /** @return array{heir:string,label:string,by_school:array<string,?string>,differs:bool} */
    private static function row(array $comparison, string $heir): array
    {
        foreach ($comparison['rows'] as $row) {
            if ($row['heir'] === $heir) {
                return $row;
            }
        }

        self::fail(sprintf('No row for %s.', $heir));
    }
}
