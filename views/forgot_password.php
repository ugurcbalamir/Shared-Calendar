<?php $pageStyles = ['/css/ui/unified.css', '/css/ForgotPasswordPage.css']; ?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="forgot-password-page">
  <div class="forgot-image-section">
    <img
      class="forgot-image"
      src="https://images.unsplash.com/photo-1623679116710-78b05d2fe2f3?auto=format&fit=crop&w=1200&q=80"
      alt="Background"
    >
    <div class="forgot-image-overlay"></div>
    <div class="forgot-image-content">
      <div class="forgot-image-text">
        <h1 class="forgot-image-title"><?= htmlspecialchars(t('siteTitle')) ?></h1>
        <p class="forgot-image-subtitle"><?= htmlspecialchars(t('heroDescription')) ?></p>
      </div>
    </div>
  </div>

  <div class="forgot-form-section">
    <div class="forgot-form-wrapper">
      <div class="forgot-mobile-logo">
        <div class="forgot-logo-container">
          <div class="forgot-logo-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="4" width="18" height="17" rx="2" stroke="white" stroke-width="2" />
              <path d="M3 9H21" stroke="white" stroke-width="2" />
              <path d="M8 3V6" stroke="white" stroke-width="2" />
              <path d="M16 3V6" stroke="white" stroke-width="2" />
            </svg>
          </div>
          <h2 class="forgot-logo-text"><?= htmlspecialchars(t('siteTitle')) ?></h2>
        </div>
      </div>

      <div class="forgot-card">
        <?php if (!empty($success)): ?>
          <div class="forgot-success">
            <div class="forgot-success-icon">
              <div class="forgot-success-icon-circle">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <h2 class="forgot-success-title"><?= htmlspecialchars(t('forgotPassword.title')) ?></h2>
            <p class="forgot-success-email"><?= htmlspecialchars($success) ?></p>
            <div class="forgot-footer">
               <a href="<?= $basePath ?>/login" class="forgot-back-link">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <?= htmlspecialchars(t('forgotPassword.backToLogin')) ?>
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="forgot-header">
            <h2 class="forgot-title"><?= htmlspecialchars(t('forgotPassword.title')) ?></h2>
            <p class="forgot-subtitle"><?= htmlspecialchars(t('forgotPassword.description')) ?></p>
          </div>

          <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= $basePath ?>/forgot-password" class="forgot-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <label class="form-field">
              <span class="form-label"><?= htmlspecialchars(t('forgotPassword.emailLabel')) ?></span>
              <input class="form-input" type="email" name="email" required placeholder="you@example.com">
            </label>

            <button type="submit" class="button button-primary button-full-width"><?= htmlspecialchars(t('forgotPassword.submitButton')) ?></button>
          </form>

          <div class="forgot-footer">
            <a href="<?= $basePath ?>/login" class="forgot-back-link">
              <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <?= htmlspecialchars(t('forgotPassword.backToLogin')) ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
