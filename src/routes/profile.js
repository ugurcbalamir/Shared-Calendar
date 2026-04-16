const express = require('express');
const { ensureAuth } = require('../middleware/auth');
const { findByUsername, updateProfile } = require('../services/userService');

const router = express.Router();

router.get('/profile', ensureAuth, (req, res) => {
  res.render('profile', { error: null, success: null });
});

router.post('/profile', ensureAuth, async (req, res) => {
  const { username, password } = req.body;
  if (username && username !== req.session.user.username) {
    const exists = await findByUsername(username);
    if (exists && exists.id !== req.session.user.id) {
      return res.render('profile', { error: 'Kullanıcı adı kullanılıyor', success: null });
    }
  }
  const updated = await updateProfile(req.session.user.id, username || null, password || null);
  req.session.user.username = updated.username;
  res.render('profile', { error: null, success: 'Profil güncellendi' });
});

module.exports = router;
