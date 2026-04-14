<?php require_once __DIR__ . '/includes/layout_top.php'; $accounts = all_accounts(); ?>
<section class="page-head">
  <div>
    <p class="eyebrow">REFERENCE</p>
    <h1>Accounts Codes Sheet</h1>
    <p class="subhead">Imported from your Excel codes sheet. Used by all pages and journal validation.</p>
  </div>
</section>
<section class="table-card mt">
  <table>
    <thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th>Level</th></tr></thead>
    <tbody>
      <?php foreach ($accounts as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['code']) ?></td>
        <td><?= str_repeat('&nbsp;&nbsp;&nbsp;', max(0, strlen($row['code'])/3 - 1)) . htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars(account_type_label($row['group'])) ?></td>
        <td><?= !empty($row['is_leaf']) ? 'Leaf' : 'Parent' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
