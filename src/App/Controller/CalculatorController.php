<?php

declare(strict_types=1);

namespace App\Controller;

use App\CalculatorRequest;
use App\Config;
use App\Csrf;
use App\Database;
use App\Locale;
use App\Response;
use App\ResultPresenter;
use App\Seo;
use App\View;
use Faraid\Calculator;
use Faraid\Exception\InvalidInput;

final class CalculatorController
{
    public function index(array $form = [], bool $submitted = false): Response
    {
        $resultHtml = null;
        $error = null;

        if ($submitted) {
            $rendered = $this->renderResult($form);
            $resultHtml = $rendered['html'];
            $error = $rendered['error'];
        }

        $seo = (new Seo(Locale::path()))
            ->title(t('ui.calculator'))
            ->description(t('ui.tagline'))
            ->type('website')
            ->alternates($this->alternates())
            ->addJsonLd($this->applicationSchema())
            ->addJsonLd($this->faqSchema());

        $body = View::page('calculator/index', [
            'input' => $form,
            'resultHtml' => $resultHtml,
            'error' => $error,
            'faq' => $this->faq(),
            'pageScript' => '/assets/js/calculator.js',
        ], $seo);

        return Response::html($body);
    }

    /** JSON endpoint the page calls as the inputs change. One engine, no duplicated rules. */
    public function api(array $form): Response
    {
        $mapped = CalculatorRequest::fromForm($form);
        if (!$mapped['hasHeirs'] || $mapped['engine']['madhhab'] === '') {
            return Response::json(['ok' => false, 'empty' => true]);
        }

        $rendered = $this->renderResult($form);
        if ($rendered['error'] !== null) {
            return Response::json(['ok' => false, 'error' => $rendered['error']]);
        }

        return Response::json(['ok' => true, 'html' => (string) $rendered['html']]);
    }

    /** @return array{html:?string,error:?string} */
    private function renderResult(array $form): array
    {
        $mapped = CalculatorRequest::fromForm($form);
        if (!$mapped['hasHeirs']) {
            return ['html' => null, 'error' => t('ui.result.empty')];
        }

        try {
            $result = (new Calculator())->calculate($mapped['engine']);
        } catch (InvalidInput $e) {
            return ['html' => null, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            error_log('Calculator failure: ' . $e->getMessage() . ' | ' . json_encode($mapped['engine']));

            return ['html' => null, 'error' => t('ui.error.500.body')];
        }

        $presented = ResultPresenter::present($result, Locale::active(), (string) Config::get('currency', ''));
        $shareText = ResultPresenter::asText($presented, Config::siteName() . ' — ' . t('ui.result'))
            . "\n" . Config::url(Locale::path());

        $this->recordEvent('completed', $mapped['engine']);

        return [
            'html' => View::partial('calculator/result', [
                'r' => $presented,
                'unreviewed' => (bool) Config::get('engine_unreviewed', true),
                'shareText' => $shareText,
            ]),
            'error' => null,
        ];
    }

    public function reportForm(?string $message = null, bool $sent = false): Response
    {
        $seo = (new Seo(Locale::path('report')))
            ->title(t('ui.report.title'))
            ->description(t('ui.report.body'))
            ->noindex()
            ->breadcrumbs([
                ['name' => t('ui.breadcrumb.home'), 'path' => Locale::path()],
                ['name' => t('ui.report.title'), 'path' => Locale::path('report')],
            ]);

        return Response::html(View::page('pages/report', [
            'sent' => $sent,
            'message' => $message,
        ], $seo));
    }

    public function submitReport(array $form): Response
    {
        if (!Csrf::check($form['_token'] ?? null)) {
            return $this->reportForm(t('ui.report.failed'));
        }

        $message = trim((string) ($form['message'] ?? ''));
        if ($message === '') {
            return $this->reportForm(t('ui.report.message'));
        }

        try {
            Database::run(
                'INSERT INTO error_reports (locale, madhhab, heirs, expected, message, reporter_email, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    Locale::active(),
                    substr((string) ($form['madhhab'] ?? ''), 0, 16) ?: null,
                    substr((string) ($form['heirs'] ?? ''), 0, 2000) ?: null,
                    substr((string) ($form['expected'] ?? ''), 0, 2000) ?: null,
                    substr($message, 0, 4000),
                    substr((string) ($form['email'] ?? ''), 0, 190) ?: null,
                    'open',
                    Database::now(),
                ]
            );
        } catch (\Throwable $e) {
            error_log('Error report not saved: ' . $e->getMessage());

            return $this->reportForm(t('ui.report.failed'));
        }

        return $this->reportForm(null, true);
    }

    /** @return array<string,string> */
    private function alternates(): array
    {
        $alternates = [];
        foreach (Locale::enabled() as $code) {
            $alternates[$code] = Locale::path('', $code);
        }

        return $alternates;
    }

    private function applicationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => Config::siteName(),
            'url' => Config::url(Locale::path()),
            'applicationCategory' => 'FinanceApplication',
            'operatingSystem' => 'Any',
            'inLanguage' => Locale::hreflang(Locale::active()),
            'description' => t('ui.tagline'),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
            'isAccessibleForFree' => true,
        ];
    }

    /** @return list<array{q:string,a:string}> */
    private function faq(): array
    {
        $faq = [];
        for ($i = 1; $i <= 6; $i++) {
            $faq[] = ['q' => t('ui.faq.q' . $i), 'a' => t('ui.faq.a' . $i)];
        }

        return $faq;
    }

    private function faqSchema(): array
    {
        $entities = [];
        foreach ($this->faq() as $item) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * Which heir combinations people actually enter is the single most useful
     * signal for deciding which guide to write next, so it is counted. No
     * names, no amounts, no identifiers — only the shape of the family.
     */
    private function recordEvent(string $event, array $engineInput): void
    {
        try {
            Database::run(
                'INSERT INTO calculator_events (locale, madhhab, event, heir_signature, created_at) VALUES (?, ?, ?, ?, ?)',
                [
                    Locale::active(),
                    (string) ($engineInput['madhhab'] ?? ''),
                    $event,
                    CalculatorRequest::signature($engineInput),
                    Database::now(),
                ]
            );
        } catch (\Throwable) {
            // Analytics must never break a calculation.
        }
    }
}
