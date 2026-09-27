<?php /** @var list $reports */ ?>
<p class="hint">A calculator that quietly carries a wrong rule does real harm. Read these, and act on them quickly.</p>
<?php if ($reports === []): ?>
    <p>No reports.</p>
<?php else: ?>
<table class="admin-table">
    <thead><tr><th>When</th><th>Locale</th><th>School</th><th>Heirs</th><th>What they said</th><th>Reply to</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $report): ?>
        <tr>
            <td><?= e(format_date((string) $report['created_at'])) ?></td>
            <td><?= e((string) $report['locale']) ?></td>
            <td><?= e((string) ($report['madhhab'] ?? '—')) ?></td>
            <td><code><?= e((string) ($report['heirs'] ?? '—')) ?></code></td>
            <td><?= e((string) $report['message']) ?><?php if (!empty($report['expected'])): ?><br><em>Expected: <?= e((string) $report['expected']) ?></em><?php endif; ?></td>
            <td><?= e((string) ($report['reporter_email'] ?? '—')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
