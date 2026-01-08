# Déploiement du sous-domaine back.yebticket.com (cPanel / Passenger)

Ce dossier est une version « propre » du backend Node/Express, prévue pour tourner via **Passenger**.

## 1) Où placer les fichiers

Sur le serveur, le sous-domaine **back.yebticket.com** pointe sur :

- `/home/rs2429210/public_html/back`

Copie le contenu `back/` de cette archive dans ce dossier.

Tu dois avoir :

- `/home/rs2429210/public_html/back/.htaccess`
- `/home/rs2429210/public_html/back/server/start.js`
- `/home/rs2429210/public_html/back/server/index.js`
- `/home/rs2429210/public_html/back/server/...` (routes, controllers, config, etc.)

## 2) .env (sans dotenv)

Le projet **n'utilise plus dotenv**.

- Copie `server/.env.example` en `server/.env`
- Mets tes vraies valeurs (DB_*, JWT_SECRET, etc.)

Le loader `server/config/loadEnv.js` lit `.env` **uniquement si le fichier existe**, et **n'écrase pas** les variables déjà définies dans `process.env` (utile si cPanel injecte ses propres variables).

## 3) Configuration Passenger (.htaccess)

Le fichier `.htaccess` doit contenir exactement :

```
PassengerAppRoot "/home/rs2429210/public_html/back/server"
PassengerBaseURI "/"
PassengerNodejs "/home/rs2429210/nodevenv/public_html/back/server/20/bin/node"
PassengerAppType node
PassengerStartupFile start.js
```

> Si tu changes de version Node dans cPanel, le chemin `PassengerNodejs` change.

## 4) Redémarrer Passenger

Après un changement de code/config :

```bash
mkdir -p /home/rs2429210/public_html/back/server/tmp
touch /home/rs2429210/public_html/back/server/tmp/restart.txt
```

## 5) Vérifications

- Santé :

```bash
curl -i "https://back.yebticket.com/health?nocache=$(date +%s)"
```

- Log de boot (créé par `server/start.js`) :

```bash
tail -n 200 /home/rs2429210/public_html/back/server/startup.log
```

Si `startup.log` reste vide, alors Passenger **ne lance pas** `start.js` → dans ce cas, re-vérifie :

- le **chemin** `PassengerAppRoot`
- le **nom** `PassengerStartupFile`
- que `.htaccess` est bien dans `/public_html/back/` (et pas ailleurs)
- que le sous-domaine pointe bien sur `/public_html/back`

## 6) Logs Apache du sous-domaine

Tu as un log compressé dédié au sous-domaine :

```bash
zgrep -iE "passenger|node|error|spawn|app" /home/rs2429210/logs/back.yebticket.com-Dec-2025.gz | tail -n 200
```

(adapte le mois si besoin)
