const { pool } = require('../config/db');

async function listCalendarsForUser(userId) {
  const [rows] = await pool.query(
    `SELECT c.* FROM calendars c
     INNER JOIN calendar_members cm ON cm.calendar_id = c.id
     WHERE cm.user_id = ? ORDER BY c.created_at DESC`,
    [userId]
  );
  return rows;
}

async function createCalendar(name, ownerId) {
  const [result] = await pool.query('INSERT INTO calendars (name, owner_id) VALUES (?, ?)', [
    name,
    ownerId
  ]);
  const calendarId = result.insertId;
  await pool.query('INSERT INTO calendar_members (calendar_id, user_id) VALUES (?, ?)', [
    calendarId,
    ownerId
  ]);
  return calendarId;
}

async function findCalendarForUser(calendarId, userId) {
  const [rows] = await pool.query(
    `SELECT c.* FROM calendars c
     INNER JOIN calendar_members cm ON cm.calendar_id = c.id
     WHERE cm.user_id = ? AND c.id = ?`,
    [userId, calendarId]
  );
  return rows[0];
}

async function upsertAvailability(calendarId, userId, day, status) {
  const [existing] = await pool.query(
    'SELECT id FROM availabilities WHERE calendar_id = ? AND user_id = ? AND day = ?',
    [calendarId, userId, day]
  );

  if (existing.length) {
    await pool.query('UPDATE availabilities SET status = ? WHERE id = ?', [status, existing[0].id]);
    return existing[0].id;
  }
  const [result] = await pool.query(
    'INSERT INTO availabilities (calendar_id, user_id, day, status) VALUES (?, ?, ?, ?)',
    [calendarId, userId, day, status]
  );
  return result.insertId;
}

async function clearAvailability(calendarId, userId, day) {
  const [existing] = await pool.query(
    'SELECT id FROM availabilities WHERE calendar_id = ? AND user_id = ? AND day = ?',
    [calendarId, userId, day]
  );
  if (!existing.length) return null;
  const availabilityId = existing[0].id;
  await pool.query('DELETE FROM availability_hours WHERE availability_id = ?', [availabilityId]);
  await pool.query('DELETE FROM availabilities WHERE id = ?', [availabilityId]);
  return availabilityId;
}

async function setAvailabilityHours(availabilityId, hours) {
  for (const entry of hours) {
    const { hour, status, note } = entry;
    const [existing] = await pool.query(
      'SELECT id FROM availability_hours WHERE availability_id = ? AND hour = ?',
      [availabilityId, hour]
    );
    if (existing.length) {
      await pool.query('UPDATE availability_hours SET status = ?, note = ? WHERE id = ?', [
        status,
        note || null,
        existing[0].id
      ]);
    } else {
      await pool.query(
        'INSERT INTO availability_hours (availability_id, hour, status, note) VALUES (?, ?, ?, ?)',
        [availabilityId, hour, status, note || null]
      );
    }
  }
}

async function getAvailabilityForMonth(calendarId, userId, year, month) {
  const start = `${year}-${String(month).padStart(2, '0')}-01`;
  const endMonth = month === 12 ? 1 : month + 1;
  const endYear = month === 12 ? year + 1 : year;
  const end = `${endYear}-${String(endMonth).padStart(2, '0')}-01`;
  const [rows] = await pool.query(
    'SELECT * FROM availabilities WHERE calendar_id = ? AND user_id = ? AND day >= ? AND day < ?',
    [calendarId, userId, start, end]
  );
  const availabilityMap = {};
  for (const row of rows) {
    availabilityMap[row.day.toISOString().slice(0, 10)] = { id: row.id, status: row.status };
  }
  if (!rows.length) return availabilityMap;
  const ids = rows.map((r) => r.id);
  const [hours] = await pool.query(
    `SELECT * FROM availability_hours WHERE availability_id IN (${ids.map(() => '?').join(',')})`,
    ids
  );
  for (const hourRow of hours) {
    const availabilityId = hourRow.availability_id;
    const dayKey = Object.keys(availabilityMap).find(
      (d) => availabilityMap[d].id === availabilityId
    );
    if (!availabilityMap[dayKey].hours) availabilityMap[dayKey].hours = [];
    availabilityMap[dayKey].hours.push({
      hour: hourRow.hour,
      status: hourRow.status,
      note: hourRow.note || ''
    });
  }
  return availabilityMap;
}

module.exports = {
  listCalendarsForUser,
  createCalendar,
  findCalendarForUser,
  upsertAvailability,
  clearAvailability,
  setAvailabilityHours,
  getAvailabilityForMonth
};
