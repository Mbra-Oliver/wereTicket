const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, authorize, checkEventAccess } = require('../middleware/auth');

const router = express.Router();

// Tous les routes nécessitent une authentification
router.use(authenticate);

// Lister les événements
router.get('/', async (req, res) => {
  try {
    const { status, date_from, date_to } = req.query;
    let query = `
      SELECT e.*, o.name as organizer_name,
             COUNT(DISTINCT r.id) as registration_count,
             COUNT(DISTINCT c.id) as checkin_count
      FROM events e
      LEFT JOIN organizers o ON e.organizer_id = o.id
      LEFT JOIN registrations r ON e.id = r.event_id
      LEFT JOIN checkins c ON r.id = c.registration_id
      WHERE 1=1
    `;
    const params = [];

    // Filtre par organisateur (sauf super-admin)
    if (req.user.role !== 'super_admin') {
      query += ' AND e.organizer_id = ?';
      params.push(req.user.organizer_id);
    }

    // Filtres optionnels
    if (status) {
      query += ' AND e.status = ?';
      params.push(status);
    }
    if (date_from) {
      query += ' AND e.start_date >= ?';
      params.push(date_from);
    }
    if (date_to) {
      query += ' AND e.end_date <= ?';
      params.push(date_to);
    }

    query += ' GROUP BY e.id ORDER BY e.created_at DESC';

    const [events] = await pool.execute(query, params);

    res.json({ events });
  } catch (error) {
    console.error('Erreur liste événements:', error);
    res.status(500).json({ error: 'Erreur lors de la récupération des événements' });
  }
});

// Obtenir un événement
router.get('/:eventId', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;

    const [events] = await pool.execute(
      `SELECT e.*, o.name as organizer_name
       FROM events e
       LEFT JOIN organizers o ON e.organizer_id = o.id
       WHERE e.id = ?`,
      [eventId]
    );

    if (!events.length) {
      return res.status(404).json({ error: 'Événement non trouvé' });
    }

    // Récupérer les statistiques
    const [stats] = await pool.execute(
      `SELECT 
        COUNT(DISTINCT r.id) as total_registrations,
        COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN r.id END) as checked_in,
        COUNT(DISTINCT CASE WHEN r.status = 'cancelled' THEN r.id END) as cancelled
       FROM registrations r
       LEFT JOIN checkins c ON r.id = c.registration_id
       WHERE r.event_id = ?`,
      [eventId]
    );

    res.json({
      event: events[0],
      statistics: stats[0]
    });
  } catch (error) {
    console.error('Erreur récupération événement:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un événement
router.post('/', [
  body('name').trim().notEmpty().withMessage('Le nom est requis'),
  body('start_date').isISO8601().withMessage('Date de début invalide'),
  body('event_type').notEmpty(),
  body('format').isIn(['in-person', 'online', 'hybrid']),
  body('ticketing_type').isIn(['free', 'paid'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const {
      name,
      start_date,
      end_date,
      event_type,
      format,
      ticketing_type,
      registration_type,
      logo_url,
      timezone,
      language,
      currency,
      description
    } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO events (
        organizer_id, name, start_date, end_date, event_type, format,
        ticketing_type, registration_type, logo_url, timezone, language,
        currency, description, status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())`,
      [
        req.user.organizer_id,
        name,
        start_date,
        end_date || null,
        event_type,
        format,
        ticketing_type,
        registration_type || 'single',
        logo_url || null,
        timezone || 'Europe/Paris',
        language || 'fr',
        currency || 'EUR',
        description || null
      ]
    );

    const [events] = await pool.execute(
      'SELECT * FROM events WHERE id = ?',
      [result.insertId]
    );

    res.status(201).json({ event: events[0] });
  } catch (error) {
    console.error('Erreur création événement:', error);
    res.status(500).json({ error: 'Erreur lors de la création de l\'événement' });
  }
});

// Mettre à jour un événement
router.put('/:eventId', checkEventAccess, [
  body('name').optional().trim().notEmpty()
], async (req, res) => {
  try {
    const { eventId } = req.params;
    const updates = req.body;
    const allowedFields = [
      'name', 'start_date', 'end_date', 'event_type', 'format',
      'ticketing_type', 'registration_type', 'logo_url', 'timezone',
      'language', 'currency', 'description', 'status'
    ];

    const fieldsToUpdate = [];
    const values = [];

    Object.keys(updates).forEach(key => {
      if (allowedFields.includes(key)) {
        fieldsToUpdate.push(`${key} = ?`);
        values.push(updates[key]);
      }
    });

    if (fieldsToUpdate.length === 0) {
      return res.status(400).json({ error: 'Aucun champ valide à mettre à jour' });
    }

    values.push(eventId);
    const query = `UPDATE events SET ${fieldsToUpdate.join(', ')}, updated_at = NOW() WHERE id = ?`;

    await pool.execute(query, values);

    const [events] = await pool.execute(
      'SELECT * FROM events WHERE id = ?',
      [eventId]
    );

    res.json({ event: events[0] });
  } catch (error) {
    console.error('Erreur mise à jour événement:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Dupliquer un événement
router.post('/:eventId/duplicate', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;

    // Récupérer l'événement original
    const [events] = await pool.execute(
      'SELECT * FROM events WHERE id = ?',
      [eventId]
    );

    if (!events.length) {
      return res.status(404).json({ error: 'Événement non trouvé' });
    }

    const originalEvent = events[0];

    // Créer la copie
    const [result] = await pool.execute(
      `INSERT INTO events (
        organizer_id, name, start_date, end_date, event_type, format,
        ticketing_type, registration_type, logo_url, timezone, language,
        currency, description, status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())`,
      [
        originalEvent.organizer_id,
        `${originalEvent.name} (Copie)`,
        originalEvent.start_date,
        originalEvent.end_date,
        originalEvent.event_type,
        originalEvent.format,
        originalEvent.ticketing_type,
        originalEvent.registration_type,
        originalEvent.logo_url,
        originalEvent.timezone,
        originalEvent.language,
        originalEvent.currency,
        originalEvent.description
      ]
    );

    const [newEvents] = await pool.execute(
      'SELECT * FROM events WHERE id = ?',
      [result.insertId]
    );

    res.status(201).json({ event: newEvents[0] });
  } catch (error) {
    console.error('Erreur duplication événement:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer/Archiver un événement
router.delete('/:eventId', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;
    const { permanent } = req.query;

    if (permanent === 'true') {
      // Suppression définitive (attention: supprime aussi les données associées)
      await pool.execute('DELETE FROM events WHERE id = ?', [eventId]);
      res.json({ message: 'Événement supprimé définitivement' });
    } else {
      // Archivage
      await pool.execute(
        'UPDATE events SET status = "archived", updated_at = NOW() WHERE id = ?',
        [eventId]
      );
      res.json({ message: 'Événement archivé' });
    }
  } catch (error) {
    console.error('Erreur suppression événement:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

