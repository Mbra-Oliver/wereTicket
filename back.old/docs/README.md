# Documentation Event Management SaaS

## Vue d'ensemble

Plateforme SaaS complète de gestion d'événements avec back office web, site d'inscription et application mobile de check-in.

## Architecture

### Backend
- **Framework**: Node.js + Express
- **Base de données**: MySQL
- **Authentification**: JWT
- **API**: REST

### Frontend
- **Framework**: React + Vite
- **Styling**: Tailwind CSS
- **State Management**: React Query

### Mobile
- **Framework**: React Native + Expo
- **Fonctionnalités**: Scan QR, Check-in, Liste invités

## Installation locale

### Backend

```bash
cd server
npm install
cp .env.example .env
# Éditer .env avec vos paramètres
node scripts/migrate.js
npm run dev
```

### Frontend

```bash
cd client
npm install
npm run dev
```

### Mobile

```bash
cd mobile
npm install
npm start
```

## Structure du projet

```
├── server/              # Backend API
│   ├── routes/         # Routes API
│   ├── middleware/     # Middleware (auth, etc.)
│   ├── config/         # Configuration
│   └── scripts/        # Scripts (migrations, etc.)
├── client/             # Frontend React
│   ├── src/
│   │   ├── pages/      # Pages
│   │   ├── components/ # Composants
│   │   └── contexts/   # Contextes React
└── mobile/             # App React Native
    ├── screens/        # Écrans
    └── services/       # Services API
```

## Fonctionnalités principales

### Back Office
- ✅ Gestion des événements
- ✅ Gestion des contacts
- ✅ Campagnes email
- ✅ Statistiques
- ✅ Check-in (configuration)

### Site d'inscription
- ✅ Formulaire d'inscription
- ✅ Génération QR code
- ✅ Paiements (Stripe)

### Application mobile
- ✅ Scan QR code
- ✅ Check-in manuel
- ✅ Liste des invités
- ✅ Mode hors ligne (à implémenter)

## Prochaines étapes

- [ ] Implémenter l'import CSV/Excel
- [ ] Compléter les campagnes email
- [ ] Mode hors ligne pour l'app mobile
- [ ] Impression de badges
- [ ] Module de networking (phase 2)

