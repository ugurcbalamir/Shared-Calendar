<?php
require_once __DIR__ . '/database.php';

function list_calendars_for_user(int $userId): array
{
    $stmt = get_pdo()->prepare("SELECT c.*, cm.role FROM calendars c INNER JOIN calendar_members cm ON cm.calendar_id = c.id WHERE cm.user_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function create_calendar(string $name, int $ownerId): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO calendars (name, owner_id) VALUES (?, ?)');
    $stmt->execute([$name, $ownerId]);
    $calendarId = (int) $pdo->lastInsertId();
    $memberStmt = $pdo->prepare('INSERT INTO calendar_members (calendar_id, user_id, role) VALUES (?, ?, ?)');
    $memberStmt->execute([$calendarId, $ownerId, 'admin']);
    return $calendarId;
}

function find_calendar_for_user(int $calendarId, int $userId): ?array
{
    $stmt = get_pdo()->prepare("SELECT c.*, cm.role FROM calendars c INNER JOIN calendar_members cm ON cm.calendar_id = c.id WHERE cm.user_id = ? AND c.id = ?");
    $stmt->execute([$userId, $calendarId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsert_availability(int $calendarId, int $userId, string $day, ?string $status): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM availabilities WHERE calendar_id = ? AND user_id = ? AND day = ?');
    $stmt->execute([$calendarId, $userId, $day]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE availabilities SET status = ? WHERE id = ?');
        $update->execute([$status, $existing['id']]);
        return (int) $existing['id'];
    }

    $insert = $pdo->prepare('INSERT INTO availabilities (calendar_id, user_id, day, status) VALUES (?, ?, ?, ?)');
    $insert->execute([$calendarId, $userId, $day, $status]);
    return (int) $pdo->lastInsertId();
}

function clear_availability(int $calendarId, int $userId, string $day): ?int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM availabilities WHERE calendar_id = ? AND user_id = ? AND day = ?');
    $stmt->execute([$calendarId, $userId, $day]);
    $existing = $stmt->fetch();
    if (!$existing) {
        return null;
    }
    $availabilityId = (int) $existing['id'];
    $deleteHours = $pdo->prepare('DELETE FROM availability_hours WHERE availability_id = ?');
    $deleteHours->execute([$availabilityId]);
    $deleteAvailability = $pdo->prepare('DELETE FROM availabilities WHERE id = ?');
    $deleteAvailability->execute([$availabilityId]);
    return $availabilityId;
}

function set_availability_hours(int $availabilityId, array $hours): void
{
    $pdo = get_pdo();

    $existingStmt = $pdo->prepare('SELECT id, hour FROM availability_hours WHERE availability_id = ?');
    $existingStmt->execute([$availabilityId]);
    $existingRows = $existingStmt->fetchAll();

    $existingMap = [];
    foreach ($existingRows as $row) {
        $existingMap[(int) $row['hour']] = (int) $row['id'];
    }

    $keptHours = [];
    foreach ($hours as $entry) {
        $hour = (int) ($entry['hour'] ?? 0);
        $status = $entry['status'] ?? null;
        $note = isset($entry['note']) ? $entry['note'] : null;
        $keptHours[] = $hour;

        if (array_key_exists($hour, $existingMap)) {
            $update = $pdo->prepare('UPDATE availability_hours SET status = ?, note = ? WHERE id = ?');
            $update->execute([$status, $note, $existingMap[$hour]]);
            continue;
        }

        $insert = $pdo->prepare('INSERT INTO availability_hours (availability_id, hour, status, note) VALUES (?, ?, ?, ?)');
        $insert->execute([$availabilityId, $hour, $status, $note]);
    }

    $hoursToDelete = array_diff(array_keys($existingMap), $keptHours);
    if (!empty($hoursToDelete)) {
        $placeholders = implode(',', array_fill(0, count($hoursToDelete), '?'));
        $delete = $pdo->prepare("DELETE FROM availability_hours WHERE availability_id = ? AND hour IN ($placeholders)");
        $delete->execute(array_merge([$availabilityId], array_values($hoursToDelete)));
    }
}

function list_calendar_members(int $calendarId): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT u.id, u.email, u.username, cm.role, c.owner_id ' .
        'FROM calendar_members cm ' .
        'INNER JOIN users u ON u.id = cm.user_id ' .
        'INNER JOIN calendars c ON c.id = cm.calendar_id ' .
        'WHERE cm.calendar_id = ? ORDER BY u.username ASC'
    );
    $stmt->execute([$calendarId]);
    return $stmt->fetchAll();
}

function calendar_user_role(array $calendar, int $userId): string
{
    if ((int) $calendar['owner_id'] === $userId) {
        return 'owner';
    }
    return $calendar['role'] ?? 'member';
}

function update_member_role(int $calendarId, int $memberId, string $role): void
{
    $allowed = ['member', 'admin'];
    if (!in_array($role, $allowed, true)) {
        return;
    }
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE calendar_members SET role = ? WHERE calendar_id = ? AND user_id = ?');
    $stmt->execute([$role, $calendarId, $memberId]);
}

function remove_member_from_calendar(int $calendarId, int $memberId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM calendar_members WHERE calendar_id = ? AND user_id = ?');
    $stmt->execute([$calendarId, $memberId]);
}

function create_invite_code(int $calendarId, int $creatorId, ?int $maxUses, ?string $expiresAt): array
{
    $pdo = get_pdo();
    $code = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare('INSERT INTO calendar_invites (calendar_id, code, max_uses, expires_at, created_by) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$calendarId, $code, $maxUses ?: null, $expiresAt ?: null, $creatorId]);
    $id = (int) $pdo->lastInsertId();
    return ['id' => $id, 'code' => $code];
}

function list_calendar_invites(int $calendarId): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT ci.*, GROUP_CONCAT(u.username SEPARATOR ", ") as joined_users ' .
        'FROM calendar_invites ci ' .
        'LEFT JOIN calendar_members cm ON cm.invite_id = ci.id ' .
        'LEFT JOIN users u ON u.id = cm.user_id ' .
        'WHERE ci.calendar_id = ? ' .
        'GROUP BY ci.id ' .
        'ORDER BY ci.created_at DESC'
    );
    $stmt->execute([$calendarId]);
    return $stmt->fetchAll();
}

function redeem_invite(string $code, int $userId): ?int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM calendar_invites WHERE code = ?');
    $stmt->execute([$code]);
    $invite = $stmt->fetch();
    if (!$invite) {
        return null;
    }
    if ($invite['expires_at'] && strtotime((string) $invite['expires_at']) < time()) {
        return null;
    }
    if ($invite['max_uses'] !== null && (int) $invite['uses'] >= (int) $invite['max_uses']) {
        return null;
    }
    $calendarId = (int) $invite['calendar_id'];

    $membership = find_calendar_for_user($calendarId, $userId);
    if ($membership) {
        return $calendarId;
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO calendar_members (calendar_id, user_id, role, invite_id) VALUES (?, ?, ?, ?)');
    $insert->execute([$calendarId, $userId, 'member', (int)$invite['id']]);

    // Check if insert was successful (row count > 0) to avoid incrementing uses if duplicate
    if ($insert->rowCount() > 0) {
        $update = $pdo->prepare('UPDATE calendar_invites SET uses = uses + 1 WHERE id = ?');
        $update->execute([(int) $invite['id']]);
    }
    return $calendarId;
}

function cancel_invite(int $inviteId, int $calendarId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE calendar_invites SET expires_at = NOW() WHERE id = ? AND calendar_id = ?');
    $stmt->execute([$inviteId, $calendarId]);
}

function delete_calendar(int $calendarId): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM calendars WHERE id = ?');
    $stmt->execute([$calendarId]);
}

function get_personal_overview(int $userId, int $year, int $month, ?array $calendarFilter = null): array
{
    $calendars = list_calendars_for_user($userId);
    $calendarMap = [];
    foreach ($calendars as $calendar) {
        $calendarMap[(int) $calendar['id']] = [
            'id' => (int) $calendar['id'],
            'name' => $calendar['name'],
        ];
    }

    $filterIds = null;
    if (is_array($calendarFilter) && !empty($calendarFilter)) {
        $filterIds = array_values(array_intersect(array_keys($calendarMap), array_map('intval', $calendarFilter)));
    }

    $activeCalendarIds = $filterIds ?? array_keys($calendarMap);
    if (empty($activeCalendarIds)) {
        return [
            'calendars' => array_values($calendarMap),
            'days' => [],
        ];
    }

    $start = sprintf('%04d-%02d-01', $year, $month);
    $endMonth = $month === 12 ? 1 : $month + 1;
    $endYear = $month === 12 ? $year + 1 : $year;
    $end = sprintf('%04d-%02d-01', $endYear, $endMonth);

    $pdo = get_pdo();

    $placeholders = implode(',', array_fill(0, count($activeCalendarIds), '?'));

    $params = array_merge($activeCalendarIds, [$userId, $start, $end]);
    $stmt = $pdo->prepare(
        'SELECT a.id, a.calendar_id, a.day, a.status, c.name AS calendar_name ' .
        'FROM availabilities a ' .
        'INNER JOIN calendars c ON c.id = a.calendar_id ' .
        'INNER JOIN calendar_members cm ON cm.calendar_id = a.calendar_id AND cm.user_id = ? ' .
        "WHERE a.calendar_id IN ($placeholders) AND a.user_id = ? AND a.day >= ? AND a.day < ?"
    );

    // Shift cm.user_id to first position
    $stmtParams = array_merge([$userId], $params);
    $stmt->execute($stmtParams);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        return [
            'calendars' => array_values($calendarMap),
            'days' => [],
        ];
    }

    $availabilityIds = array_column($rows, 'id');
    $hourPlaceholders = implode(',', array_fill(0, count($availabilityIds), '?'));
    $hourStmt = $pdo->prepare("SELECT * FROM availability_hours WHERE availability_id IN ($hourPlaceholders)");
    $hourStmt->execute($availabilityIds);
    $hourRows = $hourStmt->fetchAll();

    $hoursByAvailability = [];
    foreach ($hourRows as $hRow) {
        $aid = (int) $hRow['availability_id'];
        if (!isset($hoursByAvailability[$aid])) {
            $hoursByAvailability[$aid] = [];
        }
        $hoursByAvailability[$aid][] = $hRow;
    }

    $days = [];

    foreach ($rows as $row) {
        $date = $row['day'];
        $calendarId = (int) $row['calendar_id'];
        $calendarName = $row['calendar_name'] ?? 'Calendar';
        $status = $row['status'];
        $availabilityId = (int) $row['id'];
        $hours = $hoursByAvailability[$availabilityId] ?? [];

        if (!isset($days[$date])) {
            $days[$date] = [
                'full_day_counts' => ['available' => 0, 'busy' => 0],
                'full_day_statuses' => [],
                'hourly_summary' => ['available' => 0, 'busy' => 0, 'notes' => 0],
                'hourly_details' => [],
                'day_notes' => [],
                'hour_notes' => [],
                'hour_sets' => [
                    'available' => [],
                    'busy' => [],
                    'notes' => [],
                ],
            ];
        }

        if ($status === 'available' || $status === 'busy') {
            $days[$date]['full_day_statuses'][] = [
                'calendar_id' => $calendarId,
                'calendar_name' => $calendarName,
                'status' => $status,
            ];
            $days[$date]['full_day_counts'][$status]++;
        }

        foreach ($hours as $hourRow) {
            $hour = (int) $hourRow['hour'];
            $hStatus = $hourRow['status'];
            $note = $hourRow['note'] ?? '';

            if ($hStatus === 'available') {
                $days[$date]['hour_sets']['available'][$hour] = true;
            } elseif ($hStatus === 'busy') {
                $days[$date]['hour_sets']['busy'][$hour] = true;
            }

            if ($note !== '') {
                $days[$date]['hour_sets']['notes'][$hour] = true;
                $days[$date]['hour_notes'][] = [
                    'calendar_id' => $calendarId,
                    'calendar_name' => $calendarName,
                    'time_range' => sprintf('%02d:00-%02d:59', $hour, $hour),
                    'text' => $note,
                ];
            }

            $days[$date]['hourly_details'][] = [
                'calendar_id' => $calendarId,
                'calendar_name' => $calendarName,
                'status' => $hStatus,
                'time_range' => sprintf('%02d:00-%02d:59', $hour, $hour),
            ];
        }
    }

    foreach ($days as &$day) {
        $day['hourly_summary']['available'] = count($day['hour_sets']['available']);
        $day['hourly_summary']['busy'] = count($day['hour_sets']['busy']);
        $day['hourly_summary']['notes'] = count($day['hour_sets']['notes']);
        unset($day['hour_sets']);
    }
    unset($day);

    return [
        'calendars' => array_values($calendarMap),
        'days' => $days,
    ];
}

function get_availability_for_month(int $calendarId, int $userId, int $year, int $month): array
{
    if (!find_calendar_for_user($calendarId, $userId)) {
        return [];
    }

    $start = sprintf('%04d-%02d-01', $year, $month);
    $endMonth = $month === 12 ? 1 : $month + 1;
    $endYear = $month === 12 ? $year + 1 : $year;
    $end = sprintf('%04d-%02d-01', $endYear, $endMonth);

    $pdo = get_pdo();

    // Fetch ALL availabilities for the calendar, joining user data
    $stmt = $pdo->prepare(
        'SELECT a.*, COALESCE(u.username, "Unknown") as username FROM availabilities a ' .
        'LEFT JOIN users u ON u.id = a.user_id ' .
        'WHERE a.calendar_id = ? AND a.day >= ? AND a.day < ?'
    );
    $stmt->execute([$calendarId, $start, $end]);
    $rows = $stmt->fetchAll();

    $result = [];

    if (empty($rows)) {
        return $result;
    }

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $hourStmt = $pdo->prepare("SELECT * FROM availability_hours WHERE availability_id IN ($placeholders)");
    $hourStmt->execute($ids);
    $hourRows = $hourStmt->fetchAll();

    // Organize hours by availability_id
    $hoursByAvailId = [];
    foreach ($hourRows as $hr) {
        $aid = (int) $hr['availability_id'];
        if (!isset($hoursByAvailId[$aid])) {
            $hoursByAvailId[$aid] = [];
        }
        $hoursByAvailId[$aid][] = $hr;
    }

    // Process raw rows into the structured result
    // Result Map: Date -> { my_data, stats: {avail, busy}, hour_stats: { h -> {avail, busy} }, notes: [] }
    foreach ($rows as $row) {
        $date = $row['day'];
        $rowUserId = (int) $row['user_id'];
        $username = $row['username'];
        $status = $row['status'];
        $availId = (int) $row['id'];
        $hours = $hoursByAvailId[$availId] ?? [];

        if (!isset($result[$date])) {
            $result[$date] = [
                'my_data' => null, // Defaults
                'stats' => ['available' => 0, 'busy' => 0],
                'hour_stats' => [],
                'notes' => []
            ];
        }

        // 1. My Data (for editing)
        if ($rowUserId === $userId) {
            $myHours = [];
            foreach ($hours as $h) {
                $myHours[] = [
                    'hour' => (int) $h['hour'],
                    'status' => $h['status'],
                    'note' => $h['note'] ?? ''
                ];
            }
            $result[$date]['my_data'] = [
                'id' => $availId,
                'status' => $status,
                'hours' => $myHours
            ];
        }

        // 2. Day Stats Aggregation
        if ($status === 'available') {
            $result[$date]['stats']['available']++;
            $result[$date]['full_day_users'][] = ['username' => $username, 'status' => 'available'];
        } elseif ($status === 'busy') {
            $result[$date]['stats']['busy']++;
            $result[$date]['full_day_users'][] = ['username' => $username, 'status' => 'busy'];
        }

        // 3. Hour Stats & Notes
        foreach ($hours as $h) {
            $hVal = (int) $h['hour'];
            $hStatus = $h['status'];
            $hNote = $h['note'] ?? '';

            // Hour Stats
            if (!isset($result[$date]['hour_stats'][$hVal])) {
                $result[$date]['hour_stats'][$hVal] = ['available' => 0, 'busy' => 0];
            }
            if ($hStatus === 'available') {
                $result[$date]['hour_stats'][$hVal]['available']++;
            } elseif ($hStatus === 'busy') {
                $result[$date]['hour_stats'][$hVal]['busy']++;
            }

            // Notes
            if ($hNote !== '') {
                $result[$date]['notes'][] = [
                    'hour' => $hVal,
                    'username' => $username,
                    'note' => $hNote,
                    'is_me' => ($rowUserId === $userId)
                ];
            }
        }
    }

    return $result;
}

function get_upcoming_hours(int $calendarId, int $userId, int $limit = 8): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT a.day, ah.hour, ah.status, ah.note ' .
        'FROM availability_hours ah ' .
        'INNER JOIN availabilities a ON a.id = ah.availability_id ' .
        'WHERE a.calendar_id = ? AND a.user_id = ? AND a.day >= CURDATE() ' .
        'AND (ah.status IS NOT NULL OR ah.note IS NOT NULL) ' .
        'ORDER BY a.day ASC, ah.hour ASC LIMIT ?'
    );
    $stmt->bindValue(1, $calendarId, PDO::PARAM_INT);
    $stmt->bindValue(2, $userId, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_recent_notes(int $calendarId, int $userId, int $limit = 6): array
{
    if (!find_calendar_for_user($calendarId, $userId)) {
        return [];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT a.day, ah.hour, ah.note, COALESCE(u.username, "Unknown") as username ' .
        'FROM availability_hours ah ' .
        'INNER JOIN availabilities a ON a.id = ah.availability_id ' .
        'LEFT JOIN users u ON u.id = a.user_id ' .
        'WHERE a.calendar_id = ? AND ah.note IS NOT NULL AND ah.note <> "" ' .
        'ORDER BY a.day DESC, ah.hour DESC LIMIT ?'
    );
    $stmt->bindValue(1, $calendarId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
