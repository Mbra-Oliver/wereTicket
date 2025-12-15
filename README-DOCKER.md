# 🐳 Guide Docker - WereTicket

Ce guide vous explique comment utiliser Docker pour développer et déployer l'application WereTicket.

## 📋 Prérequis

- [Docker](https://docs.docker.com/get-docker/) (version 20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (version 2.0+)
- Git

## 🚀 Démarrage Rapide

### 1. Cloner le projet

```bash
git clone <votre-repo>
cd wereTicket
```

### 2. Copier le fichier d'environnement

```bash
cp .env.example .env
```

### 3. Configurer les variables d'environnement

Modifiez le fichier `.env` avec les paramètres Docker :

```env
# Application
APP_NAME=WereTicket
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=wereticket
DB_USERNAME=wereticket_user
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootpassword

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (avec Mailhog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# Docker Options
AUTO_MIGRATE=true
AUTO_SEED=false
APP_PORT=8000
PMA_PORT=8080
MAILHOG_PORT=8025
```

### 4. Construire et démarrer les conteneurs

```bash
# Construction des images
docker-compose build

# Démarrer les conteneurs
docker-compose up -d

# Voir les logs
docker-compose logs -f
```

### 5. Installer les dépendances

```bash
# Installer les dépendances PHP
docker-compose exec app composer install

# Installer les dépendances Node.js (si nécessaire)
docker-compose exec app npm install

# Générer la clé d'application (si pas déjà fait)
docker-compose exec app php artisan key:generate
```

### 6. Accéder à l'application

- **Application** : http://localhost:8000
- **PHPMyAdmin** : http://localhost:8080 (avec profil `tools`)
- **Mailhog** : http://localhost:8025 (avec profil `tools`)

## 📦 Services Disponibles

### Services Principaux (toujours actifs)

| Service | Description | Port |
|---------|-------------|------|
| `app` | Application PHP-FPM 8.1 | 9000 |
| `web` | Serveur Nginx | 8000 |
| `db` | MySQL 8.0 | 3306 |
| `redis` | Redis 7 (cache/sessions) | 6379 |

### Services Optionnels (profils)

| Service | Profil | Description | Port |
|---------|--------|-------------|------|
| `phpmyadmin` | `tools` | Interface web MySQL | 8080 |
| `mailhog` | `tools` | Capture d'emails | 8025 |
| `queue` | `workers` | Worker Laravel Queue | - |

## 🛠️ Commandes Docker Compose

### Gestion des conteneurs

```bash
# Démarrer tous les services
docker-compose up -d

# Démarrer avec les outils (PHPMyAdmin, Mailhog)
docker-compose --profile tools up -d

# Démarrer avec les workers
docker-compose --profile workers up -d

# Démarrer tout (services + outils + workers)
docker-compose --profile tools --profile workers up -d

# Arrêter les conteneurs
docker-compose down

# Arrêter et supprimer les volumes
docker-compose down -v

# Reconstruire les images
docker-compose build --no-cache

# Redémarrer un service
docker-compose restart app
```

### Gestion des logs

```bash
# Voir tous les logs
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f app
docker-compose logs -f web
docker-compose logs -f db
```

## 🔧 Commandes Laravel

### Artisan

```bash
# Exécuter une commande artisan
docker-compose exec app php artisan <commande>

# Exemples :
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:fresh --seed
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:list
docker-compose exec app php artisan make:controller NomController
docker-compose exec app php artisan make:model NomModel -m
```

### Composer

```bash
# Installer les dépendances
docker-compose exec app composer install

# Mettre à jour les dépendances
docker-compose exec app composer update

# Ajouter un package
docker-compose exec app composer require vendor/package

# Autoloader optimisé
docker-compose exec app composer dump-autoload -o
```

### NPM/Node

```bash
# Installer les dépendances
docker-compose exec app npm install

# Compiler les assets
docker-compose exec app npm run dev

# Compiler pour la production
docker-compose exec app npm run build

# Watch mode
docker-compose exec app npm run watch
```

## 🗄️ Gestion de la Base de Données

### Connexion MySQL

```bash
# Via docker exec
docker-compose exec db mysql -u wereticket_user -psecret wereticket

# Via MySQL Workbench ou autre client
Host: localhost
Port: 3306
User: wereticket_user
Password: secret
Database: wereticket
```

### Sauvegardes

```bash
# Exporter la base de données
docker-compose exec db mysqldump -u root -prootpassword wereticket > backup.sql

# Importer une base de données
docker-compose exec -T db mysql -u root -prootpassword wereticket < backup.sql
```

### PHPMyAdmin

```bash
# Démarrer avec PHPMyAdmin
docker-compose --profile tools up -d

# Accéder à : http://localhost:8080
```

## 🐛 Débogage

### Accéder au conteneur

```bash
# Shell dans le conteneur app
docker-compose exec app bash

# Shell dans le conteneur db
docker-compose exec db bash

# Shell en tant que root
docker-compose exec -u root app bash
```

### Vérifier les services

```bash
# Status des conteneurs
docker-compose ps

# Utilisation des ressources
docker stats

# Vérifier la connectivité MySQL
docker-compose exec app php artisan db:monitor

# Tester Redis
docker-compose exec redis redis-cli ping
```

### Problèmes courants

#### Permission denied sur storage/

```bash
docker-compose exec -u root app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec -u root app chmod -R 775 storage bootstrap/cache
```

#### Réinitialiser complètement

```bash
# Arrêter et supprimer tout
docker-compose down -v

# Supprimer les images
docker-compose down --rmi all

# Nettoyer Docker
docker system prune -a --volumes

# Reconstruire
docker-compose build --no-cache
docker-compose up -d
```

## 🚢 Déploiement en Production

### 1. Modifier le Dockerfile

Dans `docker-compose.yml`, changez :

```yaml
target: production  # Au lieu de 'development'
```

### 2. Variables d'environnement

```env
APP_ENV=production
APP_DEBUG=false
AUTO_MIGRATE=false
AUTO_SEED=false
```

### 3. Optimisations Laravel

```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan event:cache
docker-compose exec app composer install --optimize-autoloader --no-dev
```

## 📊 Volumes Docker

Les données persistantes sont stockées dans des volumes Docker :

| Volume | Description |
|--------|-------------|
| `db_data` | Données MySQL |
| `redis_data` | Données Redis |
| `vendor_data` | Dépendances Composer |
| `node_modules_data` | Dépendances NPM |
| `storage_cache` | Cache Laravel |
| `storage_sessions` | Sessions Laravel |
| `storage_views` | Vues compilées |
| `nginx_logs` | Logs Nginx |

### Gérer les volumes

```bash
# Lister les volumes
docker volume ls

# Supprimer les volumes inutilisés
docker volume prune

# Supprimer un volume spécifique
docker volume rm wereticket_db_data
```

## 🔒 Sécurité

### Bonnes pratiques

1. **Ne jamais commiter le fichier `.env`**
2. **Changer les mots de passe par défaut**
3. **Utiliser des secrets pour la production**
4. **Limiter l'exposition des ports**
5. **Mettre à jour régulièrement les images**

### Sécuriser Redis

```env
REDIS_PASSWORD=un_mot_de_passe_fort
```

Puis dans `docker-compose.yml` :

```yaml
redis:
  command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
```

## 📚 Ressources Utiles

- [Documentation Docker](https://docs.docker.com/)
- [Documentation Laravel](https://laravel.com/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [PHP-FPM Configuration](https://www.php.net/manual/fr/install.fpm.php)

## 🤝 Contribuer

Si vous trouvez des problèmes ou avez des suggestions pour améliorer la configuration Docker, n'hésitez pas à ouvrir une issue ou une pull request.

## 📝 License

Ce projet est sous licence MIT.
