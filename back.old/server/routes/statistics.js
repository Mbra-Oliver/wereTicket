const express = require('express');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Statistiques générales
router.get('/dashboard', async (req, res) => {
  try {
    const organizerId = req.user.organizer_id;

    // Statistiques événements
    const [eventStats] = await pool.execute(
      `SELECT 
        COUNT(*) as total_events,
        COUNT(CASE WHEN status = 'published' THEN 1 END) as published_events,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_events
       FROM events
       WHERE organizer_id = ?`,
      [organizerId]
    );

    // Statistiques inscriptions
    const [registrationStats] = await pool.execute(
      `SELECT 
        COUNT(DISTINCT r.id) as total_registrations,
        COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN r.id END) as checked_in
       FROM registrations r
       JOIN events e ON r.event_id = e.id
       LEFT JOIN checkins c ON r.id = c.registration_id
       WHERE e.organizer_id = ?`,
      [organizerId]
    );

    // Statistiques contacts
    const [contactStats] = await pool.execute(
      `SELECT COUNT(*) as total_contacts
       FROM contacts
       WHERE organizer_id = ?`,
      [organizerId]
    );

    res.json({
      events: eventStats[0],
      registrations: registrationStats[0],
      contacts: contactStats[0]
    });
  } catch (error) {
    console.error('Erreur statistiques:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Statistiques par événement
router.get('/event/:eventId', async (req, res) => {
  try {
    const { eventId } = req.params;

    // Vérifier l'accès à l'événement
    const [events] = await pool.execute(
      `SELECT e.* FROM events e
       WHERE e.id = ? AND (e.organizer_id = ? OR EXISTS (
         SELECT 1 FROM event_users eu 
         WHERE eu.event_id = e.id AND eu.user_id = ?
       ))`,
      [eventId, req.user.organizer_id, req.user.id]
    );

    if (!events.length) {
      return res.status(404).json({ error: 'Événement non trouvé' });
    }

    // Statistiques générales
    const [stats] = await pool.execute(
      `SELECT 
        COUNT(DISTINCT r.id) as total_registrations,
        COUNT(DISTINCT CASE WHEN r.status = 'confirmed' THEN r.id END) as confirmed,
        COUNT(DISTINCT CASE WHEN r.status = 'cancelled' THEN r.id END) as cancelled,
        COUNT(DISTINCT CASE WHEN r.status = 'pending' THEN r.id END) as pending,
        COUNT(DISTINCT CASE WHEN r.status = 'waitlist' THEN r.id END) as waitlist,
        COUNT(DISTINCT c.id) as checked_in,
        COUNT(DISTINCT CASE WHEN c.id IS NULL AND r.status = 'confirmed' THEN r.id END) as no_show
       FROM registrations r
       LEFT JOIN checkins c ON r.id = c.registration_id
       WHERE r.event_id = ?`,
      [eventId]
    );

    // Statistiques par checkpoint
    const [checkpointStats] = await pool.execute(
      `SELECT 
        cp.id, cp.name, cp.location,
        COUNT(DISTINCT c.id) as checkin_count
       FROM checkpoints cp
       LEFT JOIN checkins c ON cp.id = c.checkpoint_id
       WHERE cp.event_id = ?
       GROUP BY cp.id
       ORDER BY checkin_count DESC`,
      [eventId]
    );

    // Évolution des check-ins par heure (dernières 24h)
    const [timelineStats] = await pool.execute(
      `SELECT 
        DATE_FORMAT(c.checked_in_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as count
       FROM checkins c
       JOIN registrations r ON c.registration_id = r.id
       WHERE r.event_id = ? 
         AND c.checked_in_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
       GROUP BY hour
       ORDER BY hour ASC`,
      [eventId]
    );

    // Statistiques de revenus (si événement payant)
    const [revenueStats] = await pool.execute(
      `SELECT 
        SUM(o.total_amount) as total_revenue,
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT CASE WHEN o.payment_status = 'paid' THEN o.id END) as paid_orders,
        SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as paid_revenue
       FROM orders o
       WHERE o.event_id = ?`,
      [eventId]
    );

    res.json({
      statistics: stats[0],
      checkpoints: checkpointStats,
      timeline: timelineStats,
      revenue: revenueStats[0]
    });
  } catch (error) {
    console.error('Erreur stats événement:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

