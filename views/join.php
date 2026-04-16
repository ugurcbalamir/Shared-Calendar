<?php
$pageStyles = ['/css/style.css'];
include __DIR__ . '/partials/header.php';
?>
<section class="panel form-panel">
  <h1><?= htmlspecialchars(t('joinCalendar')) ?></h1>
  <p class="form-helper"><?= htmlspecialchars(t('joinHelp')) ?></p>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post" class="stacked-form">
    <label><?= htmlspecialchars(t('inviteCode')) ?>
      <input type="text" name="code" placeholder="<?= htmlspecialchars(t('invitePlaceholder')) ?>" value="<?= htmlspecialchars($code ?? '') ?>" required>
    </label>
    <button type="submit"><?= htmlspecialchars(t('join')) ?></button>
  </form>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
