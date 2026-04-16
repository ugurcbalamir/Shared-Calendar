<?php $pageStyles = ['/css/ui/unified.css']; ?>
<?php include __DIR__ . '/partials/header.php'; ?>
<section class="panel profile-card">
  <div class="panel-header">
    <h1><?= htmlspecialchars((string)t('profileTitle')) ?></h1>
    <p class="form-helper"><?= htmlspecialchars((string)t('profileSubtitle')) ?></p>
  </div>

  <div class="profile-grid">
    <div class="profile-summary">
      <p class="profile-label"><?= htmlspecialchars((string)t('email')) ?></p>
      <p class="profile-value"><?= htmlspecialchars($currentUser['email']) ?></p>
      <p class="profile-label"><?= htmlspecialchars((string)t('username')) ?></p>
      <p class="profile-value"><?= htmlspecialchars($currentUser['username']) ?></p>
    </div>

    <div class="profile-form">
      <?php if (!empty($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <form method="post" action="<?= $basePath ?>/profile" class="stacked-form">
        <label class="form-field">
          <span class="form-label"><?= htmlspecialchars((string)t('username')) ?></span>
          <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required class="form-input">
        </label>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

        <label class="form-field">
          <span class="form-label"><?= htmlspecialchars((string)t('currentPassword')) ?> <span style="color:red">*</span></span>
          <div class="password-wrapper">
            <input type="password" name="current_password" required placeholder="******" class="form-input" id="current_password">
            <button
              type="button"
              class="password-toggle"
              onclick="togglePassword('current_password', this)"
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

        <label class="form-field">
          <span class="form-label"><?= htmlspecialchars((string)t('newPassword')) ?></span>
          <div class="password-wrapper">
            <input type="password" name="new_password" placeholder="******" class="form-input" id="new_password">
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

        <label class="form-field">
          <span class="form-label"><?= htmlspecialchars((string)t('confirmNewPassword')) ?></span>
          <div class="password-wrapper">
            <input type="password" name="new_password_confirm" placeholder="******" class="form-input" id="new_password_confirm">
            <button
              type="button"
              class="password-toggle"
              onclick="togglePassword('new_password_confirm', this)"
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

        <button type="submit" class="button button-primary"><?= htmlspecialchars((string)t('updateProfile')) ?></button>
      </form>
    </div>
  </div>
</section>
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
