const express = require('express');
const {
  listCalendarsForUser,
  createCalendar,
  findCalendarForUser,
  upsertAvailability,
  clearAvailability,
  setAvailabilityHours,
  getAvailabilityForMonth
} = require('../services/calendarService');
const { ensureAuth } = require('../middleware/auth');

const router = express.Router();

router.get('/', ensureAuth, async (req, res) => {
  const calendars = await listCalendarsForUser(req.session.user.id);
  res.render('index', { calendars });
});

router.post('/calendars', ensureAuth, async (req, res) => {
  const { name } = req.body;
  if (!name) return res.redirect('/');
  const id = await createCalendar(name, req.session.user.id);
  res.redirect(`/calendars/${id}`);
});

router.get('/calendars/:id', ensureAuth, async (req, res) => {
  const calendar = await findCalendarForUser(req.params.id, req.session.user.id);
  if (!calendar) return res.redirect('/');
  res.render('calendar', { calendar });
});

router.get('/api/calendars/:id/availability', ensureAuth, async (req, res) => {
  const calendar = await findCalendarForUser(req.params.id, req.session.user.id);
  if (!calendar) return res.status(404).json({ error: 'Not found' });
  const year = Number(req.query.year);
  const month = Number(req.query.month);
  const data = await getAvailabilityForMonth(calendar.id, req.session.user.id, year, month);
  res.json(data);
});

router.post('/api/calendars/:id/availability', ensureAuth, async (req, res) => {
  const calendar = await findCalendarForUser(req.params.id, req.session.user.id);
  if (!calendar) return res.status(404).json({ error: 'Not found' });
  const { date, status, hours } = req.body;
  if (!date) return res.status(400).json({ error: 'Date required' });

  const normalizedStatus = status || null;
  const hasHours = Array.isArray(hours) && hours.length > 0;

  if (!normalizedStatus && !hasHours) {
    await clearAvailability(calendar.id, req.session.user.id, date);
    return res.json({ success: true });
  }

  const availabilityId = await upsertAvailability(
    calendar.id,
    req.session.user.id,
    date,
    normalizedStatus
  );

  if (hasHours) {
    await setAvailabilityHours(availabilityId, hours);
  }

  res.json({ success: true });
});

module.exports = router;
