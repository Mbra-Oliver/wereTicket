const express = require('express');
const { body, validationResult } = require('express-validator');
const QRCode = require('qrcode');
const { v4: uuidv4 } = require('uuid');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();

// Inscription publique (sans auth)
router.post('/', [
  body('event_id').isInt(),
  body('email').isEmail().normalizeEmail(),
  body('first_name').trim().notEmpty(),
  body('last_name').trim().notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { event_id, email, first_name, last_name, company, phone, ticket_type_id, registration_data } = req.body;

    // Vérifier l'événement
    const [events] = await pool.execute(
      'SELECT * FROM events WHERE id = ? AND status IN ("published", "in_progress")',
      [event_id]
    );

    if (!events.length) {
      return res.status(404).json({ error: 'Événement non trouvé ou non disponible' });
    }

    const event = events[0];

    // Vérifier les quotas si ticket type
    if (ticket_type_id) {
      const [tickets] = await pool.execute(
        'SELECT * FROM ticket_types WHERE id = ? AND event_id = ?',
        [ticket_type_id, event_id]
      );
      if (!tickets.length || tickets[0].sold >= tickets[0].quantity) {
        return res.status(400).json({ error: 'Ticket non disponible' });
      }
    }

    // Générer QR code et access pass
    const accessPassId = uuidv4();
    const qrCodeData = JSON.stringify({ event_id, registration_id: null, access_pass_id: accessPassId });
    const qrCodeUrl = await QRCode.toDataURL(qrCodeData, { width: 200 });

    // Créer l'inscription
    const [result] = await pool.execute(
      `INSERT INTO registrations (
        event_id, email, first_name, last_name, company, phone,
        ticket_type_id, registration_data, status, qr_code, access_pass_id, registered_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, NOW())`,
      [
        event_id, email, first_name, last_name, company, phone,
        ticket_type_id, JSON.stringify(registration_data || {}), qrCodeUrl, accessPassId
      ]
    );

    // Mettre à jour le QR code avec l'ID d'inscription
    const registrationId = result.insertId;
    const updatedQrData = JSON.stringify({ event_id, registration_id: registrationId, access_pass_id: accessPassId });
    const updatedQrCodeUrl = await QRCode.toDataURL(updatedQrData, { width: 200 });

    await pool.execute(
      'UPDATE registrations SET qr_code = ? WHERE id = ?',
      [updatedQrCodeUrl, registrationId]
    );

    // Mettre à jour le compteur de tickets vendus
    if (ticket_type_id) {
      await pool.execute(
        'UPDATE ticket_types SET sold = sold + 1 WHERE id = ?',
        [ticket_type_id]
      );
    }

    const [registrations] = await pool.execute(
      'SELECT * FROM registrations WHERE id = ?',
      [registrationId]
    );

    res.status(201).json({
      registration: registrations[0],
      qr_code: updatedQrCodeUrl
    });
  } catch (error) {
    console.error('Erreur inscription:', error);
    res.status(500).json({ error: 'Erreur lors de l\'inscription' });
  }
});

// Lister les inscriptions (back office)
router.get('/', authenticate, async (req, res) => {
  try {
    const { event_id, status, search } = req.query;
    let query = `
      SELECT r.*, e.name as event_name, tt.name as ticket_type_name
      FROM registrations r
      LEFT JOIN events e ON r.event_id = e.id
      LEFT JOIN ticket_types tt ON r.ticket_type_id = tt.id
      WHERE 1=1
    `;
    const params = [];

    if (event_id) {
      query += ' AND r.event_id = ?';
      params.push(event_id);
    } else if (req.user.role !== 'super_admin') {
      query += ' AND e.organizer_id = ?';
      params.push(req.user.organizer_id);
    }

    if (status) {
      query += ' AND r.status = ?';
      params.push(status);
    }

    if (search) {
      query += ' AND (r.email LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ?)';
      const searchTerm = `%${search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }

    query += ' ORDER BY r.registered_at DESC LIMIT 100';

    const [registrations] = await pool.execute(query, params);
    
    // Ajouter les informations de check-in
    const registrationsWithCheckin = await Promise.all(
      registrations.map(async (reg) => {
        const [checkins] = await pool.execute(
          'SELECT * FROM checkins WHERE registration_id = ?',
          [reg.id]
        );
        return {
          ...reg,
          checked_in: checkins.length > 0,
          checkin_count: checkins.length
        };
      })
    );

    res.json({ registrations: registrationsWithCheckin });
  } catch (error) {
    console.error('Erreur liste inscriptions:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour une inscription
router.put('/:id', authenticate, [
  body('status').optional().isIn(['confirmed', 'cancelled', 'pending'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { status } = req.body;

    // Vérifier que l'inscription existe et appartient à un événement de l'organisateur
    const [registrations] = await pool.execute(
      `SELECT r.* FROM registrations r
       JOIN events e ON r.event_id = e.id
       WHERE r.id = ? AND e.organizer_id = ?`,
      [id, req.user.organizer_id]
    );

    if (!registrations.length) {
      return res.status(404).json({ error: 'Inscription non trouvée' });
    }

    if (status) {
      await pool.execute(
        'UPDATE registrations SET status = ?, updated_at = NOW() WHERE id = ?',
        [status, id]
      );
    }

    const [updated] = await pool.execute(
      'SELECT * FROM registrations WHERE id = ?',
      [id]
    );

    res.json({ registration: updated[0] });
  } catch (error) {
    console.error('Erreur mise à jour inscription:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

