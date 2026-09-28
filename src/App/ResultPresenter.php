<?php

declare(strict_types=1);

namespace App;

use Faraid\Result;
use Faraid\Share;

/**
 * Turns an engine Result into rows a template can print.
 *
 * The engine speaks only in reason keys; every sentence a reader sees is
 * produced here, which is why one calculation renders in English, Urdu or any
 * other locale without the engine knowing a language exists.
 */
final class ResultPresenter
{
    public static function present(Result $result, ?string $locale = null, string $currency = ''): array
    {
        $locale = $locale ?? Locale::active();
        $denominator = $result->denominator();

        $rows = [];
        foreach ($result->shares as $share) {
            $rows[] = [
                'heir' => $share->heir->value,
                'label' => self::heirLabel($share->heir->value, $share->count, $locale),
                'count' => $share->count,
                'fraction' => (string) $share->share,
                'over_denominator' => $denominator === 1
                    ? (string) $share->share->numeratorOver(1)
                    : $share->share->numeratorOver($denominator) . '/' . $denominator,
                'per_head' => (string) $share->perHead(),
                'percent' => self::percent($share->share->percent(4)),
                'amount' => $result->netEstate !== null ? $share->share->applyTo($result->netEstate) : null,
                'amount_each' => $result->netEstate !== null && $share->count > 1
                    ? $share->perHead()->applyTo($result->netEstate)
                    : null,
                'reason' => self::reason($share, $locale),
                'basis' => $share->basis,
                'via' => $share->via,
            ];
        }

        $excluded = [];
        foreach ($result->excluded as $exclusion) {
            $excluded[] = [
                'heir' => $exclusion->heir->value,
                'label' => self::heirLabel($exclusion->heir->value, $exclusion->count, $locale),
                'count' => $exclusion->count,
                'reason' => Translator::get('exclusion.' . $exclusion->reasonKey, [], $locale),
                'kind' => $exclusion->kind,
            ];
        }

        $notes = [];
        foreach ($result->warnings as $warning) {
            $notes[] = [
                'key' => $warning,
                'text' => Translator::get('warning.' . $warning, [], $locale),
                'severity' => self::severity($warning),
            ];
        }
        usort($notes, static fn (array $a, array $b) => self::rank($b['severity']) <=> self::rank($a['severity']));

        return [
            'madhhab' => $result->madhhab,
            'is_school_of_law' => \Faraid\Madhhab\MadhhabRules::fromKey($result->madhhab)->isSchoolOfLaw(),
            'denominator' => $denominator,
            'awl' => $result->awlApplied,
            'radd' => $result->raddApplied,
            'mflo' => $result->mfloApplied,
            'special_case' => $result->specialCase,
            'estate_value' => $result->estateValue,
            'net_estate' => $result->netEstate,
            'deductions' => [
                'funeral' => $result->funeral,
                'debts' => $result->debts,
                'wasiyyah' => $result->wasiyyah,
            ],
            'currency' => $currency,
            'distributed' => (string) $result->distributed,
            'undistributed' => (string) $result->undistributed,
            'has_remainder' => $result->undistributed->isPositive(),
            'shares' => $rows,
            'excluded' => $excluded,
            'notes' => $notes,
        ];
    }

    /**
     * Radd raises a share above the fraction the text fixes, so the sentence
     * explaining that fraction is true but no longer the whole story. The
     * uplift is appended rather than replacing it.
     */
    private static function reason(Share $share, string $locale): string
    {
        $reason = Translator::get('reason.' . $share->reasonKey, [], $locale);

        if ($share->basis === 'quranic_and_radd' && $share->reasonKey !== 'spouse_sole_heir_takes_all') {
            $reason .= ' ' . Translator::get('reason.suffix_radd', [], $locale);
        }

        return $reason;
    }

    public static function heirLabel(string $heir, int $count, ?string $locale = null): string
    {
        $key = 'heir.' . $heir;
        if ($count > 1 && Translator::has($key . '.plural', $locale)) {
            return Translator::get($key . '.plural', [], $locale);
        }

        return Translator::get($key, [], $locale);
    }

    /** Trim trailing zeroes so 12.5000 prints as 12.5 and 16.6667 stays useful. */
    private static function percent(float $percent): string
    {
        $formatted = number_format($percent, 4, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    /** Which notes the result page shows loudly, and which sit quietly below. */
    private static function severity(string $warning): string
    {
        return match (true) {
            str_contains($warning, 'not_modelled'),
            str_contains($warning, 'not_implemented'),
            str_contains($warning, 'under_review'),
            $warning === 'estate_not_fully_distributed',
            $warning === 'surplus_undistributed_spouse_excluded_from_radd' => 'high',

            str_starts_with($warning, 'madhhab_') => 'low',

            default => 'normal',
        };
    }

    private static function rank(string $severity): int
    {
        return match ($severity) {
            'high' => 2,
            'normal' => 1,
            default => 0,
        };
    }

    /** Plain-text version for the copy button and WhatsApp share. */
    public static function asText(array $presented, string $heading): string
    {
        $lines = [$heading, str_repeat('-', mb_strlen($heading))];

        if ($presented['net_estate'] !== null) {
            $lines[] = Translator::get('ui.result.net_estate') . ': '
                . Support\Str::money((float) $presented['net_estate'], $presented['currency']);
            $lines[] = '';
        }

        foreach ($presented['shares'] as $row) {
            $line = sprintf(
                '%s%s — %s (%s%%)',
                $row['label'],
                $row['count'] > 1 ? ' ×' . $row['count'] : '',
                $row['over_denominator'],
                $row['percent']
            );
            if ($row['amount'] !== null) {
                $line .= ' — ' . Support\Str::money((float) $row['amount'], $presented['currency']);
            }
            $lines[] = $line;
        }

        if ($presented['excluded'] !== []) {
            $lines[] = '';
            $lines[] = Translator::get('ui.result.excluded');
            foreach ($presented['excluded'] as $row) {
                $lines[] = '- ' . $row['label'] . ': ' . $row['reason'];
            }
        }

        $lines[] = '';
        $lines[] = Translator::get('ui.disclaimer.body');

        return implode("\n", $lines);
    }
}
