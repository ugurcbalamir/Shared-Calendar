const express = require('express');
const bcrypt = require('bcryptjs');
const { findByEmail, findByUsername, createUser } = require('../services/userService');

const router = express.Router();

router.get('/login', (req, res) => {
  if (req.session.user) return res.redirect('/');
  res.render('login', { error: null });
});

router.post('/login', async (req, res) => {
  const { email, password } = req.body;
  const user = await findByEmail(email);
  if (!user) {
    return res.render('login', { error: 'Kullanıcı bulunamadı' });
  }
  const match = await bcrypt.compare(password, user.password_hash);
  if (!match) {
    return res.render('login', { error: 'Şifre hatalı' });
  }
  req.session.user = { id: user.id, email: user.email, username: user.username };
  res.redirect('/');
});

router.get('/register', (req, res) => {
  if (req.session.user) return res.redirect('/');
  res.render('register', { error: null });
});

router.post('/register', async (req, res) => {
  const { email, username, password } = req.body;
  const existingEmail = await findByEmail(email);
  if (existingEmail) {
    return res.render('register', { error: 'E-posta kullanılıyor' });
  }
  const existingUsername = await findByUsername(username);
  if (existingUsername) {
    return res.render('register', { error: 'Kullanıcı adı kullanılıyor' });
  }
  await createUser(email, username, password);
  res.redirect('/login');
});

router.get('/logout', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/login');
  });
});

module.exports = router;
