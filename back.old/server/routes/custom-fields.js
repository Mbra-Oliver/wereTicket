const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les champs personnalisés
router.get('/', async (req, res) => {
  try {
    const [fields] = await pool.execute(
      'SELECT * FROM custom_fields WHERE organizer_id = ? ORDER BY `order` ASC, created_at ASC',
      [req.user.organizer_id]
    );

    // Parser les options JSON
    const formattedFields = fields.map(field => {
      if (field.options) {
        field.options = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
      }
      return field;
    });
    res.json({ fields: formattedFields });
  } catch (error) {
    console.error('Erreur liste champs personnalisés:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un champ personnalisé
router.post('/', [
  body('name').trim().notEmpty(),
  body('type').isIn(['text', 'textarea', 'email', 'phone', 'number', 'date', 'select', 'checkbox', 'file']),
  body('slug').optional().trim(),
  body('options').optional().isObject(),
  body('is_required').optional().isBoolean(),
  body('is_visible_on_form').optional().isBoolean(),
  body('is_used_for_segmentation').optional().isBoolean(),
  body('order').optional().isInt()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const {
      name,
      type,
      slug,
      options,
      is_required = false,
      is_visible_on_form = true,
      is_used_for_segmentation = false,
      order = 0
    } = req.body;

    // Générer un slug si non fourni
    const fieldSlug = slug || name.toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');

    // Vérifier l'unicité du slug
    const [existing] = await pool.execute(
      'SELECT id FROM custom_fields WHERE organizer_id = ? AND slug = ?',
      [req.user.organizer_id, fieldSlug]
    );

    if (existing.length > 0) {
      return res.status(400).json({ error: 'Un champ avec ce slug existe déjà' });
    }

    // Valider les options selon le type
    let validatedOptions = null;
    if (type === 'select' && Array.isArray(options)) {
      validatedOptions = options;
    } else if (options && Array.isArray(options)) {
      validatedOptions = options;
    }

    const [result] = await pool.execute(
      `INSERT INTO custom_fields (
        organizer_id, name, slug, type, options,
        is_required, is_visible_on_form, is_used_for_segmentation, \`order\`, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
      [
        req.user.organizer_id,
        name,
        fieldSlug,
        type,
        validatedOptions ? JSON.stringify(validatedOptions) : null,
        is_required ? 1 : 0,
        is_visible_on_form ? 1 : 0,
        is_used_for_segmentation ? 1 : 0,
        order
      ]
    );

    const [fields] = await pool.execute(
      'SELECT * FROM custom_fields WHERE id = ?',
      [result.insertId]
    );

    const field = fields[0];
    if (field.options) {
      field.options = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
    }
    res.status(201).json({ field });
  } catch (error) {
    console.error('Erreur création champ personnalisé:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un champ personnalisé
router.put('/:fieldId', [
  body('name').optional().trim().notEmpty(),
  body('type').optional().isIn(['text', 'textarea', 'email', 'phone', 'number', 'date', 'select', 'checkbox', 'file']),
  body('options').optional().isObject(),
  body('is_required').optional().isBoolean(),
  body('is_visible_on_form').optional().isBoolean(),
  body('is_used_for_segmentation').optional().isBoolean(),
  body('order').optional().isInt()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { fieldId } = req.params;
    const {
      name,
      type,
      options,
      is_required,
      is_visible_on_form,
      is_used_for_segmentation,
      order
    } = req.body;

    // Vérifier que le champ appartient à l'organisateur
    const [fields] = await pool.execute(
      'SELECT * FROM custom_fields WHERE id = ? AND organizer_id = ?',
      [fieldId, req.user.organizer_id]
    );

    if (!fields.length) {
      return res.status(404).json({ error: 'Champ personnalisé non trouvé' });
    }

    const updates = [];
    const params = [];

    if (name !== undefined) {
      updates.push('name = ?');
      params.push(name);
    }
    if (type !== undefined) {
      updates.push('type = ?');
      params.push(type);
    }
    if (options !== undefined) {
      updates.push('options = ?');
      params.push(JSON.stringify(options));
    }
    if (is_required !== undefined) {
      updates.push('is_required = ?');
      params.push(is_required ? 1 : 0);
    }
    if (is_visible_on_form !== undefined) {
      updates.push('is_visible_on_form = ?');
      params.push(is_visible_on_form ? 1 : 0);
    }
    if (is_used_for_segmentation !== undefined) {
      updates.push('is_used_for_segmentation = ?');
      params.push(is_used_for_segmentation ? 1 : 0);
    }
    if (order !== undefined) {
      updates.push('`order` = ?');
      params.push(order);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune donnée à mettre à jour' });
    }

    updates.push('updated_at = NOW()');
    params.push(fieldId);

    await pool.execute(
      `UPDATE custom_fields SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updatedFields] = await pool.execute(
      'SELECT * FROM custom_fields WHERE id = ?',
      [fieldId]
    );

    const field = updatedFields[0];
    if (field.options) {
      field.options = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
    }
    res.json({ field });
  } catch (error) {
    console.error('Erreur mise à jour champ personnalisé:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un champ personnalisé
router.delete('/:fieldId', async (req, res) => {
  try {
    const { fieldId } = req.params;

    // Vérifier que le champ appartient à l'organisateur
    const [fields] = await pool.execute(
      'SELECT id FROM custom_fields WHERE id = ? AND organizer_id = ?',
      [fieldId, req.user.organizer_id]
    );

    if (!fields.length) {
      return res.status(404).json({ error: 'Champ personnalisé non trouvé' });
    }

    await pool.execute('DELETE FROM custom_fields WHERE id = ?', [fieldId]);

    res.json({ message: 'Champ personnalisé supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression champ personnalisé:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Récupérer un champ personnalisé (alias pour compatibilité)
router.get('/:fieldId', async (req, res) => {
  try {
    const { fieldId } = req.params;
    const [fields] = await pool.execute(
      'SELECT * FROM custom_fields WHERE id = ? AND organizer_id = ?',
      [fieldId, req.user.organizer_id]
    );

    if (!fields.length) {
      return res.status(404).json({ error: 'Champ personnalisé non trouvé' });
    }

    const field = fields[0];
    if (field.options) {
      field.options = typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
    }

    res.json({ field });
  } catch (error) {
    console.error('Erreur récupération champ:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
