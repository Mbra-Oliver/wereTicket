const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Fonction pour appliquer les conditions d'un segment
async function applySegmentConditions(organizerId, conditions) {
  if (!conditions || !Array.isArray(conditions) || conditions.length === 0) {
    return { query: 'SELECT * FROM contacts WHERE organizer_id = ?', params: [organizerId] };
  }

  let query = 'SELECT * FROM contacts WHERE organizer_id = ?';
  const params = [organizerId];

  conditions.forEach((condition, index) => {
    const { field, operator, value } = condition;

    if (field === 'email' || field === 'first_name' || field === 'last_name' || 
        field === 'company' || field === 'job_title' || field === 'phone' || 
        field === 'contact_type' || field === 'status') {
      // Champs standards
      if (operator === 'equals') {
        query += ` AND ${field} = ?`;
        params.push(value);
      } else if (operator === 'contains') {
        query += ` AND ${field} LIKE ?`;
        params.push(`%${value}%`);
      } else if (operator === 'starts_with') {
        query += ` AND ${field} LIKE ?`;
        params.push(`${value}%`);
      } else if (operator === 'not_equals') {
        query += ` AND ${field} != ?`;
        params.push(value);
      }
    } else {
      // Champs personnalisés dans custom_fields JSON
      if (operator === 'equals') {
        query += ` AND JSON_EXTRACT(custom_fields, ?) = ?`;
        params.push(`$.${field}`, value);
      } else if (operator === 'contains') {
        query += ` AND JSON_EXTRACT(custom_fields, ?) LIKE ?`;
        params.push(`$.${field}`, `%${value}%`);
      }
    }

    // Gérer les opérateurs logiques (AND/OR)
    if (index < conditions.length - 1 && condition.logic) {
      query += ` ${condition.logic}`;
    }
  });

  return { query, params };
}

// Lister les segments
router.get('/', async (req, res) => {
  try {
    const { favorite_only } = req.query;
    let query = 'SELECT * FROM segments WHERE organizer_id = ?';
    const params = [req.user.organizer_id];

    if (favorite_only === 'true') {
      query += ' AND is_favorite = 1';
    }

    query += ' ORDER BY is_favorite DESC, created_at DESC';

    const [segments] = await pool.execute(query, params);

    // Compter les contacts pour chaque segment
    const segmentsWithCounts = await Promise.all(
      segments.map(async (segment) => {
        let conditions = [];
        try {
          if (segment.conditions) {
            conditions = typeof segment.conditions === 'string' ? JSON.parse(segment.conditions) : segment.conditions;
          }
        } catch (e) {
          console.error('Error parsing segment conditions:', e, segment.id);
          conditions = [];
        }
        const { query: segmentQuery, params: segmentParams } = await applySegmentConditions(
          req.user.organizer_id,
          conditions
        );
        const [contacts] = await pool.execute(segmentQuery, segmentParams);
        return {
          ...segment,
          conditions: conditions,
          contact_count: contacts.length
        };
      })
    );

    res.json({ segments: segmentsWithCounts });
  } catch (error) {
    console.error('Erreur liste segments:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un segment
router.post('/', [
  body('name').trim().notEmpty(),
  body('conditions').isArray(),
  body('is_favorite').optional().isBoolean()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, conditions, is_favorite = false } = req.body;

    // Valider les conditions
    if (!Array.isArray(conditions) || conditions.length === 0) {
      return res.status(400).json({ error: 'Au moins une condition est requise' });
    }

    const [result] = await pool.execute(
      `INSERT INTO segments (organizer_id, name, conditions, is_favorite, created_at)
       VALUES (?, ?, ?, ?, NOW())`,
      [req.user.organizer_id, name, JSON.stringify(conditions), is_favorite ? 1 : 0]
    );

    const [segments] = await pool.execute('SELECT * FROM segments WHERE id = ?', [result.insertId]);
    let parsedConditions = [];
    try {
      if (segments[0].conditions) {
        parsedConditions = typeof segments[0].conditions === 'string' ? JSON.parse(segments[0].conditions) : segments[0].conditions;
      }
    } catch (e) {
      console.error('Error parsing conditions:', e);
    }
    const segment = {
      ...segments[0],
      conditions: parsedConditions
    };

    res.status(201).json({ segment });
  } catch (error) {
    console.error('Erreur création segment:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un segment
router.put('/:segmentId', [
  body('name').optional().trim().notEmpty(),
  body('conditions').optional().isArray(),
  body('is_favorite').optional().isBoolean()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { segmentId } = req.params;
    const { name, conditions, is_favorite } = req.body;

    // Vérifier que le segment appartient à l'organisateur
    const [segments] = await pool.execute(
      'SELECT * FROM segments WHERE id = ? AND organizer_id = ?',
      [segmentId, req.user.organizer_id]
    );

    if (!segments.length) {
      return res.status(404).json({ error: 'Segment non trouvé' });
    }

    const updates = [];
    const params = [];

    if (name !== undefined) {
      updates.push('name = ?');
      params.push(name);
    }
    if (conditions !== undefined) {
      updates.push('conditions = ?');
      params.push(JSON.stringify(conditions));
    }
    if (is_favorite !== undefined) {
      updates.push('is_favorite = ?');
      params.push(is_favorite ? 1 : 0);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune donnée à mettre à jour' });
    }

    params.push(segmentId);

    await pool.execute(
      `UPDATE segments SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updatedSegments] = await pool.execute(
      'SELECT * FROM segments WHERE id = ?',
      [segmentId]
    );

    let parsedConditions = [];
    try {
      if (updatedSegments[0].conditions) {
        parsedConditions = typeof updatedSegments[0].conditions === 'string' ? JSON.parse(updatedSegments[0].conditions) : updatedSegments[0].conditions;
      }
    } catch (e) {
      console.error('Error parsing conditions:', e);
    }
    const segment = {
      ...updatedSegments[0],
      conditions: parsedConditions
    };

    res.json({ segment });
  } catch (error) {
    console.error('Erreur mise à jour segment:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un segment
router.delete('/:segmentId', async (req, res) => {
  try {
    const { segmentId } = req.params;

    const [segments] = await pool.execute(
      'SELECT id FROM segments WHERE id = ? AND organizer_id = ?',
      [segmentId, req.user.organizer_id]
    );

    if (!segments.length) {
      return res.status(404).json({ error: 'Segment non trouvé' });
    }

    await pool.execute('DELETE FROM segments WHERE id = ?', [segmentId]);

    res.json({ message: 'Segment supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression segment:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Récupérer un segment (doit être APRÈS les routes spécifiques comme /:segmentId/contacts)
router.get('/:segmentId', async (req, res) => {
  try {
    const { segmentId } = req.params;
    const [segments] = await pool.execute(
      'SELECT * FROM segments WHERE id = ? AND organizer_id = ?',
      [segmentId, req.user.organizer_id]
    );

    if (!segments.length) {
      return res.status(404).json({ error: 'Segment non trouvé' });
    }

    const segment = segments[0];
    if (segment.conditions) {
      try {
        segment.conditions = typeof segment.conditions === 'string' ? JSON.parse(segment.conditions) : segment.conditions;
      } catch (e) {
        console.error('Error parsing segment conditions:', e);
        segment.conditions = [];
      }
    }

    res.json({ segment });
  } catch (error) {
    console.error('Erreur récupération segment:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
