<?php require_once __DIR__ . '/includes/layout_top.php'; $inc = income_statement_data(); ?>
<section class="page-head">
  <div>
    <h1>Income Statement</h1>
    <p class="subhead">Detailed revenue and expense movement with increases, decreases, net profit, and net loss visibility.</p>
  </div>
</section>
<section class="kpi-grid statement-top statement-top-3">
  <div class="kpi-card large"><span>Net Profit Margin</span><strong class="debit"><?= number_format($inc['profit_margin'],1) ?>%</strong><small>Updates instantly whenever revenue or expense entries change.</small></div>
  <div class="kpi-card slim"><span>Net Profit</span><strong class="<?= $inc['net_profit'] >= 0 ? 'debit' : 'credit' ?>"><?= format_currency($inc['net_profit']) ?></strong><small><?= $inc['net_profit'] >= 0 ? 'Profitable period' : 'Net loss period' ?></small></div>
  <div class="kpi-card slim"><span>Net Loss</span><strong class="credit"><?= format_currency($inc['net_loss']) ?></strong><small>Shows only when expenses exceed revenue.</small></div>
</section>
<section class="statement-card">
  <div class="statement-block">
    <div class="statement-title-row"><h3>Revenues</h3><span>↗</span></div>
    <?php if (!$inc['revenues']): ?><div class="line-item empty-line"><div><strong>No revenue entries yet</strong><small>Add a revenue account from journal entry.</small></div><div class="money">—</div></div><?php endif; ?>
    <?php foreach ($inc['revenues'] as $row): ?>
      <div class="line-item"><div><strong><?= htmlspecialchars($row['name']) ?></strong><small><?= htmlspecialchars($row['code']) ?> · Increased by <?= format_currency($row['change']) ?></small></div><div class="money debit"><?= format_currency($row['amount']) ?></div></div>
    <?php endforeach; ?>
    <div class="subtotal-row"><span>Total Revenues</span><strong><?= format_currency($inc['total_revenue']) ?></strong></div>
  </div>
  <div class="statement-block separated">
    <div class="statement-title-row"><h3>Operating Expenses</h3><span>◉</span></div>
    <?php if (!$inc['expenses']): ?><div class="line-item empty-line"><div><strong>No expense entries yet</strong><small>Add an expense account from journal entry.</small></div><div class="money">—</div></div><?php endif; ?>
    <?php foreach ($inc['expenses'] as $row): ?>
      <div class="line-item"><div><strong><?= htmlspecialchars($row['name']) ?></strong><small><?= htmlspecialchars($row['code']) ?> · Increased by <?= format_currency($row['change']) ?></small></div><div class="money credit"><?= format_currency($row['amount']) ?></div></div>
    <?php endforeach; ?>
    <div class="subtotal-row negative"><span>Total Expenses</span><strong><?= format_currency($inc['total_expense']) ?></strong></div>
  </div>
  <div class="profit-banner">
    <div><span>FINAL RESULT OF THE PERIOD</span><h2><?= $inc['net_profit'] >= 0 ? 'Net Profit' : 'Net Loss' ?></h2></div>
    <div class="profit-amount"><?= format_currency(abs($inc['net_profit'])) ?><small><?= $inc['net_profit'] >= 0 ? 'Revenue exceeded expenses' : 'Expenses exceeded revenue' ?></small></div>
  </div>
</section>

<section class="recent-entries">
  <div class="section-row">
    <h2>Income Statement Activity Details</h2>
    <span class="muted-note">Shows what increased, what decreased, and which journal entry caused the movement.</span>
  </div>
  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Date</th><th>Entry ID</th><th>Description</th><th>Code</th><th>Account</th><th>Type</th><th>Change</th><th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$inc['activity']): ?>
          <tr><td colspan="8" class="empty-state">No revenue or expense activity yet.</td></tr>
        <?php else: ?>
          <?php foreach ($inc['activity'] as $row): ?>
          <tr>
            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
            <td class="entry-id"><?= htmlspecialchars($row['entry_id']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td class="strong"><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['type']) ?></td>
            <td><span class="pill <?= strtolower($row['direction']) === 'increase' ? 'asset' : 'liability---equity' ?>"><?= htmlspecialchars($row['direction']) ?></span></td>
            <td class="money <?= strtolower($row['type']) === 'revenue' ? 'debit' : 'credit' ?>"><?= format_currency(abs($row['amount'])) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>