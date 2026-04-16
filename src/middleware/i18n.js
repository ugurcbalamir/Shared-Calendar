const fs = require('fs');
const path = require('path');

const localesDir = path.join(__dirname, '..', 'locales');
const languages = ['en', 'es', 'fr', 'tr'];

const translations = Object.fromEntries(
  languages.map((lang) => {
    const content = fs.readFileSync(path.join(localesDir, `${lang}.json`), 'utf-8');
    return [lang, JSON.parse(content)];
  })
);

function i18nMiddleware(req, res, next) {
  const selected = req.query.lang || req.session.lang || 'tr';
  const lang = languages.includes(selected) ? selected : 'tr';
  req.session.lang = lang;
  res.locals.lang = lang;
  res.locals.languages = languages;
  res.locals.t = (key) => translations[lang][key] || key;
  next();
}

module.exports = i18nMiddleware;
