<?php require_once __DIR__ . '/bootstrap.php'; $active = current_file(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars(page_title($active)) ?> - Fiscal Curator</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      <span class="brand-icon">🏛️</span>
      <span>Fiscal Curator</span>
    </div>
    <nav class="side-nav">
      <?php foreach (side_nav_items() as $file => $label): ?>
        <a class="side-link <?= $active === $file ? 'active' : '' ?>" href="<?= $file ?>">
          <span class="side-icon"><?= match($file){'journal.php'=>'≡','trial_balance.php'=>'▣','income_statement.php'=>'◉','balance_sheet.php'=>'◪','accounts.php'=>'#',default=>'•'} ?></span>
          <span><?= htmlspecialchars($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main class="main-panel">
    <header class="top-nav">
      <nav>
        <?php foreach (top_nav_items() as $file => $label): ?>
          <a class="top-link <?= $active === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </header>
    <?php if ($flash = pull_flash()): ?>
      <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
