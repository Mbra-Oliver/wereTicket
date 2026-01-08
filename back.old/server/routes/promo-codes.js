const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, checkEventAccess } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les codes promo d'un événement
router.get('/events/:eventId', checkEventAccess, async (req, res) => {
  try {
    const { eventId } = req.params;

    const [promoCodes] = await pool.execute(
      `SELECT * FROM promo_codes 
       WHERE event_id = ? 
       ORDER BY created_at DESC`,
      [eventId]
    );

    res.json({ promo_codes: promoCodes });
  } catch (error) {
    console.error('Erreur liste codes promo:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un code promo
router.post('/events/:eventId', [
  checkEventAccess,
  body('code').trim().notEmpty().isLength({ min: 3, max: 50 }),
  body('discount_type').isIn(['percentage', 'fixed']),
  body('discount_value').isFloat({ min: 0 }),
  body('max_uses').optional().isInt({ min: 1 }),
  body('valid_from').optional().isISO8601(),
  body('valid_until').optional().isISO8601()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { eventId } = req.params;
    const { code, discount_type, discount_value, max_uses, valid_from, valid_until } = req.body;

    // Vérifier l'unicité du code pour cet événement
    const [existing] = await pool.execute(
      'SELECT id FROM promo_codes WHERE event_id = ? AND code = ?',
      [eventId, code.toUpperCase()]
    );

    if (existing.length > 0) {
      return res.status(400).json({ error: 'Ce code promo existe déjà pour cet événement' });
    }

    // Valider les valeurs selon le type
    if (discount_type === 'percentage' && discount_value > 100) {
      return res.status(400).json({ error: 'Le pourcentage ne peut pas dépasser 100%' });
    }

    const [result] = await pool.execute(
      `INSERT INTO promo_codes (
        event_id, code, discount_type, discount_value, max_uses,
        valid_from, valid_until, status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())`,
      [
        eventId,
        code.toUpperCase(),
        discount_type,
        discount_value,
        max_uses || null,
        valid_from || null,
        valid_until || null
      ]
    );

    const [promoCodes] = await pool.execute(
      'SELECT * FROM promo_codes WHERE id = ?',
      [result.insertId]
    );

    res.status(201).json({ promo_code: promoCodes[0] });
  } catch (error) {
    console.error('Erreur création code promo:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Valider un code promo (pour l'utilisateur)
router.post('/validate', [
  body('event_id').isInt(),
  body('code').trim().notEmpty(),
  body('amount').isFloat({ min: 0 })
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { event_id, code, amount } = req.body;

    const [promoCodes] = await pool.execute(
      `SELECT * FROM promo_codes 
       WHERE event_id = ? AND code = ? AND status = 'active'`,
      [event_id, code.toUpperCase()]
    );

    if (!promoCodes.length) {
      return res.status(404).json({ error: 'Code promo invalide ou expiré' });
    }

    const promoCode = promoCodes[0];

    // Vérifier les dates de validité
    const now = new Date();
    if (promoCode.valid_from && new Date(promoCode.valid_from) > now) {
      return res.status(400).json({ error: 'Ce code promo n\'est pas encore valide' });
    }
    if (promoCode.valid_until && new Date(promoCode.valid_until) < now) {
      await pool.execute(
        'UPDATE promo_codes SET status = "expired" WHERE id = ?',
        [promoCode.id]
      );
      return res.status(400).json({ error: 'Ce code promo a expiré' });
    }

    // Vérifier le nombre d'utilisations
    if (promoCode.max_uses && promoCode.used_count >= promoCode.max_uses) {
      return res.status(400).json({ error: 'Ce code promo a atteint sa limite d\'utilisations' });
    }

    // Calculer la réduction
    let discountAmount = 0;
    if (promoCode.discount_type === 'percentage') {
      discountAmount = (amount * promoCode.discount_value) / 100;
    } else {
      discountAmount = Math.min(promoCode.discount_value, amount);
    }

    const finalAmount = Math.max(0, amount - discountAmount);

    res.json({
      valid: true,
      promo_code: {
        id: promoCode.id,
        code: promoCode.code,
        discount_type: promoCode.discount_type,
        discount_value: promoCode.discount_value
      },
      discount_amount: discountAmount,
      original_amount: amount,
      final_amount: finalAmount
    });
  } catch (error) {
    console.error('Erreur validation code promo:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un code promo
router.put('/:promoCodeId', [
  body('code').optional().trim().isLength({ min: 3, max: 50 }),
  body('discount_type').optional().isIn(['percentage', 'fixed']),
  body('discount_value').optional().isFloat({ min: 0 }),
  body('max_uses').optional().isInt({ min: 1 }),
  body('valid_from').optional().isISO8601(),
  body('valid_until').optional().isISO8601(),
  body('status').optional().isIn(['active', 'inactive', 'expired'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { promoCodeId } = req.params;
    const { code, discount_type, discount_value, max_uses, valid_from, valid_until, status } = req.body;

    // Vérifier que le code appartient à un événement de l'organisateur
    const [promoCodes] = await pool.execute(
      `SELECT pc.* FROM promo_codes pc
       JOIN events e ON pc.event_id = e.id
       WHERE pc.id = ? AND e.organizer_id = ?`,
      [promoCodeId, req.user.organizer_id]
    );

    if (!promoCodes.length) {
      return res.status(404).json({ error: 'Code promo non trouvé' });
    }

    const updates = [];
    const params = [];

    if (code !== undefined) {
      updates.push('code = ?');
      params.push(code.toUpperCase());
    }
    if (discount_type !== undefined) {
      updates.push('discount_type = ?');
      params.push(discount_type);
    }
    if (discount_value !== undefined) {
      updates.push('discount_value = ?');
      params.push(discount_value);
    }
    if (max_uses !== undefined) {
      updates.push('max_uses = ?');
      params.push(max_uses);
    }
    if (valid_from !== undefined) {
      updates.push('valid_from = ?');
      params.push(valid_from);
    }
    if (valid_until !== undefined) {
      updates.push('valid_until = ?');
      params.push(valid_until);
    }
    if (status !== undefined) {
      updates.push('status = ?');
      params.push(status);
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune donnée à mettre à jour' });
    }

    updates.push('updated_at = NOW()');
    params.push(promoCodeId);

    await pool.execute(
      `UPDATE promo_codes SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updatedPromoCodes] = await pool.execute(
      'SELECT * FROM promo_codes WHERE id = ?',
      [promoCodeId]
    );

    res.json({ promo_code: updatedPromoCodes[0] });
  } catch (error) {
    console.error('Erreur mise à jour code promo:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un code promo
router.delete('/:promoCodeId', async (req, res) => {
  try {
    const { promoCodeId } = req.params;

    const [promoCodes] = await pool.execute(
      `SELECT pc.id FROM promo_codes pc
       JOIN events e ON pc.event_id = e.id
       WHERE pc.id = ? AND e.organizer_id = ?`,
      [promoCodeId, req.user.organizer_id]
    );

    if (!promoCodes.length) {
      return res.status(404).json({ error: 'Code promo non trouvé' });
    }

    await pool.execute('DELETE FROM promo_codes WHERE id = ?', [promoCodeId]);

    res.json({ message: 'Code promo supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression code promo:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
