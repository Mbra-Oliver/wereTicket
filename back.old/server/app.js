// Updated: 
'use strict';

// Point d'entr??e Passenger (cPanel/CloudLinux)
// Objectif: avoir un boot *silencieux* mais tra??able via startup.log

const fs = require('fs');
const path = require('path');

const { loadEnv } = require('./config/loadEnv');

const logFile = path.join(__dirname, 'startup.log');

function log(msg) {
  try {
    fs.appendFileSync(logFile, `[${new Date().toISOString()}] ${msg}\n`);
  } catch (_) {
    // ignore (permissions/FS)
  }
}

process.on('uncaughtException', (e) => {
  log('UNCAUGHT_EXCEPTION: ' + (e && e.stack ? e.stack : String(e)));
  process.exit(1);
});
process.on('unhandledRejection', (e) => {
  log('UNHANDLED_REJECTION: ' + (e && e.stack ? e.stack : String(e)));
});

// Assure l'existence d'un dossier tmp (Passenger aime bien tmp/restart.txt)
try {
  fs.mkdirSync(path.join(__dirname, 'tmp'), { recursive: true });
} catch (_) {}

// Charge .env local (si pr??sent) avant l'app
const env = loadEnv({ path: path.join(__dirname, '.env') });

log(
  `Boot node=${process.version} pid=${process.pid} cwd=${process.cwd()} ` +
  `loadedEnv=${env.loaded} PORT=${process.env.PORT || ''} NODE_ENV=${process.env.NODE_ENV || ''}`
);

// Lance l'application
try {
  const app = require('./index');
  log('App loaded successfully');
  // Passenger attend que start.js exporte l'application Express
  // IMPORTANT: module.exports doit ??tre l'application Express directement
  module.exports = app;
} catch (e) {
  log('ERROR loading app: ' + (e && e.stack ? e.stack : String(e)));
  throw e;
}
