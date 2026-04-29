<?php require_once __DIR__ . '/includes/layout_top.php';
$accounts = all_accounts();
$depthMap = build_account_depth_map($accounts);
$parentOptions = array_values(array_filter($accounts, fn($row) => empty($row['is_leaf'])));
?>
<section class="page-head">
  <div>
    <p class="eyebrow">REFERENCE</p>
    <h1>Accounts Codes Sheet</h1>
    <p class="subhead">Imported from your Excel codes sheet, with add, delete, and live search support. New codes are available instantly across the journal and reports.</p>
  </div>
</section>

<section class="kpi-grid two account-tools-grid mt-small">
  <div class="form-card compact-card">
    <div class="section-row"><h2 id="account-form-title">Add Account Code</h2><span class="muted-note">Use leaf for posting accounts and parent for grouping accounts.</span></div>
    <form method="post" action="actions/add_account.php" class="stack-form" id="account-form">
      <input type="hidden" name="original_code" id="original-code" value="">
      <div class="form-grid three-col">
        <label><span>Code</span><input type="text" name="code" id="account-code-input" required placeholder="e.g. 401009001"></label>
        <label><span>Account Name</span><input type="text" name="name" id="account-name-input" required placeholder="New account name"></label>
        <label><span>Before / Opening Balance</span><input type="number" step="0.01" name="opening_balance" id="account-opening-input" value="0" placeholder="Previous year balance"></label>
      </div>
      <div class="form-grid three-col">
        <label><span>Type</span>
          <select name="group" id="account-group-input" required>
            <option value="asset">Asset</option>
            <option value="liability_equity">Liability / Equity</option>
            <option value="revenue">Revenue</option>
            <option value="expense">Expense</option>
          </select>
        </label>
      </div>
      <div class="form-grid two-col-tight">
        <label><span>Parent Code (optional)</span>
          <input list="parent-codes" name="parent" id="account-parent-input" placeholder="Choose parent code">
          <datalist id="parent-codes">
            <?php foreach ($parentOptions as $parent): ?>
              <option value="<?= htmlspecialchars($parent['code']) ?>"><?= htmlspecialchars($parent['name']) ?></option>
            <?php endforeach; ?>
          </datalist>
        </label>
        <label class="checkbox-label"><span>Posting Account</span><label class="check-inline"><input type="checkbox" name="is_leaf" id="account-leaf-input" checked> <span>Allow direct journal posting to this code</span></label></label>
      </div>
      <div class="form-actions">
        <span class="muted-note">Delete is blocked only when the code has children or is already used in entries. Cash opening balance must be zero or positive.</span>
        <div class="inline-actions">
          <button class="ghost-btn" type="button" id="account-form-cancel" style="display:none">Cancel Edit</button>
          <button class="primary-btn" type="submit" id="account-form-submit">+ Add Account</button>
        </div>
      </div>
    </form>
  </div>
  <div class="kpi-card slim search-card">
    <span>Search Accounts</span>
    <strong>Find by code or name</strong>
    <input type="text" id="account-search" class="search-input" placeholder="Type code or account name...">
    <small>Filtering happens instantly without reloading the page.</small>
  </div>
</section>

<section class="table-card mt">
  <table id="accounts-table">
    <thead><tr><th>Code</th><th>Account Name</th><th>Type</th><th>Before / Opening</th><th>Level</th><th class="center preview-table-actions">Action</th></tr></thead>
    <tbody>
      <?php foreach ($accounts as $row): $depth = $depthMap[$row['code']] ?? 0; $canDelete = can_delete_account($row['code']); ?>
      <tr data-code="<?= htmlspecialchars(mb_strtolower($row['code'])) ?>" data-name="<?= htmlspecialchars(mb_strtolower($row['name'])) ?>">
        <td><?= htmlspecialchars($row['code']) ?></td>
        <td><?= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, $depth)) . htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars(account_type_label($row['group'])) ?></td>
        <td class="money"><?= (float)($row['opening_balance'] ?? 0) !== 0.0 ? format_currency((float)$row['opening_balance']) : '$0.00' ?></td>
        <td><?= !empty($row['is_leaf']) ? 'Leaf' : 'Parent' ?></td>
        <td class="center">
          <button type="button" class="ghost-btn small-btn edit-account-btn"
            data-code="<?= htmlspecialchars($row['code']) ?>"
            data-name="<?= htmlspecialchars($row['name']) ?>"
            data-group="<?= htmlspecialchars($row['group']) ?>"
            data-parent="<?= htmlspecialchars((string)($row['parent'] ?? '')) ?>"
            data-opening="<?= htmlspecialchars((string)($row['opening_balance'] ?? '0')) ?>"
            data-leaf="<?= !empty($row['is_leaf']) ? '1' : '0' ?>">Edit</button>
          <?php if ($canDelete): ?>
            <form class="inline-form" method="post" action="actions/delete_account.php" onsubmit="return confirm('Delete account code <?= htmlspecialchars($row['code']) ?>?');">
              <input type="hidden" name="code" value="<?= htmlspecialchars($row['code']) ?>">
              <button class="delete-btn" type="submit">Delete</button>
            </form>
          <?php else: ?>
            <span class="muted-note">Locked</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>