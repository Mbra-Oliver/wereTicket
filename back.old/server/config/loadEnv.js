'use strict';

// Petit loader .env sans dépendance (remplace dotenv)
// - lit un fichier .env
// - n'écrase pas les variables déjà présentes dans process.env

const fs = require('fs');
const path = require('path');

function stripQuotes(value) {
  if (value === undefined || value === null) return value;
  const v = String(value).trim();
  const isDouble = v.startsWith('"') && v.endsWith('"');
  const isSingle = v.startsWith("'") && v.endsWith("'");
  return (isDouble || isSingle) ? v.slice(1, -1) : v;
}

function loadEnv(options = {}) {
  const envPath = options.path || path.join(__dirname, '..', '.env');
  if (!fs.existsSync(envPath)) return { loaded: false, envPath };

  const content = fs.readFileSync(envPath, 'utf8');
  const lines = content.split(/\r?\n/);

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;

    const eq = trimmed.indexOf('=');
    if (eq === -1) continue;

    const key = trimmed.slice(0, eq).trim();
    let val = trimmed.slice(eq + 1);

    // Support: KEY=VALUE #comment
    const hash = val.indexOf(' #');
    if (hash !== -1) val = val.slice(0, hash);

    val = stripQuotes(val);

    if (key && process.env[key] === undefined) {
      process.env[key] = val;
    }
  }

  return { loaded: true, envPath };
}

module.exports = { loadEnv };
