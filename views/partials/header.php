<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(t('siteTitle')) ?></title>
  <link rel="stylesheet" href="<?= $basePath ?>/css/globals.css">
  <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
  <?php if (!empty($pageStyles) && is_array($pageStyles)): ?>
    <?php foreach ($pageStyles as $style): ?>
      <link rel="stylesheet" href="<?= $basePath . $style ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body data-api-base="<?= htmlspecialchars($basePath) ?>">
  <header class="topbar">
    <div class="brand"><a href="<?= $basePath ?>/" style="color: inherit; text-decoration: none;"><?= htmlspecialchars(t('siteTitle')) ?></a></div>
    <nav class="nav-links">
      <a href="<?= $basePath ?>/" class="nav-link"><?= htmlspecialchars(t('navHome')) ?></a>
      <?php if ($currentUser): ?>
        <a href="<?= $basePath ?>/profile" class="nav-link"><?= htmlspecialchars(t('navProfile')) ?></a>
        <a href="<?= $basePath ?>/logout" class="nav-link"><?= htmlspecialchars(t('navLogout')) ?></a>
      <?php else: ?>
        <a href="<?= $basePath ?>/login" class="nav-link"><?= htmlspecialchars(t('navLogin')) ?></a>
        <a href="<?= $basePath ?>/register" class="nav-link"><?= htmlspecialchars(t('navRegister')) ?></a>
      <?php endif; ?>
      <form method="get" action="" class="lang-switch">
        <label for="lang"><?= htmlspecialchars(t('language')) ?></label>
        <select name="lang" id="lang" onchange="this.form.submit()">
          <?php foreach ($languages as $l): ?>
            <option value="<?= htmlspecialchars($l) ?>" <?= $l === $lang ? 'selected' : '' ?>><?= strtoupper($l) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </nav>
  </header>
  <main class="content">
