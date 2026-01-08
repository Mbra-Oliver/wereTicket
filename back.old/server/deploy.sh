#!/bin/bash

# Script de déploiement automatique pour Event Management SaaS
# Usage: ./deploy.sh

set -e  # Arrêter en cas d'erreur

echo "🚀 Démarrage du déploiement..."

# Couleurs pour les messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "package.json" ]; then
    echo -e "${RED}❌ Erreur: Veuillez exécuter ce script depuis le dossier server${NC}"
    exit 1
fi

# 1. Vérifier Node.js
echo -e "${YELLOW}📦 Vérification de Node.js...${NC}"
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js n'est pas installé. Veuillez l'installer via cPanel Node.js Selector${NC}"
    exit 1
fi
NODE_VERSION=$(node -v)
echo -e "${GREEN}✅ Node.js $NODE_VERSION détecté${NC}"

# 2. Vérifier npm
if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ npm n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✅ npm $(npm -v) détecté${NC}"

# 3. Vérifier le fichier .env
echo -e "${YELLOW}🔧 Vérification de la configuration...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Fichier .env non trouvé. Veuillez le créer avec vos paramètres${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Fichier .env trouvé${NC}"

# 4. Installer les dépendances
echo -e "${YELLOW}📦 Installation des dépendances...${NC}"
npm install --production
echo -e "${GREEN}✅ Dépendances installées${NC}"

# 5. Créer le dossier uploads
echo -e "${YELLOW}📁 Création du dossier uploads...${NC}"
mkdir -p uploads
echo -e "${GREEN}✅ Dossier uploads créé${NC}"

# 6. Migration de la base de données
echo -e "${YELLOW}🗄️  Migration de la base de données...${NC}"
if node scripts/migrate.js; then
    echo -e "${GREEN}✅ Base de données migrée avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors de la migration. Vérifiez vos paramètres de base de données${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Déploiement backend terminé avec succès!${NC}"
echo ""
echo -e "${YELLOW}📋 Prochaines étapes:${NC}"
echo -e "1. Configurez Node.js Selector dans cPanel pour démarrer l'API"
echo -e "2. Testez l'API: https://back.yebticket.com/api/health"
echo ""

