function ensureAuth(req, res, next) {
  if (req.session && req.session.user) {
    res.locals.currentUser = req.session.user;
    return next();
  }
  return res.redirect('/login');
}

function attachUser(req, res, next) {
  if (req.session && req.session.user) {
    res.locals.currentUser = req.session.user;
  }
  next();
}

module.exports = { ensureAuth, attachUser };
