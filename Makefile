# ========================
# WereTicket - Makefile
# ========================
# Commandes utiles pour gérer Docker et Laravel

.PHONY: help build up down restart logs shell composer artisan migrate fresh test clean

# Couleurs pour l'affichage
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Variables
COMPOSE := docker-compose
EXEC := $(COMPOSE) exec app
EXEC_ROOT := $(COMPOSE) exec -u root app

## —— 🐳 Docker ——————————————————————————————————————————————————————————————
help: ## Afficher cette aide
	@echo "$(BLUE)WereTicket - Commandes disponibles$(NC)"
	@echo ""
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-30s$(NC) %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
	@echo ""

build: ## Construire les images Docker
	@echo "$(BLUE)🏗️  Construction des images...$(NC)"
	$(COMPOSE) build --no-cache

up: ## Démarrer les conteneurs
	@echo "$(BLUE)🚀 Démarrage des conteneurs...$(NC)"
	$(COMPOSE) up -d
	@echo "$(GREEN)✅ Application démarrée sur http://localhost:8000$(NC)"

up-tools: ## Démarrer avec les outils (PHPMyAdmin, Mailhog)
	@echo "$(BLUE)🚀 Démarrage avec les outils...$(NC)"
	$(COMPOSE) --profile tools up -d
	@echo "$(GREEN)✅ Application: http://localhost:8000$(NC)"
	@echo "$(GREEN)✅ PHPMyAdmin: http://localhost:8080$(NC)"
	@echo "$(GREEN)✅ Mailhog: http://localhost:8025$(NC)"

up-all: ## Démarrer tous les services (app + tools + workers)
	@echo "$(BLUE)🚀 Démarrage de tous les services...$(NC)"
	$(COMPOSE) --profile tools --profile workers up -d

down: ## Arrêter les conteneurs
	@echo "$(YELLOW)⏹️  Arrêt des conteneurs...$(NC)"
	$(COMPOSE) down

down-v: ## Arrêter et supprimer les volumes
	@echo "$(RED)🗑️  Arrêt et suppression des volumes...$(NC)"
	$(COMPOSE) down -v

restart: ## Redémarrer les conteneurs
	@echo "$(BLUE)🔄 Redémarrage...$(NC)"
	$(COMPOSE) restart

logs: ## Voir les logs
	$(COMPOSE) logs -f

logs-app: ## Voir les logs de l'application
	$(COMPOSE) logs -f app

logs-web: ## Voir les logs Nginx
	$(COMPOSE) logs -f web

logs-db: ## Voir les logs MySQL
	$(COMPOSE) logs -f db

ps: ## Voir le status des conteneurs
	$(COMPOSE) ps

## —— 🔧 Shell & Commandes ————————————————————————————————————————————————————
shell: ## Accéder au shell du conteneur app
	$(EXEC) bash

shell-root: ## Accéder au shell en tant que root
	$(EXEC_ROOT) bash

shell-db: ## Accéder au shell MySQL
	$(COMPOSE) exec db bash

mysql: ## Se connecter à MySQL
	$(COMPOSE) exec db mysql -u wereticket_user -psecret wereticket

## —— 📦 Composer ——————————————————————————————————————————————————————————————
composer: ## Exécuter composer (ex: make composer cmd="require vendor/package")
	$(EXEC) composer $(cmd)

composer-install: ## Installer les dépendances PHP
	@echo "$(BLUE)📦 Installation des dépendances Composer...$(NC)"
	$(EXEC) composer install

composer-update: ## Mettre à jour les dépendances
	@echo "$(BLUE)📦 Mise à jour des dépendances...$(NC)"
	$(EXEC) composer update

composer-dump: ## Regénérer l'autoloader optimisé
	$(EXEC) composer dump-autoload -o

## —— 🎨 Laravel Artisan ——————————————————————————————————————————————————————
artisan: ## Exécuter artisan (ex: make artisan cmd="route:list")
	$(EXEC) php artisan $(cmd)

migrate: ## Exécuter les migrations
	@echo "$(BLUE)🗄️  Exécution des migrations...$(NC)"
	$(EXEC) php artisan migrate

migrate-fresh: ## Réinitialiser la base avec migrations
	@echo "$(RED)⚠️  Réinitialisation de la base...$(NC)"
	$(EXEC) php artisan migrate:fresh

seed: ## Exécuter les seeders
	@echo "$(BLUE)🌱 Exécution des seeders...$(NC)"
	$(EXEC) php artisan db:seed

fresh: ## Réinitialiser la base avec migrations et seeders
	@echo "$(RED)⚠️  Réinitialisation complète...$(NC)"
	$(EXEC) php artisan migrate:fresh --seed

cache-clear: ## Vider tous les caches
	@echo "$(BLUE)🧹 Nettoyage des caches...$(NC)"
	$(EXEC) php artisan cache:clear
	$(EXEC) php artisan config:clear
	$(EXEC) php artisan route:clear
	$(EXEC) php artisan view:clear
	@echo "$(GREEN)✅ Caches nettoyés$(NC)"

cache-prod: ## Cacher pour la production
	@echo "$(BLUE)⚡ Création des caches de production...$(NC)"
	$(EXEC) php artisan config:cache
	$(EXEC) php artisan route:cache
	$(EXEC) php artisan view:cache
	$(EXEC) php artisan event:cache
	@echo "$(GREEN)✅ Caches créés$(NC)"

key-generate: ## Générer la clé d'application
	$(EXEC) php artisan key:generate

storage-link: ## Créer le lien symbolique storage
	$(EXEC) php artisan storage:link

## —— 🧪 Tests ——————————————————————————————————————————————————————————————————
test: ## Exécuter les tests
	$(EXEC) php artisan test

test-coverage: ## Exécuter les tests avec couverture
	$(EXEC) php artisan test --coverage

## —— 🛠️ Utilitaires ——————————————————————————————————————————————————————————
permissions: ## Fixer les permissions
	@echo "$(BLUE)🔧 Correction des permissions...$(NC)"
	$(EXEC_ROOT) chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	$(EXEC_ROOT) chmod -R 775 /var/www/storage /var/www/bootstrap/cache
	@echo "$(GREEN)✅ Permissions corrigées$(NC)"

npm-install: ## Installer les dépendances NPM
	$(EXEC) npm install

npm-dev: ## Compiler les assets (dev)
	$(EXEC) npm run dev

npm-build: ## Compiler les assets (production)
	$(EXEC) npm run build

npm-watch: ## Compiler les assets en mode watch
	$(EXEC) npm run watch

## —— 💾 Base de données ————————————————————————————————————————————————————————
db-backup: ## Sauvegarder la base de données
	@echo "$(BLUE)💾 Sauvegarde de la base...$(NC)"
	$(COMPOSE) exec db mysqldump -u root -prootpassword wereticket > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Sauvegarde créée$(NC)"

db-restore: ## Restaurer la base (ex: make db-restore file=backup.sql)
	@echo "$(BLUE)📥 Restauration de la base...$(NC)"
	$(COMPOSE) exec -T db mysql -u root -prootpassword wereticket < $(file)
	@echo "$(GREEN)✅ Base restaurée$(NC)"

## —— 🧼 Nettoyage ——————————————————————————————————————————————————————————————
clean: ## Nettoyer Docker (conteneurs, images, volumes)
	@echo "$(RED)🗑️  Nettoyage Docker...$(NC)"
	$(COMPOSE) down -v --remove-orphans
	docker system prune -af --volumes
	@echo "$(GREEN)✅ Nettoyage terminé$(NC)"

clean-logs: ## Nettoyer les logs Laravel
	@echo "$(BLUE)🧹 Nettoyage des logs...$(NC)"
	$(EXEC_ROOT) rm -rf /var/www/storage/logs/*.log
	@echo "$(GREEN)✅ Logs nettoyés$(NC)"

## —— 🚀 Installation ——————————————————————————————————————————————————————————
install: ## Installation complète du projet
	@echo "$(BLUE)🚀 Installation de WereTicket...$(NC)"
	@make build
	@make up-tools
	@sleep 10
	@make composer-install
	@make key-generate
	@make migrate
	@make storage-link
	@make permissions
	@echo ""
	@echo "$(GREEN)========================================$(NC)"
	@echo "$(GREEN)✨ Installation terminée !$(NC)"
	@echo "$(GREEN)========================================$(NC)"
	@echo "$(GREEN)🌐 Application: http://localhost:8000$(NC)"
	@echo "$(GREEN)🗄️  PHPMyAdmin: http://localhost:8080$(NC)"
	@echo "$(GREEN)📧 Mailhog: http://localhost:8025$(NC)"
	@echo "$(GREEN)========================================$(NC)"

## —— 🔄 Mise à jour ——————————————————————————————————————————————————————————
update: ## Mettre à jour le projet
	@echo "$(BLUE)🔄 Mise à jour...$(NC)"
	git pull
	@make down
	@make build
	@make up
	@make composer-install
	@make migrate
	@make cache-clear
	@make permissions
	@echo "$(GREEN)✅ Mise à jour terminée$(NC)"
