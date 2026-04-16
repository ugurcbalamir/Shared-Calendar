<?php
$config = require __DIR__ . '/config.php';

function get_pdo(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $db = $config['db'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']);
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

function ensure_password_reset_table(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM password_reset_tokens LIKE 'selector'");
        if ($stmt->fetch() === false) {
            $pdo->exec("DROP TABLE IF EXISTS password_reset_tokens");
        }
    } catch (PDOException $e) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(16) NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        INDEX idx_selector (selector),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
}

function init_db(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS calendars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        owner_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        calendar_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('member','admin') DEFAULT 'member',
        UNIQUE(calendar_id, user_id),
        FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    try {
        $pdo->exec("ALTER TABLE calendar_members ADD COLUMN role ENUM('member','admin') DEFAULT 'member'");
    } catch (PDOException $e) {
        // Column already exists
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        calendar_id INT NOT NULL,
        code VARCHAR(32) UNIQUE NOT NULL,
        max_uses INT NULL,
        uses INT DEFAULT 0,
        expires_at DATETIME NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    try {
        $pdo->exec("ALTER TABLE calendar_members ADD COLUMN invite_id INT NULL");
        $pdo->exec("ALTER TABLE calendar_members ADD CONSTRAINT fk_cm_invite FOREIGN KEY (invite_id) REFERENCES calendar_invites(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column probably exists
    }

    $pdo->exec("UPDATE calendar_members cm JOIN calendars c ON c.id = cm.calendar_id SET cm.role = 'admin' WHERE cm.role IS NULL AND cm.user_id = c.owner_id");

    $pdo->exec("CREATE TABLE IF NOT EXISTS availabilities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        calendar_id INT NOT NULL,
        user_id INT NOT NULL,
        day DATE NOT NULL,
        status ENUM('available','busy') NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(calendar_id, user_id, day),
        FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS availability_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        availability_id INT NOT NULL,
        hour TINYINT NOT NULL,
        status ENUM('available','busy') NULL,
        note TEXT NULL,
        UNIQUE(availability_id, hour),
        FOREIGN KEY (availability_id) REFERENCES availabilities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    try {
        $pdo->exec("ALTER TABLE availabilities MODIFY COLUMN status ENUM('available','busy') NULL");
    } catch (PDOException $e) {
    }

    try {
        $pdo->exec("ALTER TABLE availability_hours MODIFY COLUMN status ENUM('available','busy') NULL");
    } catch (PDOException $e) {
    }

    ensure_password_reset_table($pdo);
}
