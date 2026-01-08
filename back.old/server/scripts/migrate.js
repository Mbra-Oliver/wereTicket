'use strict';

const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');
const { loadEnv } = require('../config/loadEnv');

loadEnv({ path: path.join(__dirname, '..', '.env') });

async function runMigrations() {
  let connection;

  try {
    connection = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_NAME,
      port: Number(process.env.DB_PORT) || 3306,
      multipleStatements: true
    });

    console.log('✅ Connexion à la base de données établie');

    const sqlFile = path.join(__dirname, 'schema.sql');
    const sql = await fs.readFile(sqlFile, 'utf8');

    await connection.query(sql);
    console.log('✅ Migrations exécutées avec succès');

    await connection.end();
    process.exit(0);
  } catch (error) {
    console.error('❌ Erreur lors des migrations:', error);
    if (connection) await connection.end();
    process.exit(1);
  }
}

runMigrations();
