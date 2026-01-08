const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();

// Inscription organisateur
router.post('/register', [
  body('email').isEmail().normalizeEmail(),
  body('password').isLength({ min: 8 }).matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/),
  body('first_name').trim().notEmpty(),
  body('last_name').trim().notEmpty(),
  body('company_name').trim().notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, password, first_name, last_name, company_name } = req.body;

    // Vérifier si l'email existe déjà
    const [existing] = await pool.execute(
      'SELECT id FROM users WHERE email = ?',
      [email]
    );

    if (existing.length > 0) {
      return res.status(400).json({ error: 'Cet email est déjà utilisé' });
    }

    // Créer l'organisateur
    const connection = await pool.getConnection();
    await connection.beginTransaction();

    try {
      // Créer l'organisateur
      const [organizerResult] = await connection.execute(
        'INSERT INTO organizers (name, status, created_at) VALUES (?, ?, NOW())',
        [company_name, 'active']
      );
      const organizerId = organizerResult.insertId;

      // Hasher le mot de passe
      const hashedPassword = await bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS || 12));

      // Créer l'utilisateur
      const [userResult] = await connection.execute(
        `INSERT INTO users (email, password, first_name, last_name, role, organizer_id, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
        [email, hashedPassword, first_name, last_name, 'organizer', organizerId, 'active']
      );

      await connection.commit();
      connection.release();

      // Générer le token
      const token = jwt.sign(
        { userId: userResult.insertId, organizerId, role: 'organizer' },
        process.env.JWT_SECRET,
        { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
      );

      res.status(201).json({
        message: 'Compte créé avec succès',
        token,
        user: {
          id: userResult.insertId,
          email,
          first_name,
          last_name,
          role: 'organizer',
          organizer_id: organizerId
        }
      });
    } catch (error) {
      await connection.rollback();
      connection.release();
      throw error;
    }
  } catch (error) {
    console.error('Erreur inscription:', error);
    console.error('Stack:', error.stack);
    // Écrire dans un fichier de log aussi
    const fs = require('fs');
    const path = require('path');
    const logPath = path.join(__dirname, '..', 'logs', 'auth.log');
    try {
      fs.appendFileSync(logPath, `[${new Date().toISOString()}] Erreur inscription: ${error.message}\n${error.stack}\n\n`);
    } catch (logError) {
      console.error('Impossible d\'écrire dans le log:', logError);
    }
    // Retourner plus de détails pour le debug
    res.status(500).json({ 
      error: 'Erreur lors de l\'inscription',
      message: error.message,
      code: error.code
    });
  }
});

// Connexion
router.post('/login', [
  body('email').isEmail().normalizeEmail(),
  body('password').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, password } = req.body;

    // Trouver l'utilisateur
    const [users] = await pool.execute(
      `SELECT u.id, u.email, u.password, u.first_name, u.last_name, u.role, 
              u.organizer_id, u.status, o.name as organizer_name
       FROM users u
       LEFT JOIN organizers o ON u.organizer_id = o.id
       WHERE u.email = ?`,
      [email]
    );

    if (!users.length) {
      return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
    }

    const user = users[0];

    if (user.status !== 'active') {
      return res.status(403).json({ error: 'Compte désactivé' });
    }

    // Vérifier le mot de passe
    const isValid = await bcrypt.compare(password, user.password);
    if (!isValid) {
      return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
    }

    // Générer le token
    const token = jwt.sign(
      { userId: user.id, organizerId: user.organizer_id, role: user.role },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );

    res.json({
      token,
      user: {
        id: user.id,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        role: user.role,
        organizer_id: user.organizer_id,
        organizer_name: user.organizer_name
      }
    });
  } catch (error) {
    console.error('Erreur connexion:', error);
    res.status(500).json({ error: 'Erreur lors de la connexion' });
  }
});

// Vérifier le token
router.get('/me', authenticate, async (req, res) => {
  try {
    const [users] = await pool.execute(
      `SELECT u.id, u.email, u.first_name, u.last_name, u.role, 
              u.organizer_id, o.name as organizer_name
       FROM users u
       LEFT JOIN organizers o ON u.organizer_id = o.id
       WHERE u.id = ?`,
      [req.user.id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    res.json({ user: users[0] });
  } catch (error) {
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mot de passe oublié
router.post('/forgot-password', [
  body('email').isEmail().normalizeEmail()
], async (req, res) => {
  try {
    const { email } = req.body;
    
    // Trouver l'utilisateur
    const [users] = await pool.execute(
      'SELECT id, email, first_name, last_name FROM users WHERE email = ?',
      [email]
    );

    // Toujours retourner le même message pour la sécurité
    if (!users.length) {
      return res.json({ message: 'Si cet email existe, un lien de réinitialisation a été envoyé' });
    }

    const user = users[0];

    // Générer un token de réinitialisation (valide 1 heure)
    const resetToken = jwt.sign(
      { userId: user.id, type: 'password_reset' },
      process.env.JWT_SECRET,
      { expiresIn: '1h' }
    );

    // Stocker le token dans la base (ou utiliser une table dédiée)
    // Pour simplifier, on peut utiliser une table password_reset_tokens
    await pool.execute(
      `INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
       VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
       ON DUPLICATE KEY UPDATE token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)`,
      [user.id, resetToken, resetToken]
    );

    // Envoyer l'email
    const resetUrl = `${process.env.CLIENT_URL || 'https://back.yebticket.com'}/reset-password?token=${resetToken}`;
    
    try {
      const { sendEmail } = require('../utils/email');
      await sendEmail({
        to: user.email,
        subject: 'Réinitialisation de votre mot de passe',
        html: `
          <h2>Bonjour ${user.first_name},</h2>
          <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
          <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
          <p><a href="${resetUrl}">${resetUrl}</a></p>
          <p>Ce lien est valide pendant 1 heure.</p>
          <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        `
      });
    } catch (emailError) {
      console.error('Erreur envoi email:', emailError);
      // Ne pas révéler l'erreur à l'utilisateur
    }

    res.json({ message: 'Si cet email existe, un lien de réinitialisation a été envoyé' });
  } catch (error) {
    console.error('Erreur mot de passe oublié:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Réinitialiser le mot de passe
router.post('/reset-password', [
  body('token').notEmpty(),
  body('password').isLength({ min: 8 }).matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { token, password } = req.body;

    // Vérifier le token
    let decoded;
    try {
      decoded = jwt.verify(token, process.env.JWT_SECRET);
      if (decoded.type !== 'password_reset') {
        return res.status(400).json({ error: 'Token invalide' });
      }
    } catch (error) {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }

    // Vérifier que le token existe dans la base et n'est pas expiré
    const [tokens] = await pool.execute(
      'SELECT * FROM password_reset_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()',
      [decoded.userId, token]
    );

    if (!tokens.length) {
      return res.status(400).json({ error: 'Token invalide ou expiré' });
    }

    // Hasher le nouveau mot de passe
    const hashedPassword = await bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS || 12));

    // Mettre à jour le mot de passe
    await pool.execute(
      'UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?',
      [hashedPassword, decoded.userId]
    );

    // Supprimer le token utilisé
    await pool.execute(
      'DELETE FROM password_reset_tokens WHERE user_id = ? AND token = ?',
      [decoded.userId, token]
    );

    res.json({ message: 'Mot de passe réinitialisé avec succès' });
  } catch (error) {
    console.error('Erreur réinitialisation mot de passe:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Changer le mot de passe (utilisateur connecté)
router.post('/change-password', authenticate, [
  body('current_password').notEmpty(),
  body('new_password').isLength({ min: 8 }).matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { current_password, new_password } = req.body;

    // Récupérer l'utilisateur avec son mot de passe
    const [users] = await pool.execute(
      'SELECT id, password FROM users WHERE id = ?',
      [req.user.id]
    );

    if (!users.length) {
      return res.status(404).json({ error: 'Utilisateur non trouvé' });
    }

    // Vérifier le mot de passe actuel
    const isValid = await bcrypt.compare(current_password, users[0].password);
    if (!isValid) {
      return res.status(401).json({ error: 'Mot de passe actuel incorrect' });
    }

    // Hasher le nouveau mot de passe
    const hashedPassword = await bcrypt.hash(new_password, parseInt(process.env.BCRYPT_ROUNDS || 12));

    // Mettre à jour
    await pool.execute(
      'UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?',
      [hashedPassword, req.user.id]
    );

    res.json({ message: 'Mot de passe modifié avec succès' });
  } catch (error) {
    console.error('Erreur changement mot de passe:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

