const bcrypt = require('bcryptjs');
const { pool } = require('../config/db');

async function findByEmail(email) {
  const [rows] = await pool.query('SELECT * FROM users WHERE email = ?', [email]);
  return rows[0];
}

async function findByUsername(username) {
  const [rows] = await pool.query('SELECT * FROM users WHERE username = ?', [username]);
  return rows[0];
}

async function findById(id) {
  const [rows] = await pool.query('SELECT * FROM users WHERE id = ?', [id]);
  return rows[0];
}

async function createUser(email, username, password) {
  const hash = await bcrypt.hash(password, 10);
  const [result] = await pool.query(
    'INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)',
    [email, username, hash]
  );
  return { id: result.insertId, email, username };
}

async function updateProfile(userId, username, password) {
  if (username) {
    await pool.query('UPDATE users SET username = ? WHERE id = ?', [username, userId]);
  }
  if (password) {
    const hash = await bcrypt.hash(password, 10);
    await pool.query('UPDATE users SET password_hash = ? WHERE id = ?', [hash, userId]);
  }
  return findById(userId);
}

module.exports = {
  findByEmail,
  findByUsername,
  findById,
  createUser,
  updateProfile
};
