<?php
$pageStyles = ['/css/style.css', '/css/shared_calendar.css'];
include __DIR__ . '/partials/header.php';

$activeInvites = [];
$passiveInvites = [];
foreach ($invites as $inv) {
    // Check if expired (<= time() because cancelling sets it to NOW())
    $isExpired = $inv['expires_at'] && strtotime((string)$inv['expires_at']) <= time();
    $isDepleted = $inv['max_uses'] !== null && (int)$inv['uses'] >= (int)$inv['max_uses'];
    if ($isExpired || $isDepleted) {
        $passiveInvites[] = $inv;
    } else {
        $activeInvites[] = $inv;
    }
}
?>
<section class="panel">
  <div class="panel-header">
    <h1><?= htmlspecialchars(t('calendarSettings')) ?> · <?= htmlspecialchars($calendar['name']) ?></h1>
    <p class="form-helper"><?= htmlspecialchars(t('settingsDescription')) ?></p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="settings-grid">
    <div class="settings-card">
      <h2><?= htmlspecialchars(t('generateInvite')) ?></h2>
      <p class="form-helper"><?= htmlspecialchars(t('inviteHelp')) ?></p>

      <form method="post" class="stacked-form" style="margin-bottom: 2rem;">
        <input type="hidden" name="action" value="invite">

        <div class="form-field">
            <label class="form-label"><?= htmlspecialchars(t('maxUses')) ?></label>
            <input type="number" name="max_uses" min="0" placeholder="0" class="input">
            <small class="form-helper"><?= htmlspecialchars(t('unlimitedUsesHelp')) ?></small>
        </div>

        <div class="flex flex-row gap-md w-full">
            <div class="form-field flex-1">
                <label class="form-label"><?= htmlspecialchars(t('days')) ?></label>
                <input type="number" name="validity_days" min="0" value="0" class="input">
            </div>
            <div class="form-field flex-1">
                <label class="form-label"><?= htmlspecialchars(t('hours')) ?></label>
                <input type="number" name="validity_hours" min="0" value="0" class="input">
            </div>
        </div>
        <small class="form-helper mb-md block"><?= htmlspecialchars(t('validityHelp')) ?></small>

        <button type="submit" class="button button-primary"><?= htmlspecialchars(t('createInvite')) ?></button>
      </form>

      <h3 style="margin-bottom: 1rem;"><?= htmlspecialchars(t('activeInvites')) ?></h3>
      <?php if (empty($activeInvites)): ?>
        <p class="form-helper mb-md"><?= htmlspecialchars(t('noInvites')) ?></p>
      <?php else: ?>
        <ul class="invite-list-ul" style="list-style: none; padding: 0;">
          <?php foreach ($activeInvites as $invite): ?>
            <li class="invite-row card" style="display: block; margin-bottom: 1rem;">
              <div class="flex flex-row justify-between items-center mb-sm">
                  <div>
                    <strong style="font-size: 1.1em;"><?= htmlspecialchars($invite['code']) ?></strong>
                    <div class="form-helper" style="font-size: 0.85em;">
                      <?= htmlspecialchars(t('uses')) ?>: <?= (int) $invite['uses'] ?>
                      <?php if (!empty($invite['max_uses'])): ?> / <?= (int) $invite['max_uses'] ?><?php else: ?> / <?= htmlspecialchars(t('unlimitedUses')) ?><?php endif; ?>
                      <?php if (!empty($invite['expires_at'])): ?> · <?= htmlspecialchars($invite['expires_at']) ?><?php else: ?> · <?= htmlspecialchars(t('unlimitedTime')) ?><?php endif; ?>
                    </div>
                  </div>
                  <div class="flex gap-sm items-center">
                      <button type="button" class="button button-secondary button-sm" style="height: 100%;" onclick="copyLink('<?= htmlspecialchars($invite['code']) ?>')">
                          <?= htmlspecialchars(t('copyLink')) ?>
                      </button>
                      <form method="post" style="display: flex; height: 100%;">
                          <input type="hidden" name="action" value="cancel_invite">
                          <input type="hidden" name="invite_id" value="<?= (int)$invite['id'] ?>">
                          <button type="submit" class="button button-danger button-sm" style="height: 100%; display: flex; align-items: center;" onclick="return confirm('<?= htmlspecialchars(t('cancelConfirm')) ?>');">
                              <?= htmlspecialchars(t('cancel')) ?>
                          </button>
                      </form>
                  </div>
              </div>
              <?php if (!empty($invite['joined_users'])): ?>
                  <div class="form-helper" style="border-top: 1px solid #eee; padding-top: 0.5rem;">
                      <strong><?= htmlspecialchars(t('joinedUsers')) ?>:</strong> <?= htmlspecialchars($invite['joined_users']) ?>
                  </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (!empty($passiveInvites)): ?>
          <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #999;"><?= htmlspecialchars(t('passiveInvites')) ?></h3>
          <ul class="invite-list-ul" style="opacity: 0.7; list-style: none; padding: 0;">
            <?php foreach ($passiveInvites as $invite): ?>
              <li class="invite-row card" style="display: block; margin-bottom: 1rem; background: #f9f9f9;">
                <div class="flex flex-row justify-between items-center mb-sm">
                    <div>
                      <strong><?= htmlspecialchars($invite['code']) ?></strong>
                      <div class="form-helper">
                        <?= htmlspecialchars(t('uses')) ?>: <?= (int) $invite['uses'] ?>
                      </div>
                    </div>
                </div>
                <?php if (!empty($invite['joined_users'])): ?>
                    <div class="form-helper" style="border-top: 1px solid #ddd; padding-top: 0.5rem;">
                        <strong><?= htmlspecialchars(t('joinedUsers')) ?>:</strong> <?= htmlspecialchars($invite['joined_users']) ?>
                    </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
      <?php endif; ?>
    </div>

    <div class="settings-card">
      <h2><?= htmlspecialchars(t('members')) ?></h2>
      <p class="form-helper"><?= htmlspecialchars(t('memberHelp')) ?></p>
      <div class="member-list">
        <?php foreach ($members as $member): ?>
          <?php
            $isTargetOwner = ($member['id'] == $calendar['owner_id']);
            $isTargetAdmin = ($member['role'] === 'admin');
            $canManage = false;

            if ($isOwner) {
                // Owner can manage everyone except themselves
                if (!$isTargetOwner) $canManage = true;
            } else {
                // Admin can manage Members. Cannot manage Admins or Owner.
                if (!$isTargetOwner && !$isTargetAdmin) {
                    $canManage = true;
                }
            }
          ?>
          <div class="member-row">
            <div class="member-info">
              <strong style="font-size: 1.05rem;"><?= htmlspecialchars($member['username']) ?></strong>
              <p class="form-helper" style="margin-top: 0.2rem;"><?= htmlspecialchars($member['email']) ?></p>
            </div>

            <div class="member-actions" style="<?= !$canManage ? 'justify-content: flex-end;' : '' ?>">
              <span class="pill pill-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.9rem; margin-right: 0.5rem; display: inline-block;">
                  <?= htmlspecialchars($member['id'] == $calendar['owner_id'] ? t('owner') : ($member['role'] === 'admin' ? t('admin') : t('member'))) ?>
              </span>

              <?php if ($canManage): ?>
                <form method="post" class="inline-form-small" style="display: inline-flex;">
                  <input type="hidden" name="action" value="update_role">
                  <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                  <select name="role" style="padding: 0.4rem 0.6rem;">
                    <option value="member" <?= $member['role'] === 'member' ? 'selected' : '' ?>><?= htmlspecialchars(t('member')) ?></option>
                    <?php if ($isOwner): // Only owner can promote to admin ?>
                        <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>><?= htmlspecialchars(t('admin')) ?></option>
                    <?php endif; ?>
                  </select>
                  <button type="submit" class="button button-secondary button-sm" style="background: #fff;"><?= htmlspecialchars(t('update')) ?></button>
                </form>
                <form method="post" class="inline-form-small" style="display: inline-flex;" onsubmit="return confirm('<?= htmlspecialchars(t('removeConfirm')) ?>');">
                  <input type="hidden" name="action" value="remove_member">
                  <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                  <button type="submit" class="button button-danger button-sm"><?= htmlspecialchars(t('removeMember')) ?></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="settings-actions mt-lg text-center">
      <a href="/calendars/<?= (int)$calendar['id'] ?>" class="button button-secondary"><?= htmlspecialchars(t('backToCalendar')) ?></a>
  </div>
</section>

<?php if ($isOwner): ?>
<section class="panel danger-panel" style="margin-top: 2rem;">
  <h2><?= htmlspecialchars(t('deleteCalendar')) ?></h2>
  <p class="form-helper"><?= htmlspecialchars(t('onlyOwnerCanDelete')) ?></p>
  <form method="post" onsubmit="return confirm('<?= htmlspecialchars(t('deleteConfirm')) ?>');">
    <input type="hidden" name="action" value="delete_calendar">
    <button type="submit" class="button button-danger"><?= htmlspecialchars(t('deleteCalendar')) ?></button>
  </form>
</section>
<?php endif; ?>

<script>
function copyLink(code) {
    const url = window.location.origin + '<?= $basePath ?? '' ?>/join?code=' + code;
    navigator.clipboard.writeText(url).then(() => {
        alert('<?= htmlspecialchars(t('linkCopied')) ?>');
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>