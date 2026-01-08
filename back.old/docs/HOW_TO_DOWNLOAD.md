# 📥 Comment télécharger tous les fichiers du projet

## Option 1: Créer une archive ZIP (Recommandé) ⭐

### Sur Windows - Méthode simple:

1. **Ouvrez l'explorateur Windows**
2. **Naviguez vers le dossier du projet** (où se trouvent les dossiers `server`, `client`, `mobile`, `docs`)
3. **Sélectionnez tous les dossiers**:
   - Maintenez `Ctrl` et cliquez sur: `server`, `client`, `mobile`, `docs`
   - Ou sélectionnez tout avec `Ctrl + A`
4. **Clic droit** > **Envoyer vers** > **Dossier compressé (ZIP)**
5. Vous obtiendrez un fichier `event-management-saas.zip`
6. **Uploadez ce ZIP sur votre serveur** via File Manager dans cPanel

### Sur Windows - Via PowerShell:

```powershell
# Allez dans le dossier du projet
cd C:\Users\miche\event-management-saas  # ou votre chemin

# Créer l'archive
Compress-Archive -Path server,client,mobile,docs -DestinationPath event-management-saas.zip -Force
```

## Option 2: Utiliser le script PowerShell fourni

J'ai créé un script `create-archive.ps1` pour vous. Exécutez-le:

```powershell
.\create-archive.ps1
```

## Option 3: Uploader via File Manager (cPanel)

1. **Connectez-vous à cPanel**
2. Allez dans **File Manager**
3. Naviguez vers `public_html`
4. Créez un dossier `event-management`
5. **Uploadez chaque dossier**:
   - Cliquez sur "Upload" en haut
   - Sélectionnez le dossier `server/` (avec tous ses sous-dossiers)
   - Attendez la fin de l'upload
   - Répétez pour `client/`, `mobile/`, `docs/`

## Option 4: Uploader via FTP (FileZilla)

1. **Téléchargez FileZilla** (gratuit): https://filezilla-project.org/
2. **Installez et ouvrez FileZilla**
3. **Connectez-vous**:
   - Hôte: `back.yebticket.com` (ou l'IP de votre serveur)
   - Utilisateur: votre nom d'utilisateur cPanel
   - Mot de passe: votre mot de passe cPanel
   - Port: 21 (FTP) ou 22 (SFTP - plus sécurisé)
4. **Glissez-déposez** tous les dossiers du projet vers le serveur

## 📋 Liste des fichiers à uploader

### Dossiers principaux:
- ✅ `server/` (tout le contenu)
- ✅ `client/` (tout le contenu)
- ✅ `mobile/` (optionnel pour l'instant)
- ✅ `docs/` (documentation)

### Fichiers importants dans server/:
- `package.json`
- `index.js`
- `.env` (avec vos paramètres)
- `deploy.sh`
- Tous les sous-dossiers: `config/`, `routes/`, `middleware/`, etc.

## 🚀 Après l'upload

Une fois tous les fichiers uploadés sur le serveur:

1. **Connectez-vous en SSH** (via la page "Gérer les clés SSH" dans cPanel)
2. **Allez dans le dossier**:
   ```bash
   cd ~/public_html/event-management/server
   ```
3. **Exécutez le script de déploiement**:
   ```bash
   chmod +x deploy.sh
   ./deploy.sh
   ```

## 💡 Astuce rapide

**La méthode la plus simple:**
1. Créez un ZIP avec tous les dossiers (Option 1)
2. Uploadez le ZIP via File Manager
3. Décompressez-le sur le serveur (clic droit > Extract)
4. Suivez les instructions de déploiement

## ❓ Questions fréquentes

**Q: Dois-je uploader le dossier `node_modules`?**
R: Non! Le script `deploy.sh` installera automatiquement les dépendances.

**Q: Le fichier `.env` est-il déjà configuré?**
R: Oui, j'ai créé `server/.env` avec vos paramètres de base de données.

**Q: Combien de temps prend l'upload?**
R: Cela dépend de votre connexion, mais généralement 5-10 minutes pour tous les fichiers.

