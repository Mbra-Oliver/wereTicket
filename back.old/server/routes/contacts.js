const express = require('express');
const { body, validationResult } = require('express-validator');
const pool = require('../config/database');
const { authenticate, checkEventAccess } = require('../middleware/auth');

const router = express.Router();
router.use(authenticate);

// Lister les contacts
router.get('/', async (req, res) => {
  try {
    const { event_id, segment_id, search, status } = req.query;
    let query = 'SELECT * FROM contacts WHERE organizer_id = ?';
    const params = [req.user.organizer_id];

    if (search) {
      query += ' AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR company LIKE ?)';
      const searchTerm = `%${search}%`;
      params.push(searchTerm, searchTerm, searchTerm, searchTerm);
    }

    if (status) {
      query += ' AND status = ?';
      params.push(status);
    }

    query += ' ORDER BY created_at DESC LIMIT 100';

    const [contacts] = await pool.execute(query, params);
    res.json({ contacts });
  } catch (error) {
    console.error('Erreur liste contacts:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Créer un contact
router.post('/', [
  body('email').isEmail().normalizeEmail(),
  body('first_name').optional().trim(),
  body('last_name').optional().trim()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, first_name, last_name, company, job_title, phone, contact_type, custom_fields } = req.body;

    const [result] = await pool.execute(
      `INSERT INTO contacts (
        organizer_id, email, first_name, last_name, company, job_title,
        phone, contact_type, custom_fields, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ON DUPLICATE KEY UPDATE
        first_name = COALESCE(VALUES(first_name), first_name),
        last_name = COALESCE(VALUES(last_name), last_name),
        company = COALESCE(VALUES(company), company),
        updated_at = NOW()`,
      [req.user.organizer_id, email, first_name, last_name, company, job_title, phone, contact_type, JSON.stringify(custom_fields || {})]
    );

    const [contacts] = await pool.execute('SELECT * FROM contacts WHERE id = ?', [result.insertId]);
    res.status(201).json({ contact: contacts[0] });
  } catch (error) {
    console.error('Erreur création contact:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Récupérer un contact
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const [contacts] = await pool.execute(
      'SELECT * FROM contacts WHERE id = ? AND organizer_id = ?',
      [id, req.user.organizer_id]
    );

    if (!contacts.length) {
      return res.status(404).json({ error: 'Contact non trouvé' });
    }

    const contact = contacts[0];
    if (contact.custom_fields) {
      contact.custom_fields = JSON.parse(contact.custom_fields);
    }

    res.json({ contact });
  } catch (error) {
    console.error('Erreur récupération contact:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Mettre à jour un contact
router.put('/:id', [
  body('email').optional().isEmail().normalizeEmail(),
  body('first_name').optional().trim(),
  body('last_name').optional().trim()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { email, first_name, last_name, company, job_title, phone, contact_type, status, custom_fields } = req.body;

    // Vérifier que le contact existe et appartient à l'organisateur
    const [contacts] = await pool.execute(
      'SELECT * FROM contacts WHERE id = ? AND organizer_id = ?',
      [id, req.user.organizer_id]
    );

    if (!contacts.length) {
      return res.status(404).json({ error: 'Contact non trouvé' });
    }

    await pool.execute(
      `UPDATE contacts SET
        email = COALESCE(?, email),
        first_name = COALESCE(?, first_name),
        last_name = COALESCE(?, last_name),
        company = COALESCE(?, company),
        job_title = COALESCE(?, job_title),
        phone = COALESCE(?, phone),
        contact_type = COALESCE(?, contact_type),
        status = COALESCE(?, status),
        custom_fields = COALESCE(?, custom_fields),
        updated_at = NOW()
      WHERE id = ?`,
      [
        email || null,
        first_name || null,
        last_name || null,
        company || null,
        job_title || null,
        phone || null,
        contact_type || null,
        status || null,
        custom_fields ? JSON.stringify(custom_fields) : null,
        id
      ]
    );

    const [updated] = await pool.execute(
      'SELECT * FROM contacts WHERE id = ?',
      [id]
    );

    const contact = updated[0];
    if (contact.custom_fields) {
      contact.custom_fields = JSON.parse(contact.custom_fields);
    }

    res.json({ contact });
  } catch (error) {
    console.error('Erreur mise à jour contact:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Importer des contacts (CSV/Excel)
const multer = require('multer');
const csv = require('csv-parser');
const XLSX = require('xlsx');
const fs = require('fs');
const path = require('path');

const upload = multer({
  dest: path.join(__dirname, '../tmp/uploads'),
  limits: { fileSize: 10 * 1024 * 1024 }, // 10MB
  fileFilter: (req, file, cb) => {
    const allowedMimes = [
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    if (allowedMimes.includes(file.mimetype) || 
        file.originalname.endsWith('.csv') || 
        file.originalname.endsWith('.xlsx') || 
        file.originalname.endsWith('.xls')) {
      cb(null, true);
    } else {
      cb(new Error('Format de fichier non supporté. Utilisez CSV ou Excel.'));
    }
  }
});

router.post('/import', upload.single('file'), async (req, res) => {
  const connection = await pool.getConnection();
  await connection.beginTransaction();

  try {
    if (!req.file) {
      return res.status(400).json({ error: 'Aucun fichier fourni' });
    }

    const filePath = req.file.path;
    const fileExtension = path.extname(req.file.originalname).toLowerCase();
    let contacts = [];

    // Lire le fichier selon son format
    if (fileExtension === '.csv') {
      // Lire CSV
      const results = [];
      await new Promise((resolve, reject) => {
        fs.createReadStream(filePath)
          .pipe(csv())
          .on('data', (data) => results.push(data))
          .on('end', resolve)
          .on('error', reject);
      });
      contacts = results;
    } else if (fileExtension === '.xlsx' || fileExtension === '.xls') {
      // Lire Excel
      const workbook = XLSX.readFile(filePath);
      const sheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[sheetName];
      contacts = XLSX.utils.sheet_to_json(worksheet);
    } else {
      throw new Error('Format de fichier non supporté');
    }

    if (contacts.length === 0) {
      return res.status(400).json({ error: 'Le fichier est vide' });
    }

    // Normaliser les colonnes (email, first_name, last_name, company, job_title, phone, contact_type)
    const normalizedContacts = contacts.map(row => {
      const normalized = {};
      
      // Mapping flexible des colonnes
      const columnMap = {
        email: ['email', 'e-mail', 'mail', 'courriel'],
        first_name: ['first_name', 'firstname', 'prénom', 'prenom', 'first name'],
        last_name: ['last_name', 'lastname', 'nom', 'name', 'last name'],
        company: ['company', 'entreprise', 'société', 'organization', 'organisation'],
        job_title: ['job_title', 'job', 'poste', 'position', 'title', 'job title'],
        phone: ['phone', 'téléphone', 'telephone', 'tel', 'mobile'],
        contact_type: ['contact_type', 'type', 'category', 'catégorie']
      };

      Object.keys(columnMap).forEach(key => {
        const possibleNames = columnMap[key];
        for (const name of possibleNames) {
          const foundKey = Object.keys(row).find(
            k => k.toLowerCase().trim() === name.toLowerCase().trim()
          );
          if (foundKey && row[foundKey]) {
            normalized[key] = row[foundKey].toString().trim();
            break;
          }
        }
      });

      // Extraire les autres colonnes comme champs personnalisés
      const customFields = {};
      Object.keys(row).forEach(key => {
        const lowerKey = key.toLowerCase().trim();
        const isStandardField = Object.values(columnMap).some(names =>
          names.some(n => n.toLowerCase().trim() === lowerKey)
        );
        if (!isStandardField && row[key]) {
          customFields[lowerKey.replace(/\s+/g, '_')] = row[key].toString().trim();
        }
      });

      normalized.custom_fields = Object.keys(customFields).length > 0 ? customFields : null;
      return normalized;
    });

    // Importer les contacts
    let imported = 0;
    let updated = 0;
    let errors = [];

    for (const contact of normalizedContacts) {
      if (!contact.email) {
        errors.push({ row: contact, error: 'Email manquant' });
        continue;
      }

      try {
        // Vérifier si le contact existe déjà
        const [existing] = await connection.execute(
          'SELECT id FROM contacts WHERE organizer_id = ? AND email = ?',
          [req.user.organizer_id, contact.email]
        );

        if (existing.length > 0) {
          // Mettre à jour
          await connection.execute(
            `UPDATE contacts SET
              first_name = COALESCE(?, first_name),
              last_name = COALESCE(?, last_name),
              company = COALESCE(?, company),
              job_title = COALESCE(?, job_title),
              phone = COALESCE(?, phone),
              contact_type = COALESCE(?, contact_type),
              custom_fields = COALESCE(?, custom_fields),
              updated_at = NOW()
            WHERE id = ?`,
            [
              contact.first_name || null,
              contact.last_name || null,
              contact.company || null,
              contact.job_title || null,
              contact.phone || null,
              contact.contact_type || null,
              contact.custom_fields ? JSON.stringify(contact.custom_fields) : null,
              existing[0].id
            ]
          );
          updated++;
        } else {
          // Créer
          await connection.execute(
            `INSERT INTO contacts (
              organizer_id, email, first_name, last_name, company,
              job_title, phone, contact_type, custom_fields, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
            [
              req.user.organizer_id,
              contact.email,
              contact.first_name || null,
              contact.last_name || null,
              contact.company || null,
              contact.job_title || null,
              contact.phone || null,
              contact.contact_type || null,
              contact.custom_fields ? JSON.stringify(contact.custom_fields) : null
            ]
          );
          imported++;
        }
      } catch (error) {
        errors.push({ row: contact, error: error.message });
      }
    }

    await connection.commit();
    connection.release();

    // Nettoyer le fichier temporaire
    fs.unlinkSync(filePath);

    res.json({
      message: 'Import terminé',
      imported,
      updated,
      errors: errors.length > 0 ? errors : undefined,
      total_processed: normalizedContacts.length
    });
  } catch (error) {
    await connection.rollback();
    connection.release();
    
    // Nettoyer le fichier en cas d'erreur
    if (req.file && fs.existsSync(req.file.path)) {
      fs.unlinkSync(req.file.path);
    }

    console.error('Erreur import contacts:', error);
    res.status(500).json({ error: 'Erreur lors de l\'import' });
  }
});

// Exporter des contacts
router.get('/export', async (req, res) => {
  try {
    const { format = 'csv', segment_id, event_id } = req.query;
    let query = 'SELECT * FROM contacts WHERE organizer_id = ?';
    const params = [req.user.organizer_id];

    // Filtrer par segment si fourni
    if (segment_id) {
      const [segments] = await pool.execute(
        'SELECT * FROM segments WHERE id = ? AND organizer_id = ?',
        [segment_id, req.user.organizer_id]
      );
      if (segments.length) {
        const conditions = segments[0].conditions ? JSON.parse(segments[0].conditions) : [];
        // Appliquer les conditions du segment (simplifié ici)
        // Pour une implémentation complète, utiliser la fonction applySegmentConditions
      }
    }

    const [contacts] = await pool.execute(query, params);

    if (format === 'xlsx') {
      // Export Excel
      const worksheet = XLSX.utils.json_to_sheet(
        contacts.map(c => ({
          email: c.email,
          first_name: c.first_name,
          last_name: c.last_name,
          company: c.company,
          job_title: c.job_title,
          phone: c.phone,
          contact_type: c.contact_type,
          status: c.status,
          created_at: c.created_at,
          ...(c.custom_fields ? JSON.parse(c.custom_fields) : {})
        }))
      );
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Contacts');
      
      const buffer = XLSX.write(workbook, { type: 'buffer', bookType: 'xlsx' });
      res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      res.setHeader('Content-Disposition', `attachment; filename=contacts_${Date.now()}.xlsx`);
      res.send(buffer);
    } else {
      // Export CSV
      const csvHeader = 'email,first_name,last_name,company,job_title,phone,contact_type,status,created_at\n';
      const csvRows = contacts.map(c => {
        const customFields = c.custom_fields ? JSON.parse(c.custom_fields) : {};
        const customFieldsStr = Object.entries(customFields).map(([k, v]) => `${k}=${v}`).join(';');
        return `"${c.email}","${c.first_name || ''}","${c.last_name || ''}","${c.company || ''}","${c.job_title || ''}","${c.phone || ''}","${c.contact_type || ''}","${c.status}","${c.created_at}","${customFieldsStr}"`;
      }).join('\n');
      
      res.setHeader('Content-Type', 'text/csv');
      res.setHeader('Content-Disposition', `attachment; filename=contacts_${Date.now()}.csv`);
      res.send(csvHeader + csvRows);
    }
  } catch (error) {
    console.error('Erreur export contacts:', error);
    res.status(500).json({ error: 'Erreur lors de l\'export' });
  }
});

module.exports = router;

