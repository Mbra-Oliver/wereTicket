#!/bin/bash

# ========================
# Script de démarrage rapide pour WereTicket avec Docker
# ========================

set -e

# Couleurs
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}🎫 WereTicket - Docker Setup${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Vérifier si Docker est installé
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker n'est pas installé${NC}"
    echo -e "${YELLOW}📥 Installez Docker: https://docs.docker.com/get-docker/${NC}"
    exit 1
fi

# Vérifier si Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose n'est pas installé${NC}"
    echo -e "${YELLOW}📥 Installez Docker Compose: https://docs.docker.com/compose/install/${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker et Docker Compose sont installés${NC}"
echo ""

# Vérifier si le fichier .env existe
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  Le fichier .env n'existe pas${NC}"
    echo -e "${BLUE}📝 Création du fichier .env...${NC}"

    if [ -f .env.docker.example ]; then
        cp .env.docker.example .env
        echo -e "${GREEN}✅ Fichier .env créé depuis .env.docker.example${NC}"
    elif [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✅ Fichier .env créé depuis .env.example${NC}"
        echo -e "${YELLOW}⚠️  Pensez à mettre à jour les variables pour Docker${NC}"
    else
        echo -e "${RED}❌ Aucun fichier .env.example trouvé${NC}"
        exit 1
    fi
    echo ""
fi

# Demander quel environnement
echo -e "${BLUE}🔧 Configuration de l'environnement${NC}"
echo ""
echo "Choisissez le mode de démarrage:"
echo "  1) Mode développement (app + db + redis)"
echo "  2) Mode développement avec outils (+ PHPMyAdmin + Mailhog)"
echo "  3) Mode complet (+ workers)"
echo ""
read -p "Votre choix [2]: " choice
choice=${choice:-2}

# Construction des images
echo ""
echo -e "${BLUE}🏗️  Construction des images Docker...${NC}"
docker-compose build

# Démarrage selon le choix
echo ""
case $choice in
    1)
        echo -e "${BLUE}🚀 Démarrage en mode développement...${NC}"
        docker-compose up -d
        ;;
    2)
        echo -e "${BLUE}🚀 Démarrage avec les outils de développement...${NC}"
        docker-compose --profile tools up -d
        ;;
    3)
        echo -e "${BLUE}🚀 Démarrage en mode complet...${NC}"
        docker-compose --profile tools --profile workers up -d
        ;;
    *)
        echo -e "${RED}❌ Choix invalide${NC}"
        exit 1
        ;;
esac

# Attendre que les conteneurs soient prêts
echo ""
echo -e "${BLUE}⏳ Attente du démarrage des conteneurs...${NC}"
sleep 10

# Installer les dépendances Composer
echo ""
echo -e "${BLUE}📦 Installation des dépendances Composer...${NC}"
docker-compose exec -T app composer install --no-interaction

# Générer la clé d'application si nécessaire
echo ""
echo -e "${BLUE}🔑 Vérification de la clé d'application...${NC}"
if ! grep -q "APP_KEY=base64:" .env; then
    docker-compose exec -T app php artisan key:generate --ansi --force
fi

# Créer le lien symbolique storage
echo ""
echo -e "${BLUE}🔗 Création du lien symbolique storage...${NC}"
docker-compose exec -T app php artisan storage:link || true

# Fixer les permissions
echo ""
echo -e "${BLUE}🔧 Configuration des permissions...${NC}"
docker-compose exec -u root -T app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker-compose exec -u root -T app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Demander si on veut exécuter les migrations
echo ""
read -p "Voulez-vous exécuter les migrations ? (y/N): " migrate
if [[ $migrate =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}🗄️  Exécution des migrations...${NC}"
    docker-compose exec -T app php artisan migrate --force

    # Demander si on veut exécuter les seeders
    read -p "Voulez-vous exécuter les seeders ? (y/N): " seed
    if [[ $seed =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}🌱 Exécution des seeders...${NC}"
        docker-compose exec -T app php artisan db:seed --force
    fi
fi

# Afficher le résumé
echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}✨ Installation terminée !${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo -e "${GREEN}🌐 Application Web:${NC} http://localhost:8000"

if [ "$choice" -ge 2 ]; then
    echo -e "${GREEN}🗄️  PHPMyAdmin:${NC} http://localhost:8080"
    echo -e "${GREEN}📧 Mailhog:${NC} http://localhost:8025"
fi

echo ""
echo -e "${BLUE}📚 Commandes utiles:${NC}"
echo -e "  ${YELLOW}docker-compose logs -f${NC}          Voir les logs"
echo -e "  ${YELLOW}docker-compose exec app bash${NC}    Shell dans le conteneur"
echo -e "  ${YELLOW}docker-compose down${NC}              Arrêter les conteneurs"
echo -e "  ${YELLOW}make help${NC}                        Voir toutes les commandes Make"
echo ""
echo -e "${GREEN}================================${NC}"
