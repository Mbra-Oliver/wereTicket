const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, authorize, checkEventAccess } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Route pour obtenir les permissions d'un collaborateur sur un événement
router.get('/events/:eventId/permissions', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;
    const { userId } = req.query;

    if (!userId) {
      return res.status(400).json({ error: 'userId requis' });
    }

    // Vérifier que l'utilisateur demandé appartient au même organisateur
    const [users] = await pool.execute(
      'SELECT id, organizer_id FROM users WHERE id = ? AND organizer_id = ?',
      [userId, req.user.organizer_id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    // Récupérer les permissions
    const [permissions] = await pool.execute(
      'SELECT * FROM event_users WHERE event_id = ? AND user_id = ?',
      [eventId, userId]
    );

    res.json({
      has_access: permissions.length > 0,
      permission: permissions.length > 0 ? permissions[0].permission : null
    });
  } catch (error) {
    console.error('Erreur récupération permissions:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Route pour définir les permissions d'un collaborateur sur un événement
router.put('/events/:eventId/permissions/:userId', [
  checkEventAccess,
  body('permission').isIn(['read', 'write', 'checkin_only'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { eventId, userId } = req.params;
    const { permission } = req.body;

    // Vérifier que l'utilisateur appartient au même organisateur
    const [users] = await pool.execute(
      'SELECT id FROM users WHERE id = ? AND organizer_id = ?',
      [userId, req.user.organizer_id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    // Insérer ou mettre à jour la permission
    await pool.execute(
      `INSERT INTO event_users (event_id, user_id, permission, created_at)
       VALUES (?, ?, ?, NOW())
       ON DUPLICATE KEY UPDATE permission = VALUES(permission)`,
      [eventId, userId, permission]
    );

    res.json({ message: 'Permission mise à jour avec succès' });
  } catch (error) {
    console.error('Erreur mise à jour permission:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
