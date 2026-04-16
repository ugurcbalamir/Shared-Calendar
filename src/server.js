const express = require('express');
const session = require('express-session');
const path = require('path');
const { initDb } = require('./config/db');
const authRoutes = require('./routes/auth');
const profileRoutes = require('./routes/profile');
const calendarRoutes = require('./routes/calendar');
const { attachUser } = require('./middleware/auth');
const i18nMiddleware = require('./middleware/i18n');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, '..', 'views'));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(
  session({
    secret: process.env.SESSION_SECRET || 'change-this-session-secret',
    resave: false,
    saveUninitialized: false
  })
);

app.use(express.static(path.join(__dirname, '..', 'public')));
app.use(attachUser);
app.use(i18nMiddleware);

app.use(authRoutes);
app.use(profileRoutes);
app.use(calendarRoutes);

app.use((req, res) => {
  res.status(404).render('404');
});

async function start() {
  await initDb();
  const port = process.env.PORT || 3000;
  app.listen(port, () => console.log(`Server running on ${port}`));
}

start();
