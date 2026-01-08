const jwt = require('jsonwebtoken');
const pool = require('../config/database');

// Middleware d'authentification
const authenticate = async (req, res, next) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    
    if (!token) {
      return res.status(401).json({ error: 'Token manquant' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    
    // Vérifier que l'utilisateur existe toujours
    const [users] = await pool.execute(
      'SELECT id, email, role, organizer_id, status FROM users WHERE id = ?',
      [decoded.userId]
    );

    if (!users.length || users[0].status !== 'active') {
      return res.status(401).json({ error: 'Utilisateur invalide ou inactif' });
    }

    req.user = users[0];
    next();
  } catch (error) {
    if (error.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Token expiré' });
    }
    return res.status(401).json({ error: 'Token invalide' });
  }
};

// Middleware de vérification des rôles
const authorize = (...roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Non authentifié' });
    }
    
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Accès refusé' });
    }
    
    next();
  };
};

// Middleware pour vérifier l'accès à un événement
const checkEventAccess = async (req, res, next) => {
  try {
    const eventId = req.params.eventId || req.body.event_id;
    const userId = req.user.id;
    const role = req.user.role;

    // Super-admin a accès à tout
    if (role === 'super_admin') {
      return next();
    }

    // Vérifier l'accès à l'événement
    const [events] = await pool.execute(
      `SELECT e.id, e.organizer_id, eu.user_id, eu.permission 
       FROM events e 
       LEFT JOIN event_users eu ON e.id = eu.event_id AND eu.user_id = ?
       WHERE e.id = ?`,
      [userId, eventId]
    );

    if (!events.length) {
      return res.status(404).json({ error: 'Événement non trouvé' });
    }

    const event = events[0];

    // Organisateur propriétaire
    if (event.organizer_id === req.user.organizer_id) {
      return next();
    }

    // Collaborateur avec permission
    if (event.user_id && event.permission) {
      return next();
    }

    return res.status(403).json({ error: 'Accès refusé à cet événement' });
  } catch (error) {
    return res.status(500).json({ error: 'Erreur de vérification d\'accès' });
  }
};

module.exports = {
  authenticate,
  authorize,
  checkEventAccess
};

