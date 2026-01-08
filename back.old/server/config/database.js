'use strict';

const mysql = require('mysql2/promise');
const path = require('path');
const { loadEnv } = require('./loadEnv');

// Permet d'utiliser .env en local / en Passenger si besoin
loadEnv({ path: path.join(__dirname, '..', '.env') });

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  port: Number(process.env.DB_PORT) || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

module.exports = pool;
