// Script pour générer des secrets JWT et SESSION sécurisés
const crypto = require('crypto');

console.log('🔐 Génération de secrets sécurisés...\n');

const jwtSecret = crypto.randomBytes(64).toString('hex');
const sessionSecret = crypto.randomBytes(32).toString('hex');

console.log('JWT_SECRET=' + jwtSecret);
console.log('\nSESSION_SECRET=' + sessionSecret);
console.log('\n✅ Copiez ces valeurs dans votre fichier .env');

