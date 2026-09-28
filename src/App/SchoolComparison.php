<?php

declare(strict_types=1);

namespace App;

use Faraid\Calculator;
use Faraid\Exception\InvalidInput;
use Faraid\Madhhab\MadhhabRules;
use Faraid\Result;

/**
 * The same heirs, run through all four Sunni schools at once.
 *
 * This changes what the calculator is claiming. Rather than presenting one
 * answer as the answer, it reports what each school holds and says plainly
 * where they agree and where they part company. On the overwhelming majority
 * of estates all four land on the same figures, and saying so is worth as
 * much to a family as showing the differences in the few cases where they
 * do not.
 */
final class SchoolComparison
{
    /**
     * @param array $engineInput the engine contract, with or without a madhhab
     *
     * @return array{
     *     unanimous:bool,
     *     schools:list<string>,
     *     groups:list<array{schools:list<string>,labels:list<string>,signature:string}>,
     *     rows:list<array{heir:string,label:string,by_school:array<string,?string>,differs:bool}>,
     *     notes:array<string,list<string>>,
     *     errors:array<string,string>
     * }
     */
    public static function compare(array $engineInput, ?string $locale = null): array
    {
        $locale = $locale ?? Locale::active();
        $calculator = new Calculator();

        /** @var array<string,Result> $results */
        $results = [];
        $errors = [];

        foreach (MadhhabRules::keys() as $school) {
            try {
                $results[$school] = $calculator->calculate(['madhhab' => $school] + $engineInput);
            } catch (InvalidInput $e) {
                $errors[$school] = $e->getMessage();
            } catch (\Throwable $e) {
                error_log('Comparison failed for ' . $school . ': ' . $e->getMessage());
                $errors[$school] = $e->getMessage();
            }
        }

        if ($results === []) {
            return [
                'unanimous' => false, 'schools' => [], 'groups' => [],
                'rows' => [], 'notes' => [], 'errors' => $errors,
            ];
        }

        // Every heir who inherits under at least one school, in the order the
        // first school lists them so the table reads the same way as the main
        // result above it.
        $heirOrder = [];
        foreach ($results as $result) {
            foreach ($result->shares as $share) {
                $key = $share->via === null ? $share->heir->value : $share->heir->value . '@' . $share->via;
                if (!isset($heirOrder[$key])) {
                    $heirOrder[$key] = ['heir' => $share->heir->value, 'count' => $share->count];
                }
            }
        }

        $rows = [];
        foreach ($heirOrder as $key => $meta) {
            $bySchool = [];
            $seen = [];
            foreach ($results as $school => $result) {
                $fraction = null;
                foreach ($result->shares as $share) {
                    $shareKey = $share->via === null ? $share->heir->value : $share->heir->value . '@' . $share->via;
                    if ($shareKey === $key) {
                        $fraction = (string) $share->share;
                        break;
                    }
                }
                $bySchool[$school] = $fraction;
                $seen[$fraction ?? '—'] = true;
            }

            $rows[] = [
                'heir' => $meta['heir'],
                'label' => ResultPresenter::heirLabel($meta['heir'], $meta['count'], $locale),
                'by_school' => $bySchool,
                'differs' => count($seen) > 1,
            ];
        }

        // Schools that produce an identical distribution are grouped, so a
        // three-to-one split reads as one difference rather than four columns.
        $groups = [];
        foreach ($results as $school => $result) {
            $signature = self::signature($result);
            $index = null;
            foreach ($groups as $position => $group) {
                if ($group['signature'] === $signature) {
                    $index = $position;
                    break;
                }
            }
            if ($index === null) {
                $groups[] = ['schools' => [$school], 'signature' => $signature];
            } else {
                $groups[$index]['schools'][] = $school;
            }
        }

        foreach ($groups as $position => $group) {
            $groups[$position]['labels'] = array_map(
                static fn (string $school) => Translator::get('ui.madhhab.' . $school, [], $locale),
                $group['schools']
            );
        }

        // Notes a school raises about its own position — the standing points
        // of difference, and anything still under review.
        $notes = [];
        foreach ($results as $school => $result) {
            $schoolNotes = [];
            foreach ($result->warnings as $warning) {
                if (!str_starts_with($warning, 'madhhab_') && !str_contains($warning, 'under_review')) {
                    continue;
                }
                $schoolNotes[] = Translator::get('warning.' . $warning, [], $locale);
            }
            if ($schoolNotes !== []) {
                $notes[$school] = $schoolNotes;
            }
        }

        return [
            'unanimous' => count($groups) === 1,
            'schools' => array_keys($results),
            'groups' => array_values($groups),
            'rows' => $rows,
            'notes' => $notes,
            'errors' => $errors,
        ];
    }

    /** Two schools "agree" when every heir takes the same fraction. */
    private static function signature(Result $result): string
    {
        $parts = [];
        foreach ($result->shares as $share) {
            $key = $share->via === null ? $share->heir->value : $share->heir->value . '@' . $share->via;
            $parts[$key] = $key . '=' . (string) $share->share;
        }
        ksort($parts);

        return implode('|', $parts) . '#' . (string) $result->undistributed;
    }
}
