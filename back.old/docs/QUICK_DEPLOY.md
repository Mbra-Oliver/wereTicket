# 🚀 Déploiement Rapide - Guide Express

## Étape 1: Uploader les fichiers
- Via File Manager ou FTP, uploadez TOUS les fichiers du projet sur votre serveur

## Étape 2: Se connecter en SSH
```bash
ssh -i /chemin/vers/votre/cle.pem votrenom@back.yebticket.com
```

## Étape 3: Exécuter le déploiement
```bash
cd ~/public_html/event-management/server
chmod +x deploy.sh
./deploy.sh
```

## Étape 4: Générer les secrets
```bash
node generate-secrets.js
```
Copiez les secrets générés dans `server/.env`

## Étape 5: Configurer Node.js dans cPanel
1. Allez dans **Node.js Selector**
2. Créez une application:
   - Root: `/home/votrenom/public_html/event-management/server`
   - URL: `back.yebticket.com/api`
   - Startup: `index.js`
3. Cliquez **Start App**

## Étape 6: Builder le frontend
```bash
cd ~/public_html/event-management/client
npm install
npm run build
cp -r dist/* ~/public_html/
```

## Étape 7: Tester
- API: https://back.yebticket.com/api/health
- Frontend: https://back.yebticket.com

## ✅ C'est fait !

Voir `docs/DEPLOY_CPANEL.md` pour les détails complets.

