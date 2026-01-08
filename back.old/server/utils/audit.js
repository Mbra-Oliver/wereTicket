const pool = require('../config/database');

/**
 * Enregistre une action dans les logs d'audit
 */
async function logAction({
  userId,
  eventId,
  action,
  entityType,
  entityId,
  details,
  ipAddress
}) {
  try {
    await pool.execute(
      `INSERT INTO audit_logs (
        user_id, event_id, action, entity_type, entity_id, details, ip_address, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
      [
        userId || null,
        eventId || null,
        action,
        entityType || null,
        entityId || null,
        details ? JSON.stringify(details) : null,
        ipAddress || null
      ]
    );
  } catch (error) {
    console.error('Erreur enregistrement audit log:', error);
    // Ne pas faire échouer l'opération principale si l'audit échoue
  }
}

/**
 * Middleware pour logger automatiquement les actions
 */
function auditMiddleware(action, entityType) {
  return async (req, res, next) => {
    // Intercepter la réponse pour logger après l'action
    const originalJson = res.json.bind(res);
    res.json = function(data) {
      // Logger l'action si succès
      if (res.statusCode < 400) {
        const entityId = req.params.id || req.params.eventId || req.body.id || null;
        logAction({
          userId: req.user?.id,
          eventId: req.params.eventId || req.body.event_id || null,
          action,
          entityType,
          entityId,
          details: {
            method: req.method,
            path: req.path,
            body: sanitizeBody(req.body),
            query: req.query
          },
          ipAddress: req.ip || req.headers['x-forwarded-for'] || req.connection.remoteAddress
        });
      }
      return originalJson(data);
    };
    next();
  };
}

/**
 * Nettoie le body pour ne pas logger les données sensibles
 */
function sanitizeBody(body) {
  if (!body) return null;
  const sanitized = { ...body };
  
  // Supprimer les champs sensibles
  const sensitiveFields = ['password', 'token', 'secret', 'api_key', 'credit_card', 'cvv'];
  sensitiveFields.forEach(field => {
    if (sanitized[field]) {
      sanitized[field] = '***REDACTED***';
    }
  });

  return sanitized;
}

/**
 * Récupère les logs d'audit avec filtres
 */
async function getAuditLogs({
  userId,
  eventId,
  action,
  entityType,
  entityId,
  startDate,
  endDate,
  limit = 100,
  offset = 0
}) {
  let query = 'SELECT * FROM audit_logs WHERE 1=1';
  const params = [];

  if (userId) {
    query += ' AND user_id = ?';
    params.push(userId);
  }
  if (eventId) {
    query += ' AND event_id = ?';
    params.push(eventId);
  }
  if (action) {
    query += ' AND action = ?';
    params.push(action);
  }
  if (entityType) {
    query += ' AND entity_type = ?';
    params.push(entityType);
  }
  if (entityId) {
    query += ' AND entity_id = ?';
    params.push(entityId);
  }
  if (startDate) {
    query += ' AND created_at >= ?';
    params.push(startDate);
  }
  if (endDate) {
    query += ' AND created_at <= ?';
    params.push(endDate);
  }

  query += ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
  params.push(limit, offset);

  const [logs] = await pool.execute(query, params);

  // Compter le total
  const countQuery = query.replace(/SELECT \*/, 'SELECT COUNT(*) as total').replace(/LIMIT \? OFFSET \?/, '');
  const countParams = params.slice(0, -2);
  const [countResult] = await pool.execute(countQuery, countParams);

  return {
    logs: logs.map(log => ({
      ...log,
      details: log.details ? JSON.parse(log.details) : null
    })),
    total: countResult[0].total
  };
}

module.exports = {
  logAction,
  auditMiddleware,
  getAuditLogs
};
