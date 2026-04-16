<?php $pageStyles = ['/css/ui/unified.css', '/css/LoginPage.css']; ?>
<?php include __DIR__ . '/partials/header.php'; ?>
<div class="login-page">
  <div class="login-image-section">
    <img
      class="login-image"
      src="https://images.unsplash.com/photo-1623679116710-78b05d2fe2f3?auto=format&fit=crop&w=1200&q=80"
      alt="Modern workspace"
    >
    <div class="login-image-overlay"></div>
    <div class="login-image-content">
      <div class="login-image-text">
        <h1 class="login-image-title">Welcome to Croissant Schedule</h1>
        <p class="login-image-subtitle">Share your schedule, sync your life.</p>
      </div>
    </div>
  </div>

  <div class="login-form-section">
    <div class="login-form-wrapper">
      <div class="login-mobile-logo">
        <div class="login-logo-container">
          <div class="login-logo-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="4" width="18" height="17" rx="2" stroke="white" stroke-width="2" />
              <path d="M3 9H21" stroke="white" stroke-width="2" />
              <path d="M8 3V6" stroke="white" stroke-width="2" />
              <path d="M16 3V6" stroke="white" stroke-width="2" />
            </svg>
          </div>
          <h2 class="login-logo-text">Croissant Schedule</h2>
        </div>
      </div>

      <div class="login-card">
        <div class="login-header">
          <h2 class="login-title"><?= htmlspecialchars((string)t('loginTitle')) ?></h2>
          <p class="login-subtitle"><?= htmlspecialchars((string)t('welcome')) ?></p>
        </div>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $basePath ?>/login" class="login-form">
          <label class="form-field">
            <span class="form-label"><?= htmlspecialchars((string)t('email')) ?></span>
            <input class="form-input" type="email" name="email" required placeholder="you@example.com">
          </label>

          <label class="form-field">
            <span class="form-label"><?= htmlspecialchars((string)t('password')) ?></span>
            <div class="password-wrapper">
              <input class="form-input" type="password" name="password" required placeholder="••••••••" id="login_password">
              <button
                type="button"
                class="password-toggle"
                onclick="togglePassword('login_password', this)"
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

          <div class="login-forgot-password">
            <span class="form-helper">&nbsp;</span>
            <a href="<?= $basePath ?>/forgot-password" class="login-forgot-link"><?= htmlspecialchars((string)t('auth.forgot_password_link')) ?></a>
          </div>

          <button type="submit" class="button button-primary form-submit"><?= htmlspecialchars((string)t('navLogin')) ?></button>
        </form>

        <div class="login-footer">
          <p class="login-footer-text"><?= htmlspecialchars((string)t('auth.need_account_link')) ?> <a class="login-register-link" href="<?= $basePath ?>/register"><?= htmlspecialchars((string)t('navRegister')) ?></a></p>
        </div>
      </div>
    </div>
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
