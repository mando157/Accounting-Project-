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
            $totals[$code] = [
                'code' => $code,
                'name' => $account['name'],
                'group' => $account['group'],
                'debit' => 0.0,
                'credit' => 0.0,
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
                    $revenues[$code] = ($revenues[$code] ?? ['name' => $account['name'], 'amount' => 0.0]);
                    $revenues[$code]['amount'] += $amount;
                    $totalRevenue += $amount;
                }
            } elseif ($group === 'expense') {
                $amount = (float)($line['debit'] ?? 0) - (float)($line['credit'] ?? 0);
                if ($amount !== 0.0) {
                    $expenses[$code] = ($expenses[$code] ?? ['name' => $account['name'], 'amount' => 0.0]);
                    $expenses[$code]['amount'] += $amount;
                    $totalExpense += $amount;
                }
            }
        }
    }

    return [
        'revenues' => array_values(array_filter($revenues, fn($r) => round($r['amount'],2) != 0.0)),
        'expenses' => array_values(array_filter($expenses, fn($r) => round($r['amount'],2) != 0.0)),
        'total_revenue' => $totalRevenue,
        'total_expense' => $totalExpense,
        'net_profit' => $totalRevenue - $totalExpense,
        'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalExpense) / $totalRevenue) * 100 : 0,
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
        foreach ($entry['lines'] as $line) {
            $account = $accounts[$line['code']] ?? ['name' => '', 'group' => 'other'];
            $rows[] = [
                'date' => $entry['date'],
                'code' => $line['code'],
                'name' => $account['name'],
                'type' => account_type_label($account['group']),
                'debit' => (float)$line['debit'],
                'credit' => (float)$line['credit'],
            ];
        }
    }
    return array_slice(array_reverse($rows), 0, 8);
}
