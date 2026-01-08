/**
 * Script de test automatisé pour l'API
 * Usage: node scripts/test-api.js
 */

const http = require('http');

const BASE_URL = process.env.API_URL || 'http://localhost:3000';
let authToken = null;
let organizerId = null;
let eventId = null;
let contactId = null;
let segmentId = null;
let ticketTypeId = null;
let promoCodeId = null;
let checkpointId = null;
let registrationId = null;

// Couleurs pour la console
const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m'
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

// Fonction pour faire des requêtes HTTP
function request(method, path, data = null, token = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(path, BASE_URL);
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json'
      }
    };

    if (token) {
      options.headers['Authorization'] = `Bearer ${token}`;
    }

    const req = http.request(url, options, (res) => {
      let body = '';
      res.on('data', (chunk) => {
        body += chunk;
      });
      res.on('end', () => {
        try {
          const parsed = body ? JSON.parse(body) : {};
          resolve({
            status: res.statusCode,
            data: parsed,
            headers: res.headers
          });
        } catch (e) {
          resolve({
            status: res.statusCode,
            data: body,
            headers: res.headers
          });
        }
      });
    });

    req.on('error', (error) => {
      reject(error);
    });

    if (data) {
      req.write(JSON.stringify(data));
    }

    req.end();
  });
}

// Tests
async function testHealth() {
  log('\n=== Test Health Check ===', 'blue');
  try {
    const response = await request('GET', '/health');
    if (response.status === 200) {
      log('✅ Health check OK', 'green');
      return true;
    } else {
      log(`❌ Health check failed: ${response.status}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Health check error: ${error.message}`, 'red');
    return false;
  }
}

async function testRegister() {
  log('\n=== Test Inscription Organisateur ===', 'blue');
  try {
    const response = await request('POST', '/api/auth/register', {
      email: `test${Date.now()}@example.com`,
      password: 'Test123!',
      first_name: 'Test',
      last_name: 'User',
      company_name: 'Test Company'
    });

    if (response.status === 201 && response.data.token) {
      authToken = response.data.token;
      organizerId = response.data.user.organizer_id;
      log('✅ Inscription réussie', 'green');
      log(`   Token: ${authToken.substring(0, 20)}...`, 'yellow');
      return true;
    } else {
      log(`❌ Inscription échouée: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur inscription: ${error.message}`, 'red');
    return false;
  }
}

async function testLogin() {
  log('\n=== Test Connexion ===', 'blue');
  try {
    const response = await request('POST', '/api/auth/login', {
      email: 'organisateur@test.com',
      password: 'Test123!'
    });

    if (response.status === 200 && response.data.token) {
      authToken = response.data.token;
      organizerId = response.data.user.organizer_id;
      log('✅ Connexion réussie', 'green');
      return true;
    } else {
      log(`❌ Connexion échouée: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur connexion: ${error.message}`, 'red');
    return false;
  }
}

async function testGetProfile() {
  log('\n=== Test Profil Utilisateur ===', 'blue');
  try {
    const response = await request('GET', '/api/auth/me', null, authToken);
    if (response.status === 200) {
      log('✅ Profil récupéré', 'green');
      return true;
    } else {
      log(`❌ Échec récupération profil: ${response.status}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateEvent() {
  log('\n=== Test Création Événement ===', 'blue');
  try {
    const startDate = new Date();
    startDate.setMonth(startDate.getMonth() + 3);
    const endDate = new Date(startDate);
    endDate.setHours(endDate.getHours() + 8);

    const response = await request('POST', '/api/events', {
      name: 'Test Event ' + Date.now(),
      description: 'Événement de test',
      start_date: startDate.toISOString(),
      end_date: endDate.toISOString(),
      event_type: 'external',
      format: 'in-person',
      ticketing_type: 'paid',
      registration_type: 'single',
      timezone: 'Europe/Paris',
      currency: 'EUR'
    }, authToken);

    if (response.status === 201 && response.data.event) {
      eventId = response.data.event.id;
      log(`✅ Événement créé (ID: ${eventId})`, 'green');
      return true;
    } else {
      log(`❌ Échec création événement: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateContact() {
  log('\n=== Test Création Contact ===', 'blue');
  try {
    const response = await request('POST', '/api/contacts', {
      email: `contact${Date.now()}@example.com`,
      first_name: 'John',
      last_name: 'Doe',
      company: 'Test Corp',
      job_title: 'Developer',
      phone: '+33123456789',
      contact_type: 'VIP'
    }, authToken);

    if (response.status === 201 && response.data.contact) {
      contactId = response.data.contact.id;
      log(`✅ Contact créé (ID: ${contactId})`, 'green');
      return true;
    } else {
      log(`❌ Échec création contact: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateCustomField() {
  log('\n=== Test Création Champ Personnalisé ===', 'blue');
  try {
    const response = await request('POST', '/api/custom-fields', {
      name: 'Secteur',
      type: 'select',
      options: {
        choices: ['Tech', 'Finance', 'Santé']
      },
      is_required: false,
      is_visible_on_form: true
    }, authToken);

    if (response.status === 201) {
      log('✅ Champ personnalisé créé', 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateSegment() {
  log('\n=== Test Création Segment ===', 'blue');
  try {
    const response = await request('POST', '/api/segments', {
      name: 'Segment Test',
      conditions: [
        {
          field: 'contact_type',
          operator: 'equals',
          value: 'VIP'
        }
      ],
      is_favorite: false
    }, authToken);

    if (response.status === 201 && response.data.segment) {
      segmentId = response.data.segment.id;
      log(`✅ Segment créé (ID: ${segmentId})`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateTicketType() {
  log('\n=== Test Création Type de Ticket ===', 'blue');
  try {
    const response = await request('POST', '/api/tickets', {
      event_id: eventId,
      name: 'Ticket Standard',
      description: 'Accès complet',
      price: 99.99,
      currency: 'EUR',
      quantity: 100
    }, authToken);

    if (response.status === 201 && response.data.ticket) {
      ticketTypeId = response.data.ticket.id;
      log(`✅ Type de ticket créé (ID: ${ticketTypeId})`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreatePromoCode() {
  log('\n=== Test Création Code Promo ===', 'blue');
  try {
    const response = await request('POST', `/api/promo-codes/events/${eventId}`, {
      code: 'TEST' + Date.now(),
      discount_type: 'percentage',
      discount_value: 20,
      max_uses: 50
    }, authToken);

    if (response.status === 201 && response.data.promo_code) {
      promoCodeId = response.data.promo_code.id;
      log(`✅ Code promo créé (ID: ${promoCodeId})`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testCreateCheckpoint() {
  log('\n=== Test Création Checkpoint ===', 'blue');
  try {
    const response = await request('POST', `/api/checkpoints/events/${eventId}`, {
      name: 'Entrée Principale',
      location: 'Hall',
      access_rules: {
        allowed_ticket_types: [ticketTypeId]
      }
    }, authToken);

    if (response.status === 201 && response.data.checkpoint) {
      checkpointId = response.data.checkpoint.id;
      log(`✅ Checkpoint créé (ID: ${checkpointId})`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testPublicRegistration() {
  log('\n=== Test Inscription Publique ===', 'blue');
  try {
    const response = await request('POST', '/api/registrations', {
      event_id: eventId,
      email: `participant${Date.now()}@example.com`,
      first_name: 'Jane',
      last_name: 'Smith',
      company: 'Participant Corp',
      ticket_type_id: ticketTypeId
    });

    if (response.status === 201 && response.data.registration) {
      registrationId = response.data.registration.id;
      log(`✅ Inscription créée (ID: ${registrationId})`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${JSON.stringify(response.data)}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testStatistics() {
  log('\n=== Test Statistiques ===', 'blue');
  try {
    const response = await request('GET', '/api/statistics/dashboard', null, authToken);
    if (response.status === 200) {
      log('✅ Statistiques récupérées', 'green');
      log(`   Événements: ${response.data.events?.total_events || 0}`, 'yellow');
      return true;
    } else {
      log(`❌ Échec: ${response.status}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

async function testAuditLogs() {
  log('\n=== Test Logs d\'Audit ===', 'blue');
  try {
    const response = await request('GET', '/api/audit', null, authToken);
    if (response.status === 200) {
      log(`✅ Logs récupérés (${response.data.total || 0} entrées)`, 'green');
      return true;
    } else {
      log(`❌ Échec: ${response.status}`, 'red');
      return false;
    }
  } catch (error) {
    log(`❌ Erreur: ${error.message}`, 'red');
    return false;
  }
}

// Exécuter tous les tests
async function runAllTests() {
  log('\n🧪 DÉMARRAGE DES TESTS API\n', 'blue');
  
  const results = {
    passed: 0,
    failed: 0,
    total: 0
  };

  const tests = [
    { name: 'Health Check', fn: testHealth, requiresAuth: false },
    { name: 'Inscription', fn: testRegister, requiresAuth: false },
    { name: 'Connexion', fn: testLogin, requiresAuth: false },
    { name: 'Profil', fn: testGetProfile, requiresAuth: true },
    { name: 'Création Événement', fn: testCreateEvent, requiresAuth: true },
    { name: 'Création Contact', fn: testCreateContact, requiresAuth: true },
    { name: 'Champ Personnalisé', fn: testCreateCustomField, requiresAuth: true },
    { name: 'Segment', fn: testCreateSegment, requiresAuth: true },
    { name: 'Type de Ticket', fn: testCreateTicketType, requiresAuth: true },
    { name: 'Code Promo', fn: testCreatePromoCode, requiresAuth: true },
    { name: 'Checkpoint', fn: testCreateCheckpoint, requiresAuth: true },
    { name: 'Inscription Publique', fn: testPublicRegistration, requiresAuth: false },
    { name: 'Statistiques', fn: testStatistics, requiresAuth: true },
    { name: 'Logs Audit', fn: testAuditLogs, requiresAuth: true }
  ];

  for (const test of tests) {
    if (test.requiresAuth && !authToken) {
      log(`⏭️  ${test.name} - Skipped (nécessite authentification)`, 'yellow');
      continue;
    }

    results.total++;
    try {
      const success = await test.fn();
      if (success) {
        results.passed++;
      } else {
        results.failed++;
      }
    } catch (error) {
      log(`❌ ${test.name} - Erreur: ${error.message}`, 'red');
      results.failed++;
    }

    // Petite pause entre les tests
    await new Promise(resolve => setTimeout(resolve, 500));
  }

  // Résumé
  log('\n' + '='.repeat(50), 'blue');
  log('📊 RÉSUMÉ DES TESTS', 'blue');
  log('='.repeat(50), 'blue');
  log(`Total: ${results.total}`, 'blue');
  log(`✅ Réussis: ${results.passed}`, 'green');
  log(`❌ Échoués: ${results.failed}`, results.failed > 0 ? 'red' : 'green');
  log(`📈 Taux de réussite: ${((results.passed / results.total) * 100).toFixed(1)}%`, 'blue');
  log('='.repeat(50) + '\n', 'blue');

  process.exit(results.failed > 0 ? 1 : 0);
}

// Démarrer les tests
runAllTests().catch(error => {
  log(`\n❌ Erreur fatale: ${error.message}`, 'red');
  process.exit(1);
});
