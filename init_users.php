<?php
require_once __DIR__ . '/src/php/database.php';
require_once __DIR__ . '/src/php/auth.php';
require_once __DIR__ . '/src/php/calendar.php';

$pdo = get_pdo();

// Create valid users
$pass = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (email, username, password_hash) VALUES (?, ?, ?)")
    ->execute(['userA@test.com', 'UserA', $pass]);

$pdo->prepare("INSERT IGNORE INTO users (email, username, password_hash) VALUES (?, ?, ?)")
    ->execute(['userB@test.com', 'UserB', $pass]);

// Get ID
$idA = find_user_by_email('userA@test.com')['id'];
$idB = find_user_by_email('userB@test.com')['id'];

// Create Calendar
$calId = create_calendar("Shared Demo Calendar", $idA);
// Add Member
$pdo->prepare("INSERT IGNORE INTO calendar_members (calendar_id, user_id, role) VALUES (?, ?, 'member')")->execute([$calId, $idB]);

// Set some data
upsert_availability($calId, $idA, date('Y-m-d'), 'available');
upsert_availability($calId, $idB, date('Y-m-d'), 'busy');

// Add Note
$aid = upsert_availability($calId, $idA, date('Y-m-d'), 'available');
set_availability_hours($aid, [['hour' => 10, 'status' => 'available', 'note' => 'My Public Note']]);

echo "Users Created. Calendar ID: $calId\n";
