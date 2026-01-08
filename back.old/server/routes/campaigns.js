const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les campagnes
router.get('/', async (req, res) => {
  try {
    const { event_id } = req.query;
    let query = 'SELECT * FROM campaigns WHERE organizer_id = ?';
    const params = [req.user.organizer_id];

    if (event_id) {
      query += ' AND event_id = ?';
      params.push(event_id);
    }

    query += ' ORDER BY created_at DESC';

    const [campaigns] = await pool.execute(query, params);
    res.json({ campaigns });
  } catch (error) {
    console.error('Erreur liste campagnes:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer une campagne
router.post('/', [
  body('event_id').isInt(),
  body('name').trim().notEmpty(),
  body('subject').trim().notEmpty(),
  body('content').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { event_id, name, type, subject, content, segment_id, scheduled_at } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO campaigns (
        event_id, organizer_id, name, type, subject, content,
        segment_id, scheduled_at, status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())`,
      [event_id, req.user.organizer_id, name, type || 'invitation', subject, content, segment_id || null, scheduled_at || null]
    );

    const [campaigns] = await pool.execute('SELECT * FROM campaigns WHERE id = ?', [result.insertId]);
    res.status(201).json({ campaign: campaigns[0] });
  } catch (error) {
    console.error('Erreur création campagne:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Récupérer une campagne
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const [campaigns] = await pool.execute(
      'SELECT * FROM campaigns WHERE id = ? AND organizer_id = ?',
      [id, req.user.organizer_id]
    );

    if (!campaigns.length) {
      return res.status(404).json({ error: 'Campagne non trouvée' });
    }

    res.json({ campaign: campaigns[0] });
  } catch (error) {
    console.error('Erreur récupération campagne:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour une campagne
router.put('/:id', [
  body('name').optional().trim().notEmpty(),
  body('subject').optional().trim().notEmpty(),
  body('content').optional().notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { name, type, subject, content, segment_id, scheduled_at, status } = req.body;

    // Vérifier que la campagne existe et appartient à l'organisateur
    const [campaigns] = await pool.execute(
      'SELECT * FROM campaigns WHERE id = ? AND organizer_id = ?',
      [id, req.user.organizer_id]
    );

    if (!campaigns.length) {
      return res.status(404).json({ error: 'Campagne non trouvée' });
    }

    // Construire la requête de mise à jour
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
    if (subject !== undefined) {
      updates.push('subject = ?');
      params.push(subject);
    }
    if (content !== undefined) {
      updates.push('content = ?');
      params.push(content);
    }
    if (segment_id !== undefined) {
      updates.push('segment_id = ?');
      params.push(segment_id || null);
    }
    if (scheduled_at !== undefined) {
      updates.push('scheduled_at = ?');
      params.push(scheduled_at || null);
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
      `UPDATE campaigns SET ${updates.join(', ')} WHERE id = ?`,
      params
    );

    const [updated] = await pool.execute(
      'SELECT * FROM campaigns WHERE id = ?',
      [id]
    );

    res.json({ campaign: updated[0] });
  } catch (error) {
    console.error('Erreur mise à jour campagne:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Envoyer une campagne
router.post('/:campaignId/send', async (req, res) => {
  try {
    const { campaignId } = req.params;

    // Récupérer la campagne
    const [campaigns] = await pool.execute(
      'SELECT * FROM campaigns WHERE id = ? AND organizer_id = ?',
      [campaignId, req.user.organizer_id]
    );

    if (!campaigns.length) {
      return res.status(404).json({ error: 'Campagne non trouvée' });
    }

    const campaign = campaigns[0];

    if (campaign.status === 'sent') {
      return res.status(400).json({ error: 'Cette campagne a déjà été envoyée' });
    }

    // Mettre à jour le statut
    await pool.execute(
      'UPDATE campaigns SET status = "sending", sent_at = NOW() WHERE id = ?',
      [campaignId]
    );

    // Récupérer les destinataires (via segment ou tous les contacts de l'événement)
    let contacts = [];
    if (campaign.segment_id) {
      // Utiliser le segment
      const [segments] = await pool.execute(
        'SELECT * FROM segments WHERE id = ? AND organizer_id = ?',
        [campaign.segment_id, req.user.organizer_id]
      );
      if (segments.length) {
        const conditions = segments[0].conditions ? JSON.parse(segments[0].conditions) : [];
        // Appliquer les conditions du segment (simplifié)
        const { query, params } = await applySegmentConditions(req.user.organizer_id, conditions);
        const [segmentContacts] = await pool.execute(query, params);
        contacts = segmentContacts;
      }
    } else {
      // Tous les contacts de l'événement
      const [eventContacts] = await pool.execute(
        `SELECT DISTINCT c.* FROM contacts c
         JOIN registrations r ON c.email = r.email
         WHERE r.event_id = ? AND c.organizer_id = ? AND c.status = 'active'`,
        [campaign.event_id, req.user.organizer_id]
      );
      contacts = eventContacts;
    }

    // Récupérer les infos de l'événement
    const [events] = await pool.execute(
      'SELECT * FROM events WHERE id = ?',
      [campaign.event_id]
    );
    const event = events.length > 0 ? events[0] : null;

    // Envoyer les emails de manière asynchrone
    const { sendEmail } = require('../utils/email');
    let sentCount = 0;
    let bouncedCount = 0;
    let errors = [];

    // Fonction pour personnaliser le contenu
    const personalizeContent = (content, contact, event) => {
      let personalized = content;
      personalized = personalized.replace(/\{\{first_name\}\}/g, contact.first_name || '');
      personalized = personalized.replace(/\{\{last_name\}\}/g, contact.last_name || '');
      personalized = personalized.replace(/\{\{email\}\}/g, contact.email || '');
      personalized = personalized.replace(/\{\{company\}\}/g, contact.company || '');
      if (event) {
        personalized = personalized.replace(/\{\{event_name\}\}/g, event.name || '');
        personalized = personalized.replace(/\{\{event_date\}\}/g, new Date(event.start_date).toLocaleDateString('fr-FR'));
      }
      return personalized;
    };

    // Envoyer les emails (limiter à 100 par batch pour éviter la surcharge)
    const batchSize = 100;
    for (let i = 0; i < contacts.length; i += batchSize) {
      const batch = contacts.slice(i, i + batchSize);
      
      await Promise.all(
        batch.map(async (contact) => {
          try {
            const personalizedContent = personalizeContent(campaign.content, contact, event);
            const personalizedSubject = personalizeContent(campaign.subject, contact, event);

            await sendEmail({
              to: contact.email,
              subject: personalizedSubject,
              html: personalizedContent
            });

            sentCount++;
          } catch (error) {
            console.error(`Erreur envoi email à ${contact.email}:`, error);
            bouncedCount++;
            errors.push({ email: contact.email, error: error.message });
          }
        })
      );
    }

    // Mettre à jour les statistiques
    await pool.execute(
      `UPDATE campaigns SET 
        status = 'sent',
        sent_count = ?,
        bounced_count = ?
       WHERE id = ?`,
      [sentCount, bouncedCount, campaignId]
    );

    res.json({
      message: 'Campagne envoyée',
      sent_count: sentCount,
      bounced_count: bouncedCount,
      errors: errors.length > 0 ? errors : undefined
    });
  } catch (error) {
    console.error('Erreur envoi campagne:', error);
    // Remettre le statut en draft en cas d'erreur
    await pool.execute(
      'UPDATE campaigns SET status = "draft" WHERE id = ?',
      [req.params.campaignId]
    );
    res.status(500).json({ error: 'Erreur lors de l\'envoi de la campagne' });
  }
});

// Fonction helper pour appliquer les conditions de segment (importée depuis segments.js)
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
      if (operator === 'equals') {
        query += ` AND JSON_EXTRACT(custom_fields, ?) = ?`;
        params.push(`$.${field}`, value);
      } else if (operator === 'contains') {
        query += ` AND JSON_EXTRACT(custom_fields, ?) LIKE ?`;
        params.push(`$.${field}`, `%${value}%`);
      }
    }

    if (index < conditions.length - 1 && condition.logic) {
      query += ` ${condition.logic}`;
    }
  });

  return { query, params };
}

module.exports = router;

