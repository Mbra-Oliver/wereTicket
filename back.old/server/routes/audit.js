const express = require('express');
const pool = require('../config/database');
const { authenticate, authorize } = require('../middleware/auth');
const { getAuditLogs } = require('../utils/audit');

const router = express.Router();
router.use(authenticate);

// Lister les logs d'audit
router.get('/', async (req, res) => {
  try {
    const {
      event_id,
      action,
      entity_type,
      entity_id,
      start_date,
      end_date,
      limit = 100,
      offset = 0
    } = req.query;

    // Filtrer par organisateur (sécurité)
    let query = `
      SELECT al.*, u.email as user_email, u.first_name, u.last_name, e.name as event_name
      FROM audit_logs al
      LEFT JOIN users u ON al.user_id = u.id
      LEFT JOIN events e ON al.event_id = e.id
      WHERE (e.organizer_id = ? OR al.user_id = ?)
    `;
    const params = [req.user.organizer_id, req.user.id];

    if (event_id) {
      query += ' AND al.event_id = ?';
      params.push(event_id);
    }
    if (action) {
      query += ' AND al.action = ?';
      params.push(action);
    }
    if (entity_type) {
      query += ' AND al.entity_type = ?';
      params.push(entity_type);
    }
    if (entity_id) {
      query += ' AND al.entity_id = ?';
      params.push(entity_id);
    }
    if (start_date) {
      query += ' AND al.created_at >= ?';
      params.push(start_date);
    }
    if (end_date) {
      query += ' AND al.created_at <= ?';
      params.push(end_date);
    }

    query += ' ORDER BY al.created_at DESC LIMIT ? OFFSET ?';
    params.push(parseInt(limit), parseInt(offset));

    const [logs] = await pool.execute(query, params);

    // Compter le total
    const countQuery = query.replace(/SELECT al\.\*.*?FROM/, 'SELECT COUNT(*) as total FROM').replace(/LIMIT \? OFFSET \?/, '');
    const countParams = params.slice(0, -2);
    const [countResult] = await pool.execute(countQuery, countParams);

    res.json({
      logs: logs.map(log => ({
        ...log,
        details: log.details ? JSON.parse(log.details) : null
      })),
      total: countResult[0].total,
      limit: parseInt(limit),
      offset: parseInt(offset)
    });
  } catch (error) {
    console.error('Erreur récupération logs audit:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

// Exporter les logs d'audit (pour conformité GDPR)
router.get('/export', authorize('organizer', 'super_admin'), async (req, res) => {
  try {
    const { format = 'json', start_date, end_date } = req.query;

    let query = `
      SELECT al.*, u.email as user_email, e.name as event_name
      FROM audit_logs al
      LEFT JOIN users u ON al.user_id = u.id
      LEFT JOIN events e ON al.event_id = e.id
      WHERE (e.organizer_id = ? OR al.user_id = ?)
    `;
    const params = [req.user.organizer_id, req.user.id];

    if (start_date) {
      query += ' AND al.created_at >= ?';
      params.push(start_date);
    }
    if (end_date) {
      query += ' AND al.created_at <= ?';
      params.push(end_date);
    }

    query += ' ORDER BY al.created_at DESC';

    const [logs] = await pool.execute(query, params);

    if (format === 'csv') {
      const csvHeader = 'id,user_email,event_name,action,entity_type,entity_id,ip_address,created_at\n';
      const csvRows = logs.map(log => {
        const details = log.details ? JSON.parse(log.details) : {};
        return `"${log.id}","${log.user_email || ''}","${log.event_name || ''}","${log.action}","${log.entity_type || ''}","${log.entity_id || ''}","${log.ip_address || ''}","${log.created_at}"`;
      }).join('\n');

      res.setHeader('Content-Type', 'text/csv');
      res.setHeader('Content-Disposition', `attachment; filename=audit_logs_${Date.now()}.csv`);
      res.send(csvHeader + csvRows);
    } else {
      res.json({
        logs: logs.map(log => ({
          ...log,
          details: log.details ? JSON.parse(log.details) : null
        }))
      });
    }
  } catch (error) {
    console.error('Erreur export logs audit:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;
