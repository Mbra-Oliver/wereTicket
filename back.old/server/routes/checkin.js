const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Check-in par QR code
router.post('/scan', [
  body('qr_data').notEmpty(),
  body('checkpoint_id').optional().isInt(),
  body('device_id').optional()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { qr_data, checkpoint_id, device_id } = req.body;

    // Parser le QR code
    let qrInfo;
    try {
      qrInfo = JSON.parse(qr_data);
    } catch (e) {
      return res.status(400).json({ error: 'QR code invalide' });
    }

    const { registration_id, access_pass_id } = qrInfo;

    // Trouver l'inscription
    const [registrations] = await pool.execute(
      `SELECT r.*, e.id as event_id, e.name as event_name
       FROM registrations r
       JOIN events e ON r.event_id = e.id
       WHERE r.id = ? OR r.access_pass_id = ?`,
      [registration_id, access_pass_id]
    );

    if (!registrations.length) {
      return res.status(404).json({ error: 'Inscription non trouvée' });
    }

    const registration = registrations[0];

    // Vérifier si déjà check-in
    const [existingCheckins] = await pool.execute(
      'SELECT * FROM checkins WHERE registration_id = ?',
      [registration.id]
    );

    if (existingCheckins.length > 0) {
      return res.json({
        success: true,
        already_checked_in: true,
        registration,
        checkin: existingCheckins[0]
      });
    }

    // Créer le check-in
    const [result] = await pool.execute(
      `INSERT INTO checkins (
        registration_id, checkpoint_id, user_id, device_id,
        checkin_method, checked_in_at
      ) VALUES (?, ?, ?, ?, 'qr_scan', NOW())`,
      [registration.id, checkpoint_id || null, req.user.id, device_id || null]
    );

    const [checkins] = await pool.execute(
      'SELECT * FROM checkins WHERE id = ?',
      [result.insertId]
    );

    res.json({
      success: true,
      registration,
      checkin: checkins[0]
    });
  } catch (error) {
    console.error('Erreur check-in:', error);
    res.status(500).json({ error: 'Erreur lors du check-in' });
  }
});

// Check-in manuel
router.post('/manual', [
  body('registration_id').isInt(),
  body('checkpoint_id').optional().isInt()
], async (req, res) => {
  try {
    const { registration_id, checkpoint_id, signature_data } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO checkins (
        registration_id, checkpoint_id, user_id, checkin_method,
        signature_data, checked_in_at
      ) VALUES (?, ?, ?, 'manual', ?, NOW())`,
      [registration_id, checkpoint_id || null, req.user.id, signature_data || null]
    );

    const [checkins] = await pool.execute(
      'SELECT * FROM checkins WHERE id = ?',
      [result.insertId]
    );

    res.json({ success: true, checkin: checkins[0] });
  } catch (error) {
    console.error('Erreur check-in manuel:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Statistiques check-in
router.get('/stats/:eventId', async (req, res) => {
  try {
    const { eventId } = req.params;

    const [stats] = await pool.execute(
      `SELECT 
        COUNT(DISTINCT r.id) as total_registrations,
        COUNT(DISTINCT c.id) as checked_in_count,
        COUNT(DISTINCT CASE WHEN c.checkpoint_id = ? THEN c.id END) as checkpoint_count
       FROM registrations r
       LEFT JOIN checkins c ON r.id = c.registration_id
       WHERE r.event_id = ?`,
      [req.query.checkpoint_id || null, eventId]
    );

    res.json({ statistics: stats[0] });
  } catch (error) {
    console.error('Erreur stats:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

