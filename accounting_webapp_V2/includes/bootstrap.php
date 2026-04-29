<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');

define('ACCOUNTS_FILE', DATA_DIR . '/accounts.json');
define('ENTRIES_FILE', DATA_DIR . '/entries.json');

function read_json_file(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function write_json_file(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function all_accounts(): array {
    static $accounts = null;
    if ($accounts === null) {
        $accounts = read_json_file(ACCOUNTS_FILE);
    }
    return $accounts;
}

function save_accounts(array $accounts): void {
    write_json_file(ACCOUNTS_FILE, array_values($accounts));
}

function account_index(): array {
    static $index = null;
    if ($index === null) {
        $index = [];
        foreach (all_accounts() as $account) {
            $index[$account['code']] = $account;
        }
    }
    return $index;
}


function reset_account_cache(): void {
    static $noop = null;
}

function build_account_depth_map(array $accounts): array {
    $byCode = [];
    foreach ($accounts as $account) {
        $byCode[$account['code']] = $account;
    }
    $depth = [];
    $walker = function(string $code) use (&$walker, &$depth, $byCode): int {
        if (isset($depth[$code])) return $depth[$code];
        $parent = $byCode[$code]['parent'] ?? null;
        if (!$parent || !isset($byCode[$parent])) {
            return $depth[$code] = 0;
        }
        return $depth[$code] = $walker($parent) + 1;
    };
    foreach (array_keys($byCode) as $code) {
        $walker($code);
    }
    return $depth;
}

function can_delete_account(string $code): bool {
    foreach (all_entries() as $entry) {
        foreach (($entry['lines'] ?? []) as $line) {
            if (($line['code'] ?? '') === $code) {
                return false;
            }
        }
    }
    foreach (all_accounts() as $account) {
        if (($account['parent'] ?? null) === $code) {
            return false;
        }
    }
    return true;
}

function add_account_code(array $payload): array {
    $code = trim((string)($payload['code'] ?? ''));
    $name = trim((string)($payload['name'] ?? ''));
    $group = trim((string)($payload['group'] ?? ''));
    $parent = trim((string)($payload['parent'] ?? ''));
    $isLeaf = isset($payload['is_leaf']) ? true : false;
    $openingBalance = (float)str_replace(',', '', trim((string)($payload['opening_balance'] ?? '0')));

    if ($code === '' || $name === '' || $group === '') {
        return [false, 'Code, account name, and type are required.'];
    }
    if (!preg_match('/^[0-9A-Za-z_-]+$/', $code)) {
        return [false, 'Account code can contain letters, numbers, dash, or underscore only.'];
    }
    $validGroups = ['asset', 'liability_equity', 'revenue', 'expense'];
    if (!in_array($group, $validGroups, true)) {
        return [false, 'Choose a valid account type.'];
    }

    $accounts = all_accounts();
    $index = account_index();
    if (isset($index[$code])) {
        return [false, 'This account code already exists.'];
    }
    if ($parent !== '' && !isset($index[$parent])) {
        return [false, 'Selected parent account does not exist.'];
    }
    if (is_cash_account(['code' => $code, 'name' => $name]) && $openingBalance < 0) {
        return [false, 'Cash opening balance cannot be negative. Use zero or a positive amount in Before / Opening Balance.'];
    }

    if ($parent !== '') {
        foreach ($accounts as &$account) {
            if (($account['code'] ?? '') === $parent) {
                $account['is_leaf'] = false;
                break;
            }
        }
        unset($account);
    }

    $accounts[] = [
        'code' => $code,
        'name' => $name,
        'group' => $group,
        'is_leaf' => $isLeaf,
        'parent' => $parent !== '' ? $parent : null,
        'opening_balance' => $openingBalance,
    ];

    usort($accounts, fn($a, $b) => strcmp((string)$a['code'], (string)$b['code']));
    save_accounts($accounts);
    return [true, 'Account code added successfully.'];
}


function update_account_code(array $payload): array {
    $originalCode = trim((string)($payload['original_code'] ?? ''));
    $code = trim((string)($payload['code'] ?? ''));
    $name = trim((string)($payload['name'] ?? ''));
    $group = trim((string)($payload['group'] ?? ''));
    $parent = trim((string)($payload['parent'] ?? ''));
    $isLeaf = isset($payload['is_leaf']) ? true : false;
    $openingBalance = (float)str_replace(',', '', trim((string)($payload['opening_balance'] ?? '0')));

    if ($originalCode === '') {
        return [false, 'Missing original account code.'];
    }
    $index = account_index();
    if (!isset($index[$originalCode])) {
        return [false, 'Original account code was not found.'];
    }
    if ($code === '' || $name === '' || $group === '') {
        return [false, 'Code, account name, and type are required.'];
    }
    if (!preg_match('/^[0-9A-Za-z_-]+$/', $code)) {
        return [false, 'Account code can contain letters, numbers, dash, or underscore only.'];
    }
    $validGroups = ['asset', 'liability_equity', 'revenue', 'expense'];
    if (!in_array($group, $validGroups, true)) {
        return [false, 'Choose a valid account type.'];
    }
    if ($parent !== '' && !isset($index[$parent])) {
        return [false, 'Selected parent account does not exist.'];
    }
    if (is_cash_account(['code' => $code, 'name' => $name]) && $openingBalance < 0) {
        return [false, 'Cash opening balance cannot be negative. Use zero or a positive amount in Before / Opening Balance.'];
    }
    if ($parent === $originalCode || $parent === $code) {
        return [false, 'An account cannot be the parent of itself.'];
    }
    if (is_cash_account(['code' => $code, 'name' => $name]) && $openingBalance < 0) {
        return [false, 'Cash opening balance cannot be negative. Use zero or a positive amount in Before / Opening Balance.'];
    }

    $entries = all_entries();
    $codeInUse = false;
    foreach ($entries as $entry) {
        foreach (($entry['lines'] ?? []) as $line) {
            if (($line['code'] ?? '') === $originalCode) {
                $codeInUse = true;
                break 2;
            }
        }
    }
    if ($codeInUse && $code !== $originalCode) {
        return [false, 'You cannot change the code of an account already used in journal entries.'];
    }

    $accounts = all_accounts();
    foreach ($accounts as $acc) {
        if (($acc['code'] ?? '') === $code && $code !== $originalCode) {
            return [false, 'This account code already exists.'];
        }
    }

    foreach ($accounts as &$account) {
        if (($account['code'] ?? '') === $originalCode) {
            $account['code'] = $code;
            $account['name'] = $name;
            $account['group'] = $group;
            $account['is_leaf'] = $isLeaf;
            $account['parent'] = $parent !== '' ? $parent : null;
            $account['opening_balance'] = $openingBalance;
        }
        if (($account['parent'] ?? null) === $originalCode && $code !== $originalCode) {
            $account['parent'] = $code;
        }
    }
    unset($account);

    if ($parent !== '') {
        foreach ($accounts as &$account) {
            if (($account['code'] ?? '') === $parent) {
                $account['is_leaf'] = false;
                break;
            }
        }
        unset($account);
    }

    if ($code !== $originalCode) {
        foreach ($entries as &$entry) {
            foreach (($entry['lines'] ?? []) as &$line) {
                if (($line['code'] ?? '') === $originalCode) {
                    $line['code'] = $code;
                }
            }
            unset($line);
        }
        unset($entry);
        save_entries($entries);
    }

    usort($accounts, fn($a, $b) => strcmp((string)$a['code'], (string)$b['code']));
    save_accounts($accounts);
    return [true, 'Account code updated successfully.'];
}

function delete_account_code(string $code): array {
    $code = trim($code);
    if ($code === '') {
        return [false, 'Missing account code.'];
    }
    if (!isset(account_index()[$code])) {
        return [false, 'Account code not found.'];
    }
    if (!can_delete_account($code)) {
        return [false, 'This account code cannot be deleted because it has children or is already used in entries.'];
    }
    $accounts = array_values(array_filter(all_accounts(), fn($account) => ($account['code'] ?? '') !== $code));
    save_accounts($accounts);
    return [true, 'Account code deleted successfully.'];
}

function all_entries(): array {
    return read_json_file(ENTRIES_FILE);
}

function save_entries(array $entries): void {
    write_json_file(ENTRIES_FILE, array_values($entries));
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function format_currency(float $value): string {
    $negative = $value < 0;
    $formatted = '$' . number_format(abs($value), 2);
    return $negative ? '(' . $formatted . ')' : $formatted;
}

function top_nav_items(): array {
    return [
        'journal.php' => 'Journal',
        'trial_balance.php' => 'Trial Balance',
        'income_statement.php' => 'Income',
        'balance_sheet.php' => 'Balance',
        'accounts.php' => 'Codes',
    ];
}

function side_nav_items(): array {
    return [
        'journal.php' => 'Journal Entry',
        'trial_balance.php' => 'Trial Balance',
        'income_statement.php' => 'Income Statement',
        'balance_sheet.php' => 'Balance Sheet',
        'accounts.php' => 'Accounts Codes',
    ];
}

function current_file(): string {
    return basename($_SERVER['PHP_SELF']);
}

function page_title(string $file): string {
    return side_nav_items()[$file] ?? 'Fiscal Curator';
}

function normalize_date_input(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '') return null;
    $patterns = ['Y-m-d', 'n/j/Y', 'm/d/Y', 'n-j-Y', 'm-d-Y', 'j/n/Y', 'd/m/Y'];
    foreach ($patterns as $pattern) {
        $dt = DateTime::createFromFormat($pattern, $raw);
        if ($dt && $dt->format($pattern) === $raw) {
            return $dt->format('Y-m-d');
        }
    }
    $ts = strtotime($raw);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

function account_type_label(string $group): string {
    return match ($group) {
        'asset' => 'Asset',
        'liability_equity' => 'Liability / Equity',
        'expense' => 'Expense',
        'revenue' => 'Revenue',
        default => 'Other',
    };
}


function opening_balance_for(array $account): float {
    return (float)($account['opening_balance'] ?? 0);
}

function is_cash_account(array $account): bool {
    $name = mb_strtolower((string)($account['name'] ?? ''));
    $code = mb_strtolower((string)($account['code'] ?? ''));
    foreach (['cash', 'نقد', 'النقدية', 'الصندوق', 'cash on hand'] as $needle) {
        if ($needle !== '' && (str_contains($name, $needle) || str_contains($code, $needle))) {
            return true;
        }
    }
    return false;
}

function opening_balance_split(array $account): array {
    $opening = opening_balance_for($account);
    $group = $account['group'] ?? 'other';
    if (in_array($group, ['asset', 'expense'], true)) {
        return $opening >= 0
            ? ['debit' => $opening, 'credit' => 0.0]
            : ['debit' => 0.0, 'credit' => abs($opening)];
    }
    return $opening >= 0
        ? ['debit' => 0.0, 'credit' => $opening]
        : ['debit' => abs($opening), 'credit' => 0.0];
}

function validate_entry_payload(array $payload): array {
    $date = normalize_date_input($payload['entry_date'] ?? '');
    if (!$date) {
        return [false, 'Please enter a valid date.'];
    }

    $codes = $payload['code'] ?? [];
    $debits = $payload['debit'] ?? [];
    $credits = $payload['credit'] ?? [];
    $lines = [];
    $accounts = account_index();
    $totalDebit = 0.0;
    $totalCredit = 0.0;

    $rows = max(count($codes), count($debits), count($credits));
    for ($i = 0; $i < $rows; $i++) {
        $code = trim((string)($codes[$i] ?? ''));
        $debit = (float)str_replace(',', '', trim((string)($debits[$i] ?? '0')));
        $credit = (float)str_replace(',', '', trim((string)($credits[$i] ?? '0')));

        if ($code === '' && $debit == 0.0 && $credit == 0.0) {
            continue;
        }

        if (!isset($accounts[$code])) {
            return [false, 'One or more account codes are not in the codes sheet.'];
        }
        if (empty($accounts[$code]['is_leaf'])) {
            return [false, 'Choose a final account code, not a parent code.'];
        }
        if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
            return [false, 'Each line must contain either a debit or a credit amount.'];
        }

        $lines[] = ['code' => $code, 'debit' => $debit, 'credit' => $credit];
        $totalDebit += $debit;
        $totalCredit += $credit;
    }

    if (count($lines) < 2) {
        return [false, 'Please enter at least two complete lines.'];
    }
    if (round($totalDebit, 2) !== round($totalCredit, 2)) {
        return [false, 'Debit and credit totals must be equal.'];
    }

    return [true, ['date' => $date, 'lines' => $lines, 'description' => trim((string)($payload['description'] ?? ''))]];
}


function remove_entry(string $id): bool {
    $entries = all_entries();
    $before = count($entries);
    $entries = array_values(array_filter($entries, fn($entry) => ($entry['id'] ?? '') !== $id));
    if (count($entries) === $before) {
        return false;
    }
    save_entries($entries);
    return true;
}

function recent_entries(): array {
    $accounts = account_index();
    $entries = all_entries();
    usort($entries, fn($a, $b) => strcmp(($b['date'] ?? ''), ($a['date'] ?? '')) ?: strcmp(($b['id'] ?? ''), ($a['id'] ?? '')));
    foreach ($entries as &$entry) {
        $debit = 0.0;
        $credit = 0.0;
        foreach (($entry['lines'] ?? []) as $line) {
            $debit += (float)($line['debit'] ?? 0);
            $credit += (float)($line['credit'] ?? 0);
        }
        $entry['total_debit'] = $debit;
        $entry['total_credit'] = $credit;
        $entry['line_count'] = count($entry['lines'] ?? []);
        $first = $entry['lines'][0]['code'] ?? '';
        $entry['sample_name'] = $first && isset($accounts[$first]) ? $accounts[$first]['name'] : '';
    }
    unset($entry);
    return $entries;
}

function add_entry(array $validated): void {
    $entries = all_entries();
    $entries[] = [
        'id' => 'E-' . str_pad((string)(1000 + count($entries) + 1), 4, '0', STR_PAD_LEFT),
        'date' => $validated['date'],
        'description' => $validated['description'] ?: 'Manual journal entry',
        'lines' => $validated['lines'],
    ];
    save_entries($entries);
}

function compute_trial_balance(): array {
    $accounts = account_index();
    $totals = [];
    foreach ($accounts as $code => $account) {
        if (!empty($account['is_leaf'])) {
            $opening = opening_balance_split($account);
            $totals[$code] = [
                'code' => $code,
                'name' => $account['name'],
                'group' => $account['group'],
                'debit' => (float)$opening['debit'],
                'credit' => (float)$opening['credit'],
            ];
        }
    }

    foreach (all_entries() as $entry) {
        foreach ($entry['lines'] as $line) {
            if (!isset($totals[$line['code']])) continue;
            $totals[$line['code']]['debit'] += (float)$line['debit'];
            $totals[$line['code']]['credit'] += (float)$line['credit'];
        }
    }

    $rows = array_values(array_filter($totals, fn($row) => round($row['debit'],2) != 0.0 || round($row['credit'],2) != 0.0));
    usort($rows, fn($a, $b) => strcmp($a['code'], $b['code']));

    return [
        'rows' => $rows,
        'total_debit' => array_sum(array_column($rows, 'debit')),
        'total_credit' => array_sum(array_column($rows, 'credit')),
        'balanced' => round(array_sum(array_column($rows, 'debit')),2) === round(array_sum(array_column($rows, 'credit')),2),
    ];
}

function income_statement_data(): array {
    $accounts = account_index();
    $revenues = [];
    $expenses = [];
    $activity = [];
    $totalRevenue = 0.0;
    $totalExpense = 0.0;

    foreach (all_entries() as $entry) {
        foreach (($entry['lines'] ?? []) as $line) {
            $code = $line['code'] ?? '';
            if (!isset($accounts[$code])) {
                continue;
            }
            $account = $accounts[$code];
            $group = $account['group'] ?? 'other';
            if ($group === 'revenue') {
                $amount = (float)($line['credit'] ?? 0) - (float)($line['debit'] ?? 0);
                if ($amount !== 0.0) {
                    $revenues[$code] = ($revenues[$code] ?? ['code' => $code, 'name' => $account['name'], 'amount' => 0.0, 'change' => 0.0]);
                    $revenues[$code]['amount'] += $amount;
                    $revenues[$code]['change'] += $amount;
                    $totalRevenue += $amount;
                    $activity[] = [
                        'date' => $entry['date'],
                        'entry_id' => $entry['id'] ?? '',
                        'description' => $entry['description'] ?? '',
                        'code' => $code,
                        'name' => $account['name'],
                        'type' => 'Revenue',
                        'direction' => $amount >= 0 ? 'Increase' : 'Decrease',
                        'amount' => $amount,
                    ];
                }
            } elseif ($group === 'expense') {
                $amount = (float)($line['debit'] ?? 0) - (float)($line['credit'] ?? 0);
                if ($amount !== 0.0) {
                    $expenses[$code] = ($expenses[$code] ?? ['code' => $code, 'name' => $account['name'], 'amount' => 0.0, 'change' => 0.0]);
                    $expenses[$code]['amount'] += $amount;
                    $expenses[$code]['change'] += $amount;
                    $totalExpense += $amount;
                    $activity[] = [
                        'date' => $entry['date'],
                        'entry_id' => $entry['id'] ?? '',
                        'description' => $entry['description'] ?? '',
                        'code' => $code,
                        'name' => $account['name'],
                        'type' => 'Expense',
                        'direction' => $amount >= 0 ? 'Increase' : 'Decrease',
                        'amount' => $amount,
                    ];
                }
            }
        }
    }

    usort($activity, fn($a, $b) => strcmp($b['date'], $a['date']) ?: strcmp($b['entry_id'], $a['entry_id']));
    $revenueRows = array_values(array_filter($revenues, fn($r) => round($r['amount'],2) != 0.0));
    $expenseRows = array_values(array_filter($expenses, fn($r) => round($r['amount'],2) != 0.0));
    usort($revenueRows, fn($a, $b) => strcmp($a['code'], $b['code']));
    usort($expenseRows, fn($a, $b) => strcmp($a['code'], $b['code']));

    $net = $totalRevenue - $totalExpense;
    return [
        'revenues' => $revenueRows,
        'expenses' => $expenseRows,
        'activity' => $activity,
        'total_revenue' => $totalRevenue,
        'total_expense' => $totalExpense,
        'net_profit' => $net,
        'net_loss' => $net < 0 ? abs($net) : 0.0,
        'profit_margin' => $totalRevenue > 0 ? ($net / $totalRevenue) * 100 : 0,
    ];
}

function balance_sheet_data(): array {
    $accounts = account_index();
    $income = income_statement_data();
    $currentAssets = [];
    $fixedAssets = [];
    $liabilities = [];
    $equity = [];
    $currentAssetTotal = 0.0;
    $fixedAssetTotal = 0.0;
    $liabilityTotal = 0.0;
    $equityTotal = 0.0;

    foreach ($accounts as $code => $account) {
        $group = $account['group'] ?? 'other';
        $name = $account['name'] ?? $code;
        $opening = opening_balance_for($account);
        if ($opening == 0.0) {
            continue;
        }
        if ($group === 'asset') {
            if (str_starts_with($code, '101')) {
                $fixedAssets[$code] = ($fixedAssets[$code] ?? ['name' => $name, 'amount' => 0.0]);
                $fixedAssets[$code]['amount'] += $opening;
                $fixedAssetTotal += $opening;
            } else {
                $currentAssets[$code] = ($currentAssets[$code] ?? ['name' => $name, 'amount' => 0.0]);
                $currentAssets[$code]['amount'] += $opening;
                $currentAssetTotal += $opening;
            }
        } elseif ($group === 'liability_equity') {
            if (str_starts_with($code, '201') || str_contains(mb_strtolower($name), 'رأس المال') || str_contains(mb_strtolower($name), 'capital')) {
                $equity[$code] = ($equity[$code] ?? ['name' => $name, 'amount' => 0.0]);
                $equity[$code]['amount'] += $opening;
                $equityTotal += $opening;
            } else {
                $liabilities[$code] = ($liabilities[$code] ?? ['name' => $name, 'amount' => 0.0]);
                $liabilities[$code]['amount'] += $opening;
                $liabilityTotal += $opening;
            }
        }
    }

    foreach (all_entries() as $entry) {
        foreach (($entry['lines'] ?? []) as $line) {
            $code = $line['code'] ?? '';
            if (!isset($accounts[$code])) {
                continue;
            }
            $account = $accounts[$code];
            $group = $account['group'] ?? 'other';
            $name = $account['name'];

            if ($group === 'asset') {
                $amount = (float)($line['debit'] ?? 0) - (float)($line['credit'] ?? 0);
                if ($amount !== 0.0) {
                    if (str_starts_with($code, '101')) {
                        $fixedAssets[$code] = ($fixedAssets[$code] ?? ['name' => $name, 'amount' => 0.0]);
                        $fixedAssets[$code]['amount'] += $amount;
                        $fixedAssetTotal += $amount;
                    } else {
                        $currentAssets[$code] = ($currentAssets[$code] ?? ['name' => $name, 'amount' => 0.0]);
                        $currentAssets[$code]['amount'] += $amount;
                        $currentAssetTotal += $amount;
                    }
                }
            } elseif ($group === 'liability_equity') {
                $amount = (float)($line['credit'] ?? 0) - (float)($line['debit'] ?? 0);
                if ($amount !== 0.0) {
                    if (str_starts_with($code, '201') || str_contains(mb_strtolower($name), 'رأس المال')) {
                        $equity[$code] = ($equity[$code] ?? ['name' => $name, 'amount' => 0.0]);
                        $equity[$code]['amount'] += $amount;
                        $equityTotal += $amount;
                    } else {
                        $liabilities[$code] = ($liabilities[$code] ?? ['name' => $name, 'amount' => 0.0]);
                        $liabilities[$code]['amount'] += $amount;
                        $liabilityTotal += $amount;
                    }
                }
            }
        }
    }

    if (round($income['net_profit'], 2) !== 0.0) {
        $equity['retained_earnings'] = ['name' => 'Retained Earnings', 'amount' => $income['net_profit']];
        $equityTotal += $income['net_profit'];
    }

    return [
        'current_assets' => array_values(array_filter($currentAssets, fn($r) => round($r['amount'],2) != 0.0)),
        'fixed_assets' => array_values(array_filter($fixedAssets, fn($r) => round($r['amount'],2) != 0.0)),
        'liabilities' => array_values(array_filter($liabilities, fn($r) => round($r['amount'],2) != 0.0)),
        'equity' => array_values(array_filter($equity, fn($r) => round($r['amount'],2) != 0.0)),
        'total_current_assets' => $currentAssetTotal,
        'total_fixed_assets' => $fixedAssetTotal,
        'total_assets' => $currentAssetTotal + $fixedAssetTotal,
        'total_liabilities' => $liabilityTotal,
        'total_equity' => $equityTotal,
        'total_liabilities_equity' => $liabilityTotal + $equityTotal,
    ];
}

function preview_rows(): array {
    $accounts = account_index();
    $rows = [];
    foreach (all_entries() as $entry) {
        $entryRows = [];
        foreach (($entry['lines'] ?? []) as $line) {
            $account = $accounts[$line['code']] ?? ['name' => '', 'group' => 'other'];
            $entryRows[] = [
                'date' => $entry['date'],
                'code' => $line['code'],
                'name' => $account['name'],
                'type' => account_type_label($account['group']),
                'debit' => (float)$line['debit'],
                'credit' => (float)$line['credit'],
                'description' => $entry['description'] ?? '',
                'entry_id' => $entry['id'] ?? '',
            ];
        }
        usort($entryRows, function($a, $b){
            $aRank = ($a['debit'] ?? 0) > 0 ? 0 : 1;
            $bRank = ($b['debit'] ?? 0) > 0 ? 0 : 1;
            return $aRank <=> $bRank ?: strcmp($a['code'], $b['code']);
        });
        $rows = array_merge($rows, $entryRows);
    }
    usort($rows, function($a, $b){
        return strcmp($b['date'], $a['date']) ?: strcmp($b['entry_id'], $a['entry_id']) ?: (($a['debit'] > 0 ? 0 : 1) <=> ($b['debit'] > 0 ? 0 : 1));
    });
    return array_slice($rows, 0, 14);
}
