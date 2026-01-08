'use strict';

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// (Optionnel) charge .env si tu lances en local avec `node start.js`
// En Passenger, start.js le fait déjà.
try {
  const path = require('path');
  const { loadEnv } = require('./config/loadEnv');
  loadEnv({ path: path.join(__dirname, '.env') });
} catch (_) {}

const app = express();

// Passenger/cPanel est souvent derrière un proxy
app.set('trust proxy', 1);

// Passenger définit automatiquement le port via process.env.PORT
const PORT = process.env.PORT || 3000;

// Sécurité
app.disable('x-powered-by');
app.use(helmet({
  contentSecurityPolicy: false, // Désactiver CSP temporairement
  crossOriginEmbedderPolicy: false
}));

// CORS : plusieurs origines possibles (séparées par virgule)
const allowedOrigins = (process.env.CLIENT_URLS || process.env.CLIENT_URL || 'http://localhost:3001')
  .split(',')
  .map((s) => s.trim().replace(/\/$/, ''))
  .filter(Boolean);

app.use(
  cors({
    origin: (origin, cb) => {
      // origin peut être undefined sur curl / server-to-server
      if (!origin) return cb(null, true);

      const cleanOrigin = origin.replace(/\/$/, '');
      if (allowedOrigins.includes(cleanOrigin)) return cb(null, true);

      return cb(new Error(`CORS bloqué pour l'origine: ${origin}`));
    },
    credentials: true,
  })
);

// Parser JSON
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Rate limiting (API)
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
});
app.use('/api', limiter);

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/organizers', require('./routes/organizers'));
app.use('/api/collaborators', require('./routes/collaborators'));
app.use('/api/events', require('./routes/events'));
app.use('/api/contacts', require('./routes/contacts'));
app.use('/api/custom-fields', require('./routes/custom-fields'));
app.use('/api/segments', require('./routes/segments'));
app.use('/api/registrations', require('./routes/registrations'));
app.use('/api/checkin', require('./routes/checkin'));
app.use('/api/checkpoints', require('./routes/checkpoints'));
app.use('/api/campaigns', require('./routes/campaigns'));
app.use('/api/tickets', require('./routes/tickets'));
app.use('/api/promo-codes', require('./routes/promo-codes'));
app.use('/api/payments', require('./routes/payments'));
app.use('/api/statistics', require('./routes/statistics'));
app.use('/api/audit', require('./routes/audit'));

// Health
app.get('/health', (req, res) => {
  try {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
  } catch (error) {
    console.error('Error in /health:', error);
    res.status(500).json({ error: 'Health check failed' });
  }
});

// Servir le frontend React pour toutes les routes non-API
const path = require('path');
const fs = require('fs');
const publicPath = path.join(__dirname, '../public');

// Servir les fichiers statiques
app.use(express.static(publicPath, {
  index: false
}));

// Pour toutes les routes non-API, servir index.html (SPA)
app.get('*', (req, res, next) => {
  // Si c'est une route API ou health, laisser le 404 handler gérer
  if (req.path.startsWith('/api') || req.path === '/health') {
    return next();
  }
  // Ne pas servir index.html pour les assets
  if (req.path.startsWith('/assets')) {
    return next();
  }
  // Servir index.html pour le frontend React
  const indexPath = path.join(publicPath, 'index.html');
  if (!fs.existsSync(indexPath)) {
    return res.status(404).json({ error: 'index.html not found' });
  }
  res.sendFile(indexPath, (err) => {
    if (err) {
      console.error('Error sending index.html:', err);
      if (!res.headersSent) {
        res.status(500).json({ error: 'Error serving index.html' });
      }
    }
  });
});

// 404 pour les routes API non trouvées
app.use((req, res) => {
  res.status(404).json({ error: { message: 'Route introuvable' } });
});

// Gestion erreurs
app.use((err, req, res, next) => {
  console.error(err && err.stack ? err.stack : err);
  res.status(err.status || 500).json({
    error: {
      message: err.message || 'Erreur serveur interne',
      ...(process.env.NODE_ENV === 'development' && err.stack ? { stack: err.stack } : {}),
    },
  });
});

// IMPORTANT Passenger: Passenger gère le serveur automatiquement
// Ne PAS appeler app.listen() en production avec Passenger
// Passenger attend juste module.exports = app

module.exports = app;
