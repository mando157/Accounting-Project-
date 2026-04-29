<?php require_once __DIR__ . '/includes/layout_top.php'; $tb = compute_trial_balance(); ?>
<section class="page-head">
  <div>
    <p class="eyebrow">Reports › Trial Balance</p>
    <h1>Trial Balance</h1>
    <p class="subhead">As of <?= date('F d, Y') ?> • Debit appears before credit across the report for easier review.</p>
  </div>
</section>
<section class="kpi-grid three">
  <div class="kpi-card"><span>Debit Total</span><strong class="debit"><?= format_currency($tb['total_debit']) ?></strong></div>
  <div class="kpi-card"><span>Credit Total</span><strong class="credit"><?= format_currency($tb['total_credit']) ?></strong></div>
  <div class="kpi-card dark"><span>Status</span><strong class="<?= $tb['balanced'] ? 'status-balanced' : 'status-unbalanced' ?>"><?= $tb['balanced'] ? 'Balanced' : 'Unbalanced' ?></strong><small><?= $tb['balanced'] ? '✓ In sync' : 'Needs review' ?></small></div>
</section>
<section class="table-card mt">
  <table>
    <thead><tr><th>Account Code</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr></thead>
    <tbody>
      <?php foreach ($tb['rows'] as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['code']) ?></td>
        <td class="strong"><?= htmlspecialchars($row['name']) ?></td>
        <td class="money debit"><?= $row['debit'] ? format_currency($row['debit']) : '—' ?></td>
        <td class="money credit"><?= $row['credit'] ? format_currency($row['credit']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><th colspan="2">TOTAL</th><th class="money debit"><?= format_currency($tb['total_debit']) ?></th><th class="money credit"><?= format_currency($tb['total_credit']) ?></th></tr>
    </tfoot>
  </table>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
