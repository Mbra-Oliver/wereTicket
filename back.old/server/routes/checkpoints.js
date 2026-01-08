const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, checkEventAccess } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les checkpoints d'un événement
router.get('/events/:eventId', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;

    const [checkpoints] = await pool.execute(
      `SELECT c.*, 
              COUNT(DISTINCT ch.id) as checkin_count
       FROM checkpoints c
       LEFT JOIN checkins ch ON c.id = ch.checkpoint_id
       WHERE c.event_id = ?
       GROUP BY c.id
       ORDER BY c.created_at ASC`,
      [eventId]
    );

    res.json({ checkpoints });
  } catch (error) {
    console.error('Erreur liste checkpoints:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un checkpoint
router.post('/events/:eventId', [
  checkEventAccess,
  body('name').trim().notEmpty(),
  body('location').optional().trim(),
  body('access_rules').optional().isObject()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { eventId } = req.params;
    const { name, location, access_rules } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO checkpoints (event_id, name, location, access_rules, status, created_at)
       VALUES (?, ?, ?, ?, 'active', NOW())`,
      [eventId, name, location || null, access_rules ? JSON.stringify(access_rules) : null]
    );

    const [checkpoints] = await pool.execute(
      'SELECT * FROM checkpoints WHERE id = ?',
      [result.insertId]
    );

    res.status(201).json({ checkpoint: checkpoints[0] });
  } catch (error) {
    console.error('Erreur création checkpoint:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un checkpoint
router.put('/:checkpointId', [
  body('name').optional().trim().notEmpty(),
  body('location').optional().trim(),
  body('access_rules').optional().isObject(),
  body('status').optional().isIn(['active', 'inactive'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { checkpointId } = req.params;
    const { name, location, access_rules, status } = req.body;

    // Vérifier que le checkpoint appartient à un événement de l'organisateur
    const [checkpoints] = await pool.execute(
      `SELECT c.* FROM checkpoints c
       JOIN events e ON c.event_id = e.id
       WHERE c.id = ? AND e.organizer_id = ?`,
      [checkpointId, req.user.organizer_id]
    );

    if (!checkpoints.length) {
      return res.status(404).json({ error: 'Checkpoint non trouvé' });
    }

    const updates = [];
    const params = [];

    if (name !== undefined) {
      updates.push('name = ?');
      params.push(name);
    }
    if (location !== undefined) {
      updates.push('location = ?');
      params.push(location);
    }
    if (access_rules !== undefined) {
      updates.push('access_rules = ?');
      params.push(JSON.stringify(access_rules));
    }
    if (status !== undefined) {
      updates.push('status = ?');
      params.push(status);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune donnée à mettre à jour' });
    }

    params.push(checkpointId);

    await pool.execute(
      `UPDATE checkpoints SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updatedCheckpoints] = await pool.execute(
      'SELECT * FROM checkpoints WHERE id = ?',
      [checkpointId]
    );

    res.json({ checkpoint: updatedCheckpoints[0] });
  } catch (error) {
    console.error('Erreur mise à jour checkpoint:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un checkpoint
router.delete('/:checkpointId', async (req, res) => {
  try {
    const { checkpointId } = req.params;

    // Vérifier que le checkpoint appartient à un événement de l'organisateur
    const [checkpoints] = await pool.execute(
      `SELECT c.id FROM checkpoints c
       JOIN events e ON c.event_id = e.id
       WHERE c.id = ? AND e.organizer_id = ?`,
      [checkpointId, req.user.organizer_id]
    );

    if (!checkpoints.length) {
      return res.status(404).json({ error: 'Checkpoint non trouvé' });
    }

    await pool.execute('DELETE FROM checkpoints WHERE id = ?', [checkpointId]);

    res.json({ message: 'Checkpoint supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression checkpoint:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
