<?php require_once __DIR__ . '/includes/layout_top.php';
$accounts = array_values(array_filter(all_accounts(), fn($a) => !empty($a['is_leaf'])));
$preview = preview_rows();
$trial = compute_trial_balance();
$entries = recent_entries();
$accountLookup = $accounts;
?>
<section class="page-head compact">
  <div>
    <p class="eyebrow">GENERAL LEDGER</p>
    <h1>Journal Entry</h1>
    <p class="subhead">Entries are previewed by entry date with the most recent date at the top. Inside each entry, debit lines appear before credit lines.</p>
  </div>
</section>

<section class="kpi-grid two account-tools-grid mt-small">
  <div class="kpi-card slim search-card journal-search-card">
    <span>Search Posting Accounts</span>
    <strong>Find an account code before adding the entry</strong>
    <input type="text" id="journal-account-search" class="search-input" placeholder="Search by code or account name...">
    <small>Use the search below, then click “Use code” to copy it into the focused journal line.</small>
  </div>
  <div class="table-card compact-search-table">
    <table id="journal-accounts-table">
      <thead>
        <tr><th>Code</th><th>Account Name</th><th>Type</th><th class="center preview-table-actions">Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($accountLookup as $account): ?>
        <tr data-code="<?= htmlspecialchars(mb_strtolower($account['code'])) ?>" data-name="<?= htmlspecialchars(mb_strtolower($account['name'])) ?>">
          <td><?= htmlspecialchars($account['code']) ?></td>
          <td class="strong"><?= htmlspecialchars($account['name']) ?></td>
          <td><?= htmlspecialchars(account_type_label($account['group'])) ?></td>
          <td class="center"><button type="button" class="ghost-btn small-btn use-code-btn" data-code="<?= htmlspecialchars($account['code']) ?>">Use code</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="form-card">
  <form method="post" action="actions/save_entry.php" id="journal-form">
    <div class="form-grid">
      <label>
        <span>Date</span>
        <input type="text" name="entry_date" placeholder="mm/dd/yyyy" required>
      </label>
      <label>
        <span>Description</span>
        <input type="text" name="description" placeholder="Journal explanation">
      </label>
    </div>

    <div class="entry-lines" id="entry-lines">
      <?php for($i=0;$i<2;$i++): ?>
      <div class="entry-line">
        <label>
          <span>Account Code</span>
          <input list="account-codes" name="code[]" placeholder="e.g. 102001001" required>
        </label>
        <label>
          <span>Account Name</span>
          <input class="account-name-display" type="text" placeholder="Enter ledger account..." readonly>
        </label>
        <label>
          <span>Type of Account</span>
          <input class="account-type-display" type="text" placeholder="Account type" readonly>
        </label>
        <label>
          <span>Debit</span>
          <input type="number" step="0.01" min="0" name="debit[]" placeholder="0.00">
        </label>
        <label>
          <span>Credit</span>
          <input type="number" step="0.01" min="0" name="credit[]" placeholder="0.00">
        </label>
      </div>
      <?php endfor; ?>
    </div>

    <datalist id="account-codes">
      <?php foreach ($accounts as $account): ?>
        <option value="<?= htmlspecialchars($account['code']) ?>"><?= htmlspecialchars($account['name']) ?></option>
      <?php endforeach; ?>
    </datalist>

    <div class="form-actions">
      <button class="ghost-btn" type="button" id="add-line-btn">+ Add Line</button>
      <button class="primary-btn" type="submit">⊕ Validate &amp; Add</button>
    </div>
  </form>
</section>

<section class="preview-wrap">
  <div class="preview-header">
    <h2>Entry Preview</h2>
    <div class="legend"><span class="dot green"></span> Debits first <span class="dot red"></span> Credits second</div>
  </div>
  <div class="preview-layout full-preview-layout">
    <div class="table-card wide preview-table-shell">
      <div class="table-title-row">
        <div>
          <strong>Preview lines before and after saving</strong>
          <small>Sorted by accounting date, with the newest journal date shown first.</small>
        </div>
        <span class="muted-pill"><?= count($preview) ?> line<?= count($preview) === 1 ? '' : 's' ?></span>
      </div>
      <table>
        <thead>
          <tr>
            <th>Date</th><th>Entry ID</th><th>Description</th><th>Code</th><th>Account Name</th><th>Type</th><th>Debit</th><th>Credit</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$preview): ?>
            <tr><td colspan="8" class="empty-state">No preview lines yet. Add a valid entry to see it here.</td></tr>
          <?php else: ?>
            <?php foreach ($preview as $row): ?>
            <tr>
              <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
              <td class="entry-id"><?= htmlspecialchars($row['entry_id']) ?></td>
              <td><?= htmlspecialchars($row['description']) ?></td>
              <td><?= htmlspecialchars($row['code']) ?></td>
              <td class="strong"><?= htmlspecialchars($row['name']) ?></td>
              <td><span class="pill <?= strtolower(str_replace([' ','/'],['-','-'],$row['type'])) ?>"><?= htmlspecialchars($row['type']) ?></span></td>
              <td class="money debit"><?= $row['debit'] ? format_currency($row['debit']) : '—' ?></td>
              <td class="money credit"><?= $row['credit'] ? format_currency($row['credit']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mini-summary preview-summary-row">
      <div class="sum-row"><span>Total Debits</span><strong class="debit"><?= format_currency($trial['total_debit']) ?></strong></div>
      <div class="sum-row"><span>Total Credits</span><strong class="credit"><?= format_currency($trial['total_credit']) ?></strong></div>
      <div class="summary-status"><span>Status</span><strong class="balanced-dot"><?= $trial['balanced'] ? '● Balanced' : '● Unbalanced' ?></strong></div>
    </div>
  </div>
</section>

<section class="recent-entries">
  <div class="section-row">
    <h2>Saved Journal Entries</h2>
    <span class="muted-note">Sorted by accounting date, not by save time. Delete any entry and all linked pages update immediately.</span>
  </div>
  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Entry ID</th><th>Date</th><th>Description</th><th>Lines</th><th>Debit</th><th>Credit</th><th class="center preview-table-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr><td colspan="7" class="empty-state">No journal entries yet.</td></tr>
        <?php else: ?>
          <?php foreach ($entries as $entry): ?>
            <tr>
              <td class="entry-id"><?= htmlspecialchars($entry['id']) ?></td>
              <td><?= date('M d, Y', strtotime($entry['date'])) ?></td>
              <td>
                <div class="strong"><?= htmlspecialchars($entry['description']) ?></div>
                <div class="muted-note"><?= htmlspecialchars($entry['sample_name']) ?></div>
              </td>
              <td><?= (int)$entry['line_count'] ?></td>
              <td class="money debit"><?= format_currency($entry['total_debit']) ?></td>
              <td class="money credit"><?= format_currency($entry['total_credit']) ?></td>
              <td class="center">
                <form class="inline-form" method="post" action="actions/delete_entry.php" onsubmit="return confirm('Delete this entry? All reports will update immediately.');">
                  <input type="hidden" name="entry_id" value="<?= htmlspecialchars($entry['id']) ?>">
                  <button class="delete-btn" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>