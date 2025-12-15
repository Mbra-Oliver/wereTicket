# 🧹 Nettoyage Git & Docker - Guide Rapide

## Fichiers supprimés du repository Git

Les fichiers suivants ont été retirés du tracking Git et ajoutés au `.gitignore` :

### ✅ Dossiers et fichiers retirés :
- ✅ `/vendor/` - Dépendances PHP Composer (11000+ fichiers)
- ✅ `.DS_Store` - Fichiers système macOS
- ✅ `public/.DS_Store`
- ✅ Tous les fichiers `.DS_Store` du projet

### 📝 Prochaine étape

Pour finaliser le nettoyage, vous devez créer un commit :

```bash
# Ajouter tous les changements (suppression de vendor + nouveaux fichiers Docker)
git add .

# Créer le commit
git commit -m "feat: Configure Docker et nettoie le repository

- Ajoute configuration Docker complète (Dockerfile, docker-compose)
- Supprime vendor/ du tracking Git
- Améliore .gitignore pour Laravel
- Ajoute .dockerignore, Makefile, scripts de démarrage
- Supprime fichiers .DS_Store
- Ajoute documentation Docker (README-DOCKER.md)"

# Pousser vers le repository
git push
```

## ⚠️ Important

Après ce commit, le dossier `vendor/` ne sera plus poussé sur Git.

**Sur chaque environnement (local, staging, production), vous devrez faire :**

```bash
# Installer les dépendances
composer install
```

Avec Docker, c'est automatique :
```bash
docker-compose up -d
# ou
make install
```

## 🎯 Avantages

✅ Repository beaucoup plus léger (de plusieurs centaines de Mo à quelques Mo)
✅ Pas de conflits sur les dépendances lors des merge
✅ Clones Git beaucoup plus rapides
✅ Pas de fichiers système (.DS_Store, Thumbs.db) dans le repo
✅ Pas de fichiers ZIP ou archives dans Git

## 🚀 Utilisation Docker

Après avoir poussé les changements, pour démarrer le projet :

```bash
# Démarrage automatique
./docker-start.sh

# Ou manuel
docker-compose build
docker-compose --profile tools up -d
docker-compose exec app composer install
```

## 📊 Taille avant/après

**Avant le nettoyage :**
- ~300+ Mo (avec vendor/)
- 11000+ fichiers vendor trackés

**Après le nettoyage :**
- ~10-20 Mo (sans vendor/)
- Seulement le code source de l'application
