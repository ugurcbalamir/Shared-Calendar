<?php
declare(strict_types=1);

session_start();

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$publicFile = __DIR__ . $uriPath;
if (PHP_SAPI === 'cli-server' && $uriPath !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    // Let PHP's built-in server serve the asset; under real hosting continue routing.
    return false;
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if ($scriptName && str_starts_with($uriPath, $scriptName)) {
    $uriPath = substr($uriPath, strlen($scriptName));
    $uriPath = $uriPath === '' ? '/' : $uriPath;
}

require_once __DIR__ . '/src/php/database.php';
require_once __DIR__ . '/src/php/auth.php';
require_once __DIR__ . '/src/php/calendar.php';
require_once __DIR__ . '/src/php/helpers.php';
require_once __DIR__ . '/src/php/mail.php';

$config = require __DIR__ . '/src/php/config.php';

init_db(get_pdo());
ensure_password_reset_table(get_pdo());
current_lang();
$path = rtrim($uriPath, '/') ?: '/';

if ($path === '/login') {
    if (!empty($_SESSION['user'])) {
        redirect('/');
    }
    $success = $_SESSION['success_message'] ?? null;
    if (isset($_SESSION['success_message'])) {
        unset($_SESSION['success_message']);
    }
    if (is_post()) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = $email ? find_user_by_email($email) : null;
        if (!$user || !password_verify($password, $user['password_hash'])) {
            render('login', ['error' => t('invalidCredentials'), 'success' => $success]);
        } else {
            $_SESSION['user'] = ['id' => (int) $user['id'], 'email' => $user['email'], 'username' => $user['username']];
            redirect('/');
        }
    } else {
        render('login', ['error' => null, 'success' => $success]);
    }
    exit;
}

if ($path === '/forgot-password') {
    if (!empty($_SESSION['user'])) {
        redirect('/');
    }
    $error = null;
    $success = null;

    if (is_post()) {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = t('forgotPassword.invalidCsrf');
        } else {
            $email = trim($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = t('forgotPassword.invalidEmail');
            } else {
                // Check user
                $user = find_user_by_email($email);
                if ($user) {
                    $token = create_reset_token((int)$user['id']);
                    $resetLink = $config['base_path'] . '/reset-password?token=' . urlencode($token);
                    // Ensure absolute URL
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $fullLink = $protocol . '://' . $host . $resetLink;

                    $subject = t('emailMessages.resetSubject');
                    $bodyTitle = t('emailMessages.resetBodyTitle');
                    $bodyText = t('emailMessages.resetBodyText');
                    $btnText = t('emailMessages.resetButtonText');
                    $validity = t('emailMessages.resetValidity');

                    $html = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2>" . htmlspecialchars($bodyTitle) . "</h2>
                        <p>" . htmlspecialchars($bodyText) . "</p>
                        <p style='margin: 30px 0;'>
                            <a href='" . htmlspecialchars($fullLink) . "' style='background: #0284c7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>" . htmlspecialchars($btnText) . "</a>
                        </p>
                        <p style='color: #666; font-size: 14px;'>" . htmlspecialchars($validity) . "</p>
                        <p style='color: #999; font-size: 12px; margin-top: 40px;'>Croissant Schedule</p>
                    </div>";

                    send_smtp_email($email, $subject, $html);
                }
                // Always show success
                $success = t('forgotPassword.successMessage');
            }
        }
    }

    render('forgot_password', [
        'error' => $error,
        'success' => $success,
        'csrfToken' => generate_csrf_token()
    ]);
    exit;
}

if ($path === '/reset-password') {
    $token = $_GET['token'] ?? '';
    $error = null;
    $success = null;
    $invalidTokenError = null;

    // Verify token validity first for GET and POST (so we don't process invalid tokens)
    $tokenRecord = verify_reset_token($token);

    if (!$tokenRecord) {
        $invalidTokenError = t('resetPassword.invalidToken');
    }

    if (is_post() && !$invalidTokenError) {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = t('forgotPassword.invalidCsrf');
        } else {
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (strlen($newPass) < 8) {
                $error = t('resetPassword.passwordTooShort');
            } elseif ($newPass !== $confirmPass) {
                $error = t('resetPassword.passwordsDoNotMatch');
            } else {
                reset_user_password((int)$tokenRecord['user_id'], $newPass);
                consume_reset_token((int)$tokenRecord['id']);
                $success = t('resetPassword.successMessage');
            }
        }
    }

    render('reset_password', [
        'token' => $token,
        'error' => $error,
        'success' => $success,
        'invalidTokenError' => $invalidTokenError,
        'csrfToken' => generate_csrf_token()
    ]);
    exit;
}

if ($path === '/register') {
    if (!empty($_SESSION['user'])) {
        redirect('/');
    }
    if (is_post()) {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($password === '' || $confirmPassword === '') {
            render('register', ['error' => t('passwordRequired')]);
        } elseif ($password !== $confirmPassword) {
            render('register', ['error' => t('passwordsDoNotMatch')]);
        } elseif (find_user_by_email($email)) {
            render('register', ['error' => t('emailInUse')]);
        } elseif (find_user_by_username($username)) {
            render('register', ['error' => t('usernameInUse')]);
        } else {
            create_user($email, $username, $password);
            $_SESSION['success_message'] = t('registrationSuccess');
            redirect('/login');
        }
    } else {
        render('register', ['error' => null]);
    }
    exit;
}

if ($path === '/logout') {
    session_destroy();
    redirect('/login');
}

if ($path === '/join') {
    $user = require_auth();
    $error = null;
    $code = trim($_GET['code'] ?? '');

    if ($code !== '') {
        $calendarId = redeem_invite($code, (int) $user['id']);
        if ($calendarId) {
            redirect('/calendars/' . $calendarId);
        }
        $error = t('invalidInvite');
    }

    if (is_post()) {
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            $error = t('inviteRequired');
        } else {
            $calendarId = redeem_invite($code, (int) $user['id']);
            if ($calendarId) {
                redirect('/calendars/' . $calendarId);
            }
            $error = t('invalidInvite');
        }
    }
    render('join', ['error' => $error, 'code' => $code]);
    exit;
}

if ($path === '/' || $path === '/calendar') {
    $user = require_auth();
    $calendars = list_calendars_for_user($user['id']);
    render('index', ['calendars' => $calendars]);
    exit;
}

if ($path === '/calendars' && is_post()) {
    $user = require_auth();
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        redirect('/');
    }
    $id = create_calendar($name, $user['id']);
    redirect('/calendars/' . $id);
}

$params = [];
if (path_matches($path, '/calendars/:id', $params) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = require_auth();
    $calendar = find_calendar_for_user((int) $params['id'], $user['id']);
    if (!$calendar) {
        redirect('/');
    }
    $role = calendar_user_role($calendar, (int) $user['id']);
    $upcoming = get_upcoming_hours((int) $params['id'], $user['id']);
    $recentNotes = get_recent_notes((int) $params['id'], $user['id']);
    render('calendar', [
        'calendar' => $calendar,
        'upcoming' => $upcoming,
        'recentNotes' => $recentNotes,
        'role' => $role,
    ]);
    exit;
}

if (path_matches($path, '/calendars/:id/settings', $params)) {
    $user = require_auth();
    $calendar = find_calendar_for_user((int) $params['id'], $user['id']);
    if (!$calendar) {
        redirect('/');
    }
    $role = calendar_user_role($calendar, (int) $user['id']);
    $isOwner = (int) $calendar['owner_id'] === (int) $user['id'];
    $canManage = $isOwner || $role === 'admin';
    if (!$canManage) {
        redirect('/calendars/' . $calendar['id']);
    }

    $error = null;
    $success = null;

    if (is_post()) {
        $action = $_POST['action'] ?? '';
        if ($action === 'invite') {
            $maxUsesInput = trim($_POST['max_uses'] ?? '');
            $validityDays = trim($_POST['validity_days'] ?? '');
            $validityHours = trim($_POST['validity_hours'] ?? '');

            if ($validityDays === '' && $validityHours === '') {
                $error = t('inviteValidityRequired');
            } else {
                $days = (int) $validityDays;
                $hours = (int) $validityHours;
                $expiresAt = null;
                if ($days > 0 || $hours > 0) {
                     $expiresAt = date('Y-m-d H:i:s', strtotime("+$days days +$hours hours"));
                }

                $maxUsesValue = null;
                if ($maxUsesInput !== '') {
                    $val = (int) $maxUsesInput;
                    if ($val > 0) {
                        $maxUsesValue = $val;
                    }
                }

                $invite = create_invite_code((int) $calendar['id'], (int) $user['id'], $maxUsesValue, $expiresAt);
                $success = t('inviteCreated') . ': ' . $invite['code'];
            }
        }

        if ($action === 'cancel_invite') {
            $inviteId = (int) ($_POST['invite_id'] ?? 0);
            if ($inviteId) {
                cancel_invite($inviteId, (int) $calendar['id']);
                $success = t('inviteCancelled');
            }
        }

        if ($action === 'remove_member') {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            if ($memberId && $memberId !== (int) $calendar['owner_id']) {
                remove_member_from_calendar((int) $calendar['id'], $memberId);
                $success = t('memberRemoved');
            } else {
                $error = t('cannotRemoveOwner');
            }
        }

        if ($action === 'update_role') {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            $newRole = $_POST['role'] ?? 'member';
            if ($memberId === (int) $calendar['owner_id']) {
                $error = t('cannotChangeOwner');
            } else {
                update_member_role((int) $calendar['id'], $memberId, $newRole);
                $success = t('roleUpdated');
            }
        }

        if ($action === 'delete_calendar' && $isOwner) {
            delete_calendar((int) $calendar['id']);
            redirect('/');
        }
    }

    $members = list_calendar_members((int) $calendar['id']);
    $invites = list_calendar_invites((int) $calendar['id']);
    render('calendar_settings', [
        'calendar' => $calendar,
        'members' => $members,
        'invites' => $invites,
        'error' => $error,
        'success' => $success,
        'role' => $role,
        'isOwner' => $isOwner,
    ]);
    exit;
}

if (path_matches($path, '/api/calendars/:id/availability', $params)) {
    $user = require_auth();
    $calendar = find_calendar_for_user((int) $params['id'], $user['id']);
    if (!$calendar) {
        json_response(['error' => 'Not found'], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        $data = get_availability_for_month((int) $calendar['id'], $user['id'], $year, $month);
        json_response($data);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = get_json_input();
        $date = $body['date'] ?? null;
        $status = $body['status'] ?? null;
        $hours = $body['hours'] ?? [];

        if (!$date) {
            json_response(['error' => 'Date required'], 400);
        }

        $filtered = is_array($hours) ? array_filter($hours, fn($h) => isset($h['status']) || !empty($h['note'])) : [];
        $hasHourData = !empty($filtered);

        // Only clear if status is explicitly cleared AND there is no hour data
        if ($status === null && !$hasHourData) {
             // Logic check: if we are here, it means we have no status and no hour data.
             // But wait, if status is null (meaning "no change" or "cleared"?), we need to know the intent.
             // If the user sent status=null, it means they want to clear the day status.
             // But if they just sent a note, $hasHourData is true.
             // If they sent nothing?
             clear_availability((int) $calendar['id'], $user['id'], $date);
             json_response(['success' => true]);
        }

        $availabilityId = upsert_availability((int) $calendar['id'], $user['id'], $date, $status);
        if ($hasHourData) {
            set_availability_hours($availabilityId, $filtered);
        }
        json_response(['success' => true]);
    }
}

if ($path === '/api/overview/availability') {
    $user = require_auth();
    $year = (int) ($_GET['year'] ?? date('Y'));
    $month = (int) ($_GET['month'] ?? date('n'));
    $calendarParam = trim($_GET['calendars'] ?? '');
    $calendarIds = $calendarParam !== '' ? array_filter(array_map('intval', explode(',', $calendarParam))) : null;

    $data = get_personal_overview((int) $user['id'], $year, $month, $calendarIds);
    json_response($data);
}

if ($path === '/profile') {
    $user = require_auth();
    if (is_post()) {
        $username = trim($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        // Verify current password first (always required)
        $userData = find_user_by_id((int)$user['id']);
        if (!$userData || !password_verify($currentPassword, $userData['password_hash'])) {
             render('profile', ['error' => t('currentPasswordIncorrect'), 'success' => null]);
             exit;
        }

        if ($username && $username !== $user['username']) {
            $existing = find_user_by_username($username);
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                render('profile', ['error' => t('usernameInUse'), 'success' => null]);
                exit;
            }
        }

        $passwordToUpdate = null;
        if (!empty($newPassword)) {
            if ($newPassword !== $newPasswordConfirm) {
                render('profile', ['error' => t('newPasswordsMismatch'), 'success' => null]);
                exit;
            }
            $passwordToUpdate = $newPassword;
        }

        $updated = update_profile($user['id'], $username ?: null, $passwordToUpdate);
        $_SESSION['user']['username'] = $updated['username'];
        render('profile', ['error' => null, 'success' => t('profileUpdated')]);
    } else {
        render('profile', ['error' => null, 'success' => null]);
    }
    exit;
}

render('404', [], 404);
