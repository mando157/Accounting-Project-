<?php require_once __DIR__ . '/includes/layout_top.php'; $inc = income_statement_data(); ?>
<section class="page-head">
  <div>
    <h1>Income Statement</h1>
    <p class="subhead">For the Period Ended December 31, 2023</p>
  </div>
</section>
<section class="kpi-grid statement-top">
  <div class="kpi-card large"><span>Net Profit Margin</span><strong class="debit"><?= number_format($inc['profit_margin'],1) ?>%</strong><small>↗ +4.2% from previous quarter</small></div>
  <div class="kpi-card slim"><span>Status</span><strong><span class="audit-pill">AUDITED</span></strong><small>Generated automatically via real-time ledger integration.</small></div>
</section>
<section class="statement-card">
  <div class="statement-block">
    <div class="statement-title-row"><h3>Revenues</h3><span>↗</span></div>
    <?php foreach ($inc['revenues'] as $row): ?>
      <div class="line-item"><div><strong><?= htmlspecialchars($row['name']) ?></strong><small>Revenue account</small></div><div class="money"><?= format_currency($row['amount']) ?></div></div>
    <?php endforeach; ?>
    <div class="subtotal-row"><span>Total Revenues</span><strong><?= format_currency($inc['total_revenue']) ?></strong></div>
  </div>
  <div class="statement-block separated">
    <div class="statement-title-row"><h3>Operating Expenses</h3><span>◉</span></div>
    <?php foreach ($inc['expenses'] as $row): ?>
      <div class="line-item"><div><strong><?= htmlspecialchars($row['name']) ?></strong><small>Expense account</small></div><div class="money"><?= format_currency($row['amount']) ?></div></div>
    <?php endforeach; ?>
    <div class="subtotal-row negative"><span>Total Expenses</span><strong><?= format_currency($inc['total_expense']) ?></strong></div>
  </div>
  <div class="profit-banner">
    <div><span>NET EARNINGS AFTER OPERATIONS</span><h2>Net Profit</h2></div>
    <div class="profit-amount"><?= format_currency($inc['net_profit']) ?><small>◎ FINALIZED CALCULATION</small></div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
