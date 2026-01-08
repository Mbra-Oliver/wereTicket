const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les types de tickets
router.get('/', async (req, res) => {
  try {
    const { event_id } = req.query;
    let query = 'SELECT * FROM ticket_types WHERE 1=1';
    const params = [];

    if (event_id) {
      query += ' AND event_id = ?';
      params.push(event_id);
    }

    query += ' ORDER BY created_at DESC';

    const [tickets] = await pool.execute(query, params);
    res.json({ tickets });
  } catch (error) {
    console.error('Erreur liste tickets:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un type de ticket
router.post('/', [
  body('event_id').isInt(),
  body('name').trim().notEmpty(),
  body('price').isFloat({ min: 0 })
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { event_id, name, description, price, currency, quantity, available_from, available_until } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO ticket_types (
        event_id, name, description, price, currency, quantity,
        available_from, available_until, status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())`,
      [event_id, name, description || null, price, currency || 'EUR', quantity || null, available_from || null, available_until || null]
    );

    const [tickets] = await pool.execute('SELECT * FROM ticket_types WHERE id = ?', [result.insertId]);
    res.status(201).json({ ticket: tickets[0] });
  } catch (error) {
    console.error('Erreur création ticket:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Récupérer un type de ticket
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const [tickets] = await pool.execute(
      'SELECT * FROM ticket_types WHERE id = ?',
      [id]
    );

    if (!tickets.length) {
      return res.status(404).json({ error: 'Ticket non trouvé' });
    }

    res.json({ ticket: tickets[0] });
  } catch (error) {
    console.error('Erreur récupération ticket:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un type de ticket
router.put('/:id', [
  body('name').optional().trim().notEmpty(),
  body('price').optional().isFloat({ min: 0 })
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { name, description, price, currency, quantity, available_from, available_until, status } = req.body;

    const updates = [];
    const params = [];

    if (name !== undefined) {
      updates.push('name = ?');
      params.push(name);
    }
    if (description !== undefined) {
      updates.push('description = ?');
      params.push(description);
    }
    if (price !== undefined) {
      updates.push('price = ?');
      params.push(price);
    }
    if (currency !== undefined) {
      updates.push('currency = ?');
      params.push(currency);
    }
    if (quantity !== undefined) {
      updates.push('quantity = ?');
      params.push(quantity || null);
    }
    if (available_from !== undefined) {
      updates.push('available_from = ?');
      params.push(available_from || null);
    }
    if (available_until !== undefined) {
      updates.push('available_until = ?');
      params.push(available_until || null);
    }
    if (status !== undefined) {
      updates.push('status = ?');
      params.push(status);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune modification à apporter' });
    }

    updates.push('updated_at = NOW()');
    params.push(id);

    await pool.execute(
      `UPDATE ticket_types SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updated] = await pool.execute(
      'SELECT * FROM ticket_types WHERE id = ?',
      [id]
    );

    res.json({ ticket: updated[0] });
  } catch (error) {
    console.error('Erreur mise à jour ticket:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un type de ticket
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;

    // Vérifier si des tickets ont été vendus
    const [tickets] = await pool.execute(
      'SELECT sold FROM ticket_types WHERE id = ?',
      [id]
    );

    if (!tickets.length) {
      return res.status(404).json({ error: 'Ticket non trouvé' });
    }

    if (tickets[0].sold > 0) {
      return res.status(400).json({ error: 'Impossible de supprimer un ticket avec des ventes' });
    }

    await pool.execute('DELETE FROM ticket_types WHERE id = ?', [id]);

    res.json({ message: 'Ticket supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression ticket:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

