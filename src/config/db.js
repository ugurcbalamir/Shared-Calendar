const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'croissant_user',
  password: process.env.DB_PASSWORD || 'change-me',
  database: process.env.DB_NAME || 'croissant_schedule',
  waitForConnections: true,
  connectionLimit: 10
});

async function initDb() {
  await pool.query(`CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB`);

  await pool.query(`CREATE TABLE IF NOT EXISTS calendars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB`);

  await pool.query(`CREATE TABLE IF NOT EXISTS calendar_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE(calendar_id, user_id),
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB`);

  await pool.query(`CREATE TABLE IF NOT EXISTS availabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_id INT NOT NULL,
    user_id INT NOT NULL,
    day DATE NOT NULL,
    status ENUM('available','busy') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(calendar_id, user_id, day),
    FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB`);

  await pool.query(`CREATE TABLE IF NOT EXISTS availability_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    availability_id INT NOT NULL,
    hour TINYINT NOT NULL,
    status ENUM('available','busy') NULL,
    note TEXT NULL,
    UNIQUE(availability_id, hour),
    FOREIGN KEY (availability_id) REFERENCES availabilities(id) ON DELETE CASCADE
  ) ENGINE=InnoDB`);
}

module.exports = { pool, initDb };
