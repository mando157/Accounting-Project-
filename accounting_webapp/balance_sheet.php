<?php require_once __DIR__ . '/includes/layout_top.php'; $bs = balance_sheet_data(); ?>
<section class="page-head">
  <div>
    <p class="eyebrow">FINANCIAL POSITION</p>
    <h1>Balance Sheet</h1>
    <p class="subhead">A curated snapshot of your business's financial health, captured on December 31, 2023.</p>
  </div>
</section>
<section class="kpi-grid two-tight">
  <div class="kpi-card big-number"><span>Total Assets</span><strong><?= format_currency($bs['total_assets']) ?></strong><small>↝ +4.2% from last quarter</small></div>
  <div class="kpi-card big-number pink"><span>Liabilities + Equity</span><strong><?= format_currency($bs['total_liabilities_equity']) ?></strong><small><span class="audit-pill">● IN BALANCE</span></small></div>
</section>
<section class="dual-columns">
  <div>
    <h2 class="section-title">Assets</h2>
    <div class="table-card split">
      <table>
        <thead><tr><th>Current Assets</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($bs['current_assets'] as $row): ?><tr><td><?= htmlspecialchars($row['name']) ?></td><td class="money"><?= format_currency($row['amount']) ?></td></tr><?php endforeach; ?>
        </tbody>
        <tfoot><tr><th>Total Current Assets</th><th class="money"><?= format_currency($bs['total_current_assets']) ?></th></tr></tfoot>
      </table>
    </div>
    <div class="table-card split mt-small">
      <table>
        <thead><tr><th>Fixed Assets</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($bs['fixed_assets'] as $row): ?><tr><td><?= htmlspecialchars($row['name']) ?></td><td class="money"><?= format_currency($row['amount']) ?></td></tr><?php endforeach; ?>
        </tbody>
        <tfoot><tr><th>Total Fixed Assets</th><th class="money"><?= format_currency($bs['total_fixed_assets']) ?></th></tr></tfoot>
      </table>
    </div>
    <div class="total-banner light"><span>Total Assets</span><strong><?= format_currency($bs['total_assets']) ?></strong></div>
  </div>
  <div>
    <h2 class="section-title">Liabilities &amp; Equity</h2>
    <div class="table-card split">
      <table>
        <thead><tr><th>Liabilities</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($bs['liabilities'] as $row): ?><tr><td><?= htmlspecialchars($row['name']) ?></td><td class="money"><?= format_currency($row['amount']) ?></td></tr><?php endforeach; ?>
        </tbody>
        <tfoot><tr><th>Total Liabilities</th><th class="money"><?= format_currency($bs['total_liabilities']) ?></th></tr></tfoot>
      </table>
    </div>
    <div class="table-card split mt-small">
      <table>
        <thead><tr><th>Equity</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($bs['equity'] as $row): ?><tr><td><?= htmlspecialchars($row['name']) ?></td><td class="money"><?= format_currency($row['amount']) ?></td></tr><?php endforeach; ?>
        </tbody>
        <tfoot><tr><th>Total Equity</th><th class="money"><?= format_currency($bs['total_equity']) ?></th></tr></tfoot>
      </table>
    </div>
    <div class="total-banner dark"><span>Total Liabilities &amp; Equity</span><strong><?= format_currency($bs['total_liabilities_equity']) ?></strong></div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
