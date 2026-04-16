<?php $pageStyles = ['/css/ui/unified.css', '/css/ResetPasswordPage.css']; ?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="reset-password-page">
  <div class="reset-password-container">
    <div class="reset-header">
      <div class="reset-logo-icon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="18" height="17" rx="2" stroke="white" stroke-width="2" />
          <path d="M3 9H21" stroke="white" stroke-width="2" />
          <path d="M8 3V6" stroke="white" stroke-width="2" />
          <path d="M16 3V6" stroke="white" stroke-width="2" />
        </svg>
      </div>
      <h2 class="reset-title"><?= htmlspecialchars((string)t('siteTitle')) ?></h2>
      <p class="reset-subtitle"><?= htmlspecialchars((string)t('resetPassword.title')) ?></p>
    </div>

    <?php if (!empty($success)): ?>
      <div class="reset-success-container">
        <div class="reset-success-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="reset-success-title"><?= htmlspecialchars((string)t('resetPassword.title')) ?></h2>
        <p class="reset-success-message"><?= htmlspecialchars($success) ?></p>
        <div class="reset-footer">
           <a href="<?= $basePath ?>/login" class="reset-back-link">
            <?= htmlspecialchars((string)t('forgotPassword.backToLogin')) ?>
          </a>
        </div>
      </div>
    <?php elseif (!empty($invalidTokenError)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <?= htmlspecialchars($invalidTokenError) ?>
        </div>
        <div class="reset-footer">
           <a href="<?= $basePath ?>/forgot-password" class="reset-back-link">
            <?= htmlspecialchars((string)t('resetPassword.backToForgot')) ?>
          </a>
        </div>
    <?php else: ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $basePath ?>/reset-password?token=<?= htmlspecialchars($token) ?>" class="reset-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

          <div class="reset-form-group">
            <label class="form-field">
              <span class="form-label"><?= htmlspecialchars((string)t('resetPassword.newPasswordLabel')) ?></span>
              <div class="password-wrapper">
                <input class="form-input" type="password" name="new_password" required minlength="8" id="new_password">
                <button
                  type="button"
                  class="password-toggle"
                  onclick="togglePassword('new_password', this)"
                  aria-label="<?= htmlspecialchars((string)t('showPassword')) ?>"
                  data-show-label="<?= htmlspecialchars((string)t('showPassword')) ?>"
                  data-hide-label="<?= htmlspecialchars((string)t('hidePassword')) ?>"
                  title="<?= htmlspecialchars((string)t('showPassword')) ?>"
                >
                  <!-- Eye Icon (Visible when password is hidden) -->
                  <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <!-- Eye Off Icon (Visible when password is shown) -->
                  <svg class="icon-eye-off icon-hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                  </svg>
                </button>
              </div>
            </label>
          </div>

          <div class="reset-form-group">
            <label class="form-field">
              <span class="form-label"><?= htmlspecialchars((string)t('resetPassword.confirmPasswordLabel')) ?></span>
              <div class="password-wrapper">
                <input class="form-input" type="password" name="confirm_password" required minlength="8" id="confirm_password">
                <button
                  type="button"
                  class="password-toggle"
                  onclick="togglePassword('confirm_password', this)"
                  aria-label="<?= htmlspecialchars((string)t('showPassword')) ?>"
                  data-show-label="<?= htmlspecialchars((string)t('showPassword')) ?>"
                  data-hide-label="<?= htmlspecialchars((string)t('hidePassword')) ?>"
                  title="<?= htmlspecialchars((string)t('showPassword')) ?>"
                >
                  <!-- Eye Icon (Visible when password is hidden) -->
                  <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <!-- Eye Off Icon (Visible when password is shown) -->
                  <svg class="icon-eye-off icon-hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                  </svg>
                </button>
              </div>
            </label>
          </div>

          <button type="submit" class="button button-primary button-full-width"><?= htmlspecialchars((string)t('resetPassword.submitButton')) ?></button>
        </form>
    <?php endif; ?>
  </div>
</div>
<script>
function togglePassword(id, btn) {
  var input = document.getElementById(id);
  var eye = btn.querySelector('.icon-eye');
  var eyeOff = btn.querySelector('.icon-eye-off');
  var showLabel = btn.getAttribute('data-show-label');
  var hideLabel = btn.getAttribute('data-hide-label');

  if (input.type === "password") {
    input.type = "text";
    eye.classList.add('icon-hidden');
    eyeOff.classList.remove('icon-hidden');
    btn.setAttribute('aria-label', hideLabel);
    btn.setAttribute('title', hideLabel);
  } else {
    input.type = "password";
    eye.classList.remove('icon-hidden');
    eyeOff.classList.add('icon-hidden');
    btn.setAttribute('aria-label', showLabel);
    btn.setAttribute('title', showLabel);
  }
}
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
