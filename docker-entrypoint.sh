#!/bin/bash
set -e

echo "================================"
echo "🚀 WereTicket - Initialisation"
echo "================================"

# ========================
# 1. Attendre que MySQL soit prêt
# ========================
echo "⏳ Attente de la disponibilité de MySQL..."
MAX_TRIES=30
TRIES=0

until php artisan db:monitor --database=mysql > /dev/null 2>&1 || [ $TRIES -eq $MAX_TRIES ]; do
  TRIES=$((TRIES+1))
  echo "   Tentative $TRIES/$MAX_TRIES..."
  sleep 2
done

if [ $TRIES -eq $MAX_TRIES ]; then
  echo "❌ Impossible de se connecter à MySQL après $MAX_TRIES tentatives"
  exit 1
fi

echo "✅ MySQL est prêt!"

# ========================
# 2. Attendre que Redis soit prêt (si configuré)
# ========================
if [ ! -z "$REDIS_HOST" ]; then
  echo "⏳ Attente de la disponibilité de Redis..."
  MAX_TRIES=30
  TRIES=0

  until php -r "new Redis();" > /dev/null 2>&1 || [ $TRIES -eq $MAX_TRIES ]; do
    TRIES=$((TRIES+1))
    echo "   Tentative $TRIES/$MAX_TRIES..."
    sleep 1
  done

  if [ $TRIES -eq $MAX_TRIES ]; then
    echo "⚠️  Impossible de se connecter à Redis (non critique)"
  else
    echo "✅ Redis est prêt!"
  fi
fi

# ========================
# 3. Vérifier et générer la clé APP_KEY
# ========================
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:random_generated_key" ]; then
  echo "🔑 Génération de la clé d'application..."
  php artisan key:generate --ansi --force
  echo "✅ Clé générée!"
else
  echo "✅ Clé d'application déjà configurée"
fi

# ========================
# 4. Fixer les permissions
# ========================
echo "🔧 Configuration des permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
echo "✅ Permissions configurées!"

# ========================
# 5. Nettoyer les caches (développement)
# ========================
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
  echo "🧹 Nettoyage des caches de développement..."
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan cache:clear
  echo "✅ Caches nettoyés!"
fi

# ========================
# 6. Créer le lien symbolique storage
# ========================
echo "🔗 Création du lien symbolique storage..."
if [ ! -L /var/www/public/storage ]; then
  php artisan storage:link
  echo "✅ Lien symbolique créé!"
else
  echo "✅ Lien symbolique déjà existant"
fi

# ========================
# 7. Exécuter les migrations (si AUTO_MIGRATE=true)
# ========================
if [ "$AUTO_MIGRATE" = "true" ]; then
  echo "🗄️  Exécution des migrations..."
  php artisan migrate --force
  echo "✅ Migrations exécutées!"
else
  echo "⏭️  Migrations ignorées (AUTO_MIGRATE n'est pas activé)"
  echo "   Pour activer : définir AUTO_MIGRATE=true dans .env"
fi

# ========================
# 8. Exécuter les seeders (si AUTO_SEED=true)
# ========================
if [ "$AUTO_SEED" = "true" ]; then
  echo "🌱 Exécution des seeders..."
  php artisan db:seed --force
  echo "✅ Seeders exécutés!"
fi

# ========================
# 9. Optimisations pour la production
# ========================
if [ "$APP_ENV" = "production" ]; then
  echo "⚡ Optimisations pour la production..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  echo "✅ Optimisations appliquées!"
fi

# ========================
# 10. Afficher les informations
# ========================
echo ""
echo "================================"
echo "✨ Application prête!"
echo "================================"
echo "📦 Environment: $APP_ENV"
echo "🐛 Debug: ${APP_DEBUG:-false}"
echo "🗄️  Database: $DB_DATABASE"
echo "🌐 Redis: ${REDIS_HOST:-non configuré}"
echo "================================"
echo ""

# ========================
# 11. Démarrer PHP-FPM
# ========================
echo "🚀 Démarrage de PHP-FPM..."
exec php-fpm
