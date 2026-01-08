const express = require('express');
const bcrypt = require('bcryptjs');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, authorize } = require('../middleware/auth');
const { sendEmail } = require('../utils/email');

const router = express.Router();
router.use(authenticate);

// Obtenir les infos de l'organisateur
router.get('/me', async (req, res) => {
  try {
    const [organizers] = await pool.execute(
      'SELECT * FROM organizers WHERE id = ?',
      [req.user.organizer_id]
    );

    if (!organizers.length) {
      return res.status(404).json({ error: 'Organisateur non trouvé' });
    }

    res.json({ organizer: organizers[0] });
  } catch (error) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour l'organisateur
router.put('/me', [
  body('name').optional().trim().notEmpty(),
  body('timezone').optional().trim(),
  body('currency').optional().trim().isLength({ min: 3, max: 3 }),
  body('logo_url').optional().trim(),
  body('company_info').optional()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, timezone, currency, logo_url, company_info } = req.body;
    const updates = [];
    const params = [];

    if (name !== undefined) {
      updates.push('name = ?');
      params.push(name);
    }
    if (timezone !== undefined) {
      updates.push('timezone = ?');
      params.push(timezone);
    }
    if (currency !== undefined) {
      updates.push('currency = ?');
      params.push(currency);
    }
    if (logo_url !== undefined) {
      updates.push('logo_url = ?');
      params.push(logo_url);
    }
    if (company_info !== undefined) {
      updates.push('company_info = ?');
      params.push(JSON.stringify(company_info));
    }

    if (updates.length === 0) {
      return res.status(400).json({ error: 'Aucune donnée à mettre à jour' });
    }

    updates.push('updated_at = NOW()');
    params.push(req.user.organizer_id);

    await pool.execute(
      `UPDATE organizers SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [organizers] = await pool.execute(
      'SELECT * FROM organizers WHERE id = ?',
      [req.user.organizer_id]
    );

    res.json({ organizer: organizers[0] });
  } catch (error) {
    console.error('Erreur mise à jour organisateur:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Lister les collaborateurs
router.get('/collaborators', authorize('organizer', 'super_admin'), async (req, res) => {
  try {
    const [collaborators] = await pool.execute(
      `SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.status, 
              u.last_login, u.created_at,
              GROUP_CONCAT(
                CONCAT(e.id, ':', eu.permission) SEPARATOR ','
              ) as event_permissions
       FROM users u
       LEFT JOIN event_users eu ON u.id = eu.user_id
       WHERE u.organizer_id = ? AND u.role IN ('collaborator', 'checkin_host')
       GROUP BY u.id
       ORDER BY u.created_at DESC`,
      [req.user.organizer_id]
    );

    // Parser les permissions par événement
    const formatted = collaborators.map(collab => ({
      ...collab,
      event_permissions: collab.event_permissions
        ? collab.event_permissions.split(',').map(p => {
            const [eventId, permission] = p.split(':');
            return { event_id: parseInt(eventId), permission };
          })
        : []
    }));

    res.json({ collaborators: formatted });
  } catch (error) {
    console.error('Erreur liste collaborateurs:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Inviter un collaborateur
router.post('/collaborators/invite', [
  authorize('organizer', 'super_admin'),
  body('email').isEmail().normalizeEmail(),
  body('first_name').trim().notEmpty(),
  body('last_name').trim().notEmpty(),
  body('role').isIn(['collaborator', 'checkin_host']),
  body('event_permissions').optional().isArray()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, first_name, last_name, role, event_permissions = [] } = req.body;

    // Vérifier si l'email existe déjà
    const [existing] = await pool.execute(
      'SELECT id FROM users WHERE email = ?',
      [email]
    );

    if (existing.length > 0) {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }

    // Générer un mot de passe temporaire
    const tempPassword = Math.random().toString(36).slice(-12) + Math.random().toString(36).slice(-12).toUpperCase() + '1!';
    const hashedPassword = await bcrypt.hash(tempPassword, parseInt(process.env.BCRYPT_ROUNDS || 12));

    const connection = await pool.getConnection();
    await connection.beginTransaction();

    try {
      // Créer l'utilisateur
      const [userResult] = await connection.execute(
        `INSERT INTO users (email, password, first_name, last_name, role, organizer_id, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())`,
        [email, hashedPassword, first_name, last_name, role, req.user.organizer_id]
      );

      const userId = userResult.insertId;

      // Ajouter les permissions par événement
      if (event_permissions.length > 0) {
        const permissionValues = event_permissions.map(ep => [
          ep.event_id,
          userId,
          ep.permission || 'read'
        ]);

        await connection.query(
          `INSERT INTO event_users (event_id, user_id, permission) VALUES ?`,
          [permissionValues]
        );
      }

      await connection.commit();
      connection.release();

      // Envoyer l'email d'invitation
      try {
        await sendEmail({
          to: email,
          subject: 'Invitation à rejoindre la plateforme',
          html: `
            <h2>Bonjour ${first_name},</h2>
            <p>Vous avez été invité(e) à rejoindre la plateforme de gestion d'événements.</p>
            <p><strong>Email:</strong> ${email}</p>
            <p><strong>Mot de passe temporaire:</strong> ${tempPassword}</p>
            <p>Veuillez changer votre mot de passe lors de votre première connexion.</p>
            <p><a href="${process.env.CLIENT_URL || 'https://back.yebticket.com'}/login">Se connecter</a></p>
          `
        });
      } catch (emailError) {
        console.error('Erreur envoi email:', emailError);
        // Ne pas faire échouer la création si l'email échoue
      }

      const [users] = await connection.execute(
        'SELECT id, email, first_name, last_name, role, status FROM users WHERE id = ?',
        [userId]
      );

      res.status(201).json({
        message: 'Collaborateur invité avec succès',
        collaborator: users[0],
        temp_password: tempPassword // À retirer en production
      });
    } catch (error) {
      await connection.rollback();
      connection.release();
      throw error;
    }
  } catch (error) {
    console.error('Erreur invitation collaborateur:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un collaborateur
router.put('/collaborators/:userId', [
  authorize('organizer', 'super_admin'),
  body('first_name').optional().trim(),
  body('last_name').optional().trim(),
  body('role').optional().isIn(['collaborator', 'checkin_host']),
  body('status').optional().isIn(['active', 'inactive', 'suspended']),
  body('event_permissions').optional().isArray()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { userId } = req.params;
    const { first_name, last_name, role, status, event_permissions } = req.body;

    // Vérifier que le collaborateur appartient à l'organisateur
    const [users] = await pool.execute(
      'SELECT * FROM users WHERE id = ? AND organizer_id = ?',
      [userId, req.user.organizer_id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Collaborateur non trouvé' });
    }

    const connection = await pool.getConnection();
    await connection.beginTransaction();

    try {
      // Mettre à jour les infos de base
      const updates = [];
      const params = [];

      if (first_name !== undefined) {
        updates.push('first_name = ?');
        params.push(first_name);
      }
      if (last_name !== undefined) {
        updates.push('last_name = ?');
        params.push(last_name);
      }
      if (role !== undefined) {
        updates.push('role = ?');
        params.push(role);
      }
      if (status !== undefined) {
        updates.push('status = ?');
        params.push(status);
      }

      if (updates.length > 0) {
        updates.push('updated_at = NOW()');
        params.push(userId);
        await connection.execute(
          `UPDATE users SET ${updates.join(', ')} WHERE id = ?`,
          params
        );
      }

      // Mettre à jour les permissions par événement
      if (event_permissions !== undefined) {
        // Supprimer les anciennes permissions
        await connection.execute(
          'DELETE FROM event_users WHERE user_id = ?',
          [userId]
        );

        // Ajouter les nouvelles permissions
        if (event_permissions.length > 0) {
          const permissionValues = event_permissions.map(ep => [
            ep.event_id,
            userId,
            ep.permission || 'read'
          ]);

          await connection.query(
            `INSERT INTO event_users (event_id, user_id, permission) VALUES ?`,
            [permissionValues]
          );
        }
      }

      await connection.commit();
      connection.release();

      const [updatedUsers] = await pool.execute(
        'SELECT id, email, first_name, last_name, role, status FROM users WHERE id = ?',
        [userId]
      );

      res.json({ collaborator: updatedUsers[0] });
    } catch (error) {
      await connection.rollback();
      connection.release();
      throw error;
    }
  } catch (error) {
    console.error('Erreur mise à jour collaborateur:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Supprimer un collaborateur
router.delete('/collaborators/:userId', authorize('organizer', 'super_admin'), async (req, res) => {
  try {
    const { userId } = req.params;

    // Vérifier que le collaborateur appartient à l'organisateur
    const [users] = await pool.execute(
      'SELECT * FROM users WHERE id = ? AND organizer_id = ?',
      [userId, req.user.organizer_id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Collaborateur non trouvé' });
    }

    // Ne pas permettre la suppression de l'organisateur principal
    if (users[0].role === 'organizer') {
      return res.status(403).json({ error: 'Impossible de supprimer l\'organisateur principal' });
    }

    await pool.execute('DELETE FROM users WHERE id = ?', [userId]);

    res.json({ message: 'Collaborateur supprimé avec succès' });
  } catch (error) {
    console.error('Erreur suppression collaborateur:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

