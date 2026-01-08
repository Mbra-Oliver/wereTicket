# Guide de déploiement sur cPanel

Ce guide vous aidera à déployer la plateforme de gestion d'événements sur votre serveur cPanel.

## Prérequis

- Compte cPanel avec accès SSH (recommandé) ou File Manager
- Base de données MySQL créée dans cPanel
- Node.js installé sur le serveur (version 14+)
- Domaine configuré

## Étapes de déploiement

### 1. Préparation de la base de données

1. Connectez-vous à votre cPanel
2. Allez dans "MySQL Databases"
3. Créez une nouvelle base de données (ex: `event_management`)
4. Créez un utilisateur et associez-le à la base de données
5. Notez les identifiants (host, user, password, database)

### 2. Configuration des variables d'environnement

1. Sur votre serveur, créez le fichier `.env` dans le dossier `server/`:

```bash
NODE_ENV=production
PORT=3000
CLIENT_URL=https://votre-domaine.com

DB_HOST=localhost
DB_USER=votre_utilisateur_db
DB_PASSWORD=votre_mot_de_passe_db
DB_NAME=event_management
DB_PORT=3306

JWT_SECRET=votre_secret_jwt_tres_long_et_aleatoire
JWT_EXPIRES_IN=7d

EMAIL_SERVICE=sendgrid
SENDGRID_API_KEY=votre_cle_sendgrid
EMAIL_FROM=noreply@votre-domaine.com

STRIPE_SECRET_KEY=votre_cle_stripe
STRIPE_WEBHOOK_SECRET=votre_webhook_secret

MAX_FILE_SIZE=5242880
UPLOAD_DIR=./uploads

BCRYPT_ROUNDS=12
SESSION_SECRET=votre_session_secret
```

### 3. Installation des dépendances

Via SSH:

```bash
cd /home/votre_compte/event-management
cd server
npm install --production
cd ../client
npm install
npm run build
```

### 4. Migration de la base de données

```bash
cd server
node scripts/migrate.js
```

### 5. Configuration du serveur Node.js

#### Option A: Utilisation de PM2 (recommandé)

```bash
npm install -g pm2
cd server
pm2 start index.js --name event-management-api
pm2 save
pm2 startup
```

#### Option B: Utilisation de cPanel Node.js Selector

1. Dans cPanel, allez dans "Node.js Selector"
2. Créez une nouvelle application Node.js
3. Définissez:
   - Node.js version: 18.x ou supérieur
   - Application root: `/home/votre_compte/event-management/server`
   - Application URL: `votre-domaine.com/api` (ou sous-domaine)
   - Application startup file: `index.js`
4. Cliquez sur "Create"

### 6. Configuration du frontend

1. Dans cPanel File Manager, copiez le contenu du dossier `client/dist` vers `public_html/` (ou votre dossier web)
2. Créez un fichier `.htaccess` dans `public_html/`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

### 7. Configuration du proxy API

Si votre API est sur un port différent, configurez un proxy dans cPanel:

1. Allez dans "Apache Handlers" ou créez un fichier `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/api/(.*)$
RewriteRule ^api/(.*)$ http://localhost:3000/api/$1 [P,L]
```

### 8. Configuration SSL

1. Dans cPanel, allez dans "SSL/TLS Status"
2. Installez un certificat SSL (Let's Encrypt recommandé)
3. Forcez HTTPS dans votre `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 9. Configuration des emails

1. Créez un compte SendGrid (ou autre service)
2. Configurez les DNS (SPF, DKIM) dans cPanel DNS Zone Editor
3. Ajoutez la clé API dans votre `.env`

### 10. Configuration Stripe

1. Créez un compte Stripe
2. Récupérez les clés API (test et production)
3. Configurez le webhook: `https://votre-domaine.com/api/payments/webhook`
4. Ajoutez les clés dans votre `.env`

## Vérification

1. Testez l'API: `https://votre-domaine.com/api/health`
2. Testez le frontend: `https://votre-domaine.com`
3. Testez l'inscription et la connexion

## Maintenance

### Logs

Les logs sont disponibles via:
- PM2: `pm2 logs event-management-api`
- cPanel: "Errors" dans File Manager

### Mises à jour

```bash
cd /home/votre_compte/event-management
git pull  # si vous utilisez Git
cd server
npm install --production
pm2 restart event-management-api
cd ../client
npm install
npm run build
# Copier dist/ vers public_html/
```

## Support

En cas de problème, vérifiez:
- Les logs d'erreur
- Les permissions des fichiers
- La configuration de la base de données
- Les variables d'environnement

