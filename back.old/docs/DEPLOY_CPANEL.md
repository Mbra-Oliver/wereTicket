# Guide de déploiement cPanel - Étapes détaillées

## 📋 Informations de configuration

- **Domaine**: back.yebticket.com
- **Base de données**: rs2429210_event_management
- **Utilisateur DB**: rs2429210_eventplatfom_user
- **Port API**: 3000

## 🚀 Déploiement étape par étape

### Étape 1: Uploader les fichiers sur le serveur

**Méthode 1: Via File Manager (cPanel)**
1. Connectez-vous à cPanel
2. Allez dans **"File Manager"**
3. Naviguez vers `public_html` (ou votre dossier web)
4. Créez un dossier `event-management` (ou utilisez le dossier racine)
5. Uploadez **TOUS** les fichiers du projet (server, client, mobile, etc.)

**Méthode 2: Via FTP**
- Utilisez FileZilla ou un autre client FTP
- Connectez-vous avec vos identifiants cPanel
- Uploadez tous les fichiers

### Étape 2: Configurer l'accès SSH (recommandé)

1. Dans cPanel, allez dans **"Gérer les clés SSH"** (la page que vous avez vue)
2. Cliquez sur **"+ Générer une nouvelle clé"**
3. Configurez:
   - **Nom de la clé**: `deployment-key`
   - **Type de clé**: RSA (2048 bits minimum)
4. Cliquez sur **"Générer"**
5. **Autorisez la clé publique** (bouton "Autoriser")
6. **Téléchargez la clé privée** (bouton "Télécharger")
7. Sur votre ordinateur, connectez-vous:
   ```bash
   ssh -i /chemin/vers/votre/cle.pem votrenom@back.yebticket.com
   ```

### Étape 3: Exécuter le script de déploiement

Une fois connecté en SSH:

```bash
# Aller dans le dossier du projet
cd ~/public_html/event-management  # ou votre chemin exact

# Aller dans le dossier server
cd server

# Rendre le script exécutable
chmod +x deploy.sh

# Exécuter le script
./deploy.sh
```

Le script va automatiquement:
- ✅ Vérifier Node.js et npm
- ✅ Installer les dépendances backend
- ✅ Créer le dossier uploads
- ✅ Migrer la base de données (créer toutes les tables)

### Étape 4: Configurer Node.js dans cPanel

1. Dans cPanel, allez dans **"Node.js Selector"**
2. Cliquez sur **"Create Application"**
3. Configurez l'application:
   ```
   Node.js version: 18.x (ou la plus récente disponible)
   Application root: /home/votrenom/public_html/event-management/server
   Application URL: back.yebticket.com/api
   Application startup file: index.js
   Application mode: Production
   ```
4. Cliquez sur **"Create"**
5. Dans la liste des applications, trouvez votre app
6. Cliquez sur **"Run NPM Install"** (si nécessaire)
7. Cliquez sur **"Start App"**

### Étape 5: Déployer le frontend

1. Dans File Manager, allez dans `public_html`
2. **Option A**: Copiez tout le contenu de `client/dist` vers `public_html`
3. **Option B**: Créez un sous-dossier si vous préférez

**Via SSH:**
```bash
cd ~/public_html/event-management/client
npm install
npm run build
cp -r dist/* ~/public_html/
```

### Étape 6: Créer le fichier .htaccess

Dans `public_html`, créez/modifiez `.htaccess`:

```apache
# Configuration Apache pour Event Management SaaS

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # Redirection HTTPS
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

  # Proxy pour l'API (si Node.js est sur un port différent)
  RewriteCond %{REQUEST_URI} ^/api/(.*)$
  RewriteRule ^api/(.*)$ http://localhost:3000/api/$1 [P,L]

  # SPA Routing
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_URI} !^/api/
  RewriteRule . /index.html [L]
</IfModule>

# Compression GZIP
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Sécurité
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

### Étape 7: Générer un JWT_SECRET sécurisé

**Via SSH:**
```bash
node -e "console.log(require('crypto').randomBytes(64).toString('hex'))"
```

Copiez le résultat et mettez-le dans `server/.env`:
```
JWT_SECRET=le_resultat_copié_ici
```

Faites de même pour `SESSION_SECRET`.

### Étape 8: Vérifier le déploiement

1. **Testez l'API:**
   ```
   https://back.yebticket.com/api/health
   ```
   Devrait retourner: `{"status":"ok","timestamp":"..."}`

2. **Testez le frontend:**
   ```
   https://back.yebticket.com
   ```
   Devrait afficher la page de connexion

3. **Créez un compte:**
   - Allez sur `/login`
   - Cliquez sur "Créer un compte"
   - Remplissez le formulaire

## 🔧 Configuration optionnelle

### SendGrid (pour les emails)

1. Créez un compte sur [SendGrid](https://sendgrid.com)
2. Générez une clé API
3. Dans `server/.env`:
   ```
   SENDGRID_API_KEY=votre_cle_sendgrid
   EMAIL_FROM=noreply@yebticket.com
   ```

### Stripe (pour les paiements)

1. Créez un compte sur [Stripe](https://stripe.com)
2. Récupérez les clés API (test et production)
3. Dans `server/.env`:
   ```
   STRIPE_SECRET_KEY=votre_cle_stripe
   STRIPE_WEBHOOK_SECRET=votre_webhook_secret
   ```
4. Configurez le webhook dans Stripe:
   - URL: `https://back.yebticket.com/api/payments/webhook`
   - Événements: `payment_intent.succeeded`

## 🐛 Dépannage

### L'API ne démarre pas
- Vérifiez les logs dans **Node.js Selector** > **Logs**
- Vérifiez que le port 3000 est disponible
- Vérifiez les variables d'environnement dans `.env`
- Vérifiez les permissions: `chmod 755 server`

### Erreur de connexion à la base de données
- Vérifiez les identifiants dans `server/.env`
- Testez la connexion depuis cPanel > **MySQL Databases** > **phpMyAdmin**
- Vérifiez que l'utilisateur a les permissions sur la base de données

### Le frontend ne charge pas
- Vérifiez que les fichiers sont dans `public_html`
- Vérifiez le `.htaccess`
- Vérifiez les permissions: `chmod 755 public_html` et `chmod 644 public_html/*`

### Erreur CORS
- Vérifiez que `CLIENT_URL` dans `.env` correspond à votre domaine
- Vérifiez la configuration CORS dans `server/index.js`

### Erreur 500 ou page blanche
- Vérifiez les logs d'erreur Apache dans cPanel > **Errors**
- Vérifiez les logs Node.js dans **Node.js Selector**

## 📞 Commandes utiles

```bash
# Voir les logs Node.js
pm2 logs event-management-api

# Redémarrer l'application
pm2 restart event-management-api

# Vérifier le statut
pm2 status

# Voir les processus Node.js
ps aux | grep node
```

## ✅ Checklist de déploiement

- [ ] Fichiers uploadés sur le serveur
- [ ] Accès SSH configuré
- [ ] Script de déploiement exécuté
- [ ] Base de données migrée
- [ ] Node.js Selector configuré
- [ ] Application Node.js démarrée
- [ ] Frontend buildé et déployé
- [ ] .htaccess configuré
- [ ] JWT_SECRET généré et configuré
- [ ] API testée (endpoint /health)
- [ ] Frontend testé
- [ ] Compte organisateur créé
- [ ] SendGrid configuré (optionnel)
- [ ] Stripe configuré (optionnel)

