# 📋 Explication Complète des Nouveaux Changements

## 🎯 Pourquoi Scanner les QR Codes ?

Le système de scan QR code permet de **vérifier et valider les tickets** à l'entrée d'un événement. C'est essentiel pour :

1. **Lutter contre la fraude** - Empêcher l'utilisation de tickets dupliqués ou contrefaits
2. **Gérer l'accès** - Vérifier que seuls les tickets valides permettent l'entrée
3. **Suivre les présences** - Savoir exactement combien de personnes sont entrées
4. **Gérer la capacité** - Contrôler le nombre réel de participants
5. **Suivi en temps réel** - Voir qui a été scanné et qui ne l'a pas encore été

## 🔍 Deux Types de Scanners : Organisateur vs Admin

### 1. **Scanner Organisateur** (`OrganizerScannerController`)

**Rôle** : Permet aux **organisateurs d'événements** de scanner les tickets de leurs propres événements

**Permissions** :
- Peut scanner **UNIQUEMENT** les tickets des événements qu'il organise
- Vérification : `$check->organizer_id == $organizer_id`
- Si l'organisateur essaie de scanner un ticket d'un autre organisateur → "you do not have permission"

**Utilisation** : 
- Un organisateur organise un concert → Il peut scanner les tickets de son concert uniquement
- Il ne peut PAS scanner les tickets d'autres événements

### 2. **Scanner Admin** (`AdminScannerController`)

**Rôle** : Permet aux **administrateurs** de scanner les tickets de **TOUS** les événements

**Permissions** :
- Peut scanner les tickets de **n'importe quel** événement
- Accès global à tous les événements de la plateforme
- Pas de vérification d'organizer_id nécessaire

**Utilisation** :
- Un admin peut scanner les tickets de n'importe quel événement sur la plateforme
- Utile pour le support technique, les événements organisés par l'admin, etc.

## 🔐 Authentification et Sécurité

### Authentification avec Laravel Sanctum

Les deux systèmes utilisent **Laravel Sanctum** pour l'authentification par token :

```php
// Après connexion réussie
$token = $organizer->createToken($request->device_name ?? 'unknown-device')->plainTextToken;

// Chaque requête nécessite ce token dans le header
Authorization: Bearer {token}
```

**Avantages** :
- Sécurisé (token unique par device)
- Permet l'utilisation mobile (app Android/iOS)
- Gestion des sessions multi-devices

## 📱 Comment Fonctionne le Scan QR Code ?

### Format du QR Code

Le QR code contient un identifiant unique au format : `{booking_id}__{unique_id}`

**Exemple** : `BK123456789__ABC123XYZ`

- `BK123456789` = ID de la réservation (booking_id)
- `ABC123XYZ` = ID unique du ticket individuel (unique_id)

### Processus de Vérification

1. **Scan du QR Code** → L'app mobile lit le QR code et récupère `booking_id__unique_id`

2. **Séparation des IDs** :
```php
$ids = explode('__', $request->booking_id);
$booking_id = $ids[0];      // BK123456789
$unique_id = $ids[1];        // ABC123XYZ
```

3. **Vérification de la Réservation** :
```php
$check = Booking::where('booking_id', $booking_id)->first();
```

4. **Vérifications de Sécurité** :

   a. **Paiement** : Le ticket doit être payé
   ```php
   if ($check->paymentStatus == 'completed' || $check->paymentStatus == 'free')
   ```
   
   b. **Permission** (pour organisateur) :
   ```php
   if ($check->organizer_id == $organizer_id) // Seulement ses événements
   ```
   
   c. **Déjà scanné ?** : Évite les scans multiples
   ```php
   if (!in_array($unique_id, $scannedTicketArr)) {
       // Pas encore scanné, on peut valider
   }
   ```

5. **Enregistrement du Scan** :
```php
$scannedTicketArr = json_decode($check->scanned_tickets, true);
array_push($scannedTicketArr, $unique_id);
$check->scanned_tickets = json_encode($scannedTicketArr);
$check->save();
```

### Statuts Possibles

| Statut | Signification | Action |
|--------|---------------|--------|
| ✅ **Verified** | Ticket valide et scanné avec succès | Entrée autorisée |
| ❌ **Already Scanned** | Ticket déjà scanné précédemment | Refus d'entrée (fraude possible) |
| ❌ **Payment incomplete** | Le paiement n'est pas terminé | Refus d'entrée |
| ❌ **Payment Rejected** | Le paiement a été rejeté | Refus d'entrée |
| ❌ **Unverified** | Ticket invalide ou inexistant | Refus d'entrée |
| ❌ **you do not have permission** | Organisateur essaie de scanner un ticket qui ne lui appartient pas | Refus d'entrée |

## 📊 Nouvelles Fonctionnalités API

### 1. API REST Complète (`app/Http/Controllers/Api/`)

**12 nouveaux contrôleurs** pour l'application mobile :

#### CustomerController
- Inscription/Connexion clients
- Authentification Facebook/Google (Socialite)
- Dashboard client
- Gestion du profil
- Réinitialisation de mot de passe

#### EventController
- Liste des événements
- Détails d'un événement
- Catégories d'événements
- Application de coupon de réduction
- Vérification avant checkout
- Création de réservation
- Vérification de paiement
- **Nouveau** : Slot mapping pour les sièges

#### ShopController
- Liste des produits
- Détails d'un produit
- Ajout d'avis/commentaires

#### WishlistController
- Ajouter/supprimer des événements de la wishlist
- Liste des favoris

#### ProductOrderController
- Commandes produits du client
- Détails des commandes

#### SupportTicketController
- Création de tickets de support
- Liste des tickets
- Réponses aux tickets

#### FcmTokenController
- Enregistrement des tokens FCM (Firebase Cloud Messaging)
- Récupération des notifications push

#### HomeController
- Informations de base de l'application
- Configuration mobile

#### OrganizerController
- Liste des organisateurs
- Détails d'un organisateur
- Contact d'un organisateur

### 2. Scanner API (`app/Http/Controllers/ScannerApi/`)

#### OrganizerScannerController
- ✅ Connexion organisateur avec token
- ✅ Liste des événements de l'organisateur
- ✅ Scan de QR code (avec vérification de permission)
- ✅ Gestion manuelle du statut de scan
- ✅ Statistiques de scan (total scanné/non scanné)
- ✅ Liste détaillée des tickets (scannés/non scannés)

**Endpoints** :
```
POST /api/scanner/organizer/login/submit
GET  /api/scanner/organizer/events
POST /api/scanner/organizer/check-qrcode
POST /api/scanner/organizer/ticket/scanned-status-change
POST /api/scanner/organizer/logout
```

#### AdminScannerController
- ✅ Connexion admin avec token
- ✅ Liste de TOUS les événements
- ✅ Scan de QR code (accès global)
- ✅ Gestion manuelle du statut de scan
- ✅ Statistiques de scan globales
- ✅ Liste détaillée de tous les tickets

**Endpoints** :
```
POST /api/scanner/admin/login/submit
GET  /api/scanner/admin/events
POST /api/scanner/admin/check-qrcode
POST /api/scanner/admin/ticket/scanned-status-change
POST /api/scanner/admin/logout
```

#### BasicController
- Informations de base (couleurs, logo, favicon)
- Récupération des traductions (langues)

## 🗄️ Nouveaux Modèles Event

### 1. **EventCity** - Gestion des Villes
- Association ville ↔ événement
- Permet de filtrer les événements par ville

### 2. **EventCountry** - Gestion des Pays
- Association pays ↔ événement
- Filtrage international des événements

### 3. **EventState** - Gestion des États/Régions
- Association état/région ↔ événement
- Niveau intermédiaire entre pays et ville

### 4. **Slot** - Créneaux/Emplacements
- Système de créneaux pour événements physiques
- Permet la gestion d'emplacements multiples (salles, zones)

### 5. **SlotImage** - Images des Créneaux
- Images associées aux créneaux/emplacements

### 6. **SlotSeats** - Sièges dans les Créneaux
- **Système de réservation de sièges**
- Chaque siège peut être réservé individuellement
- Statut : disponible/réservé

**Cas d'usage** :
- Cinéma : Réservation de sièges spécifiques
- Théâtre : Choix de la place
- Concert : Zones avec sièges numérotés

### 7. **Mise à jour Booking**
- ✅ Nouveau champ : `fcm_token` (pour notifications push)
- ✅ Nouveau champ : `scanned_tickets` (JSON stockant les IDs des tickets scannés)
- ✅ Méthode `boot()` : Libère automatiquement les sièges lors de la suppression d'une réservation

## 🔧 Nouveaux Services et Helpers

### 1. **BookingServices** (`app/Services/BookingServices.php`)
- Logique métier pour la gestion des réservations
- Calculs de prix, taxes, commissions
- Gestion des variations de tickets

### 2. **GeoSearch** (`app/Http/Helpers/GeoSearch.php`)
- Recherche géographique d'événements
- Filtrage par proximité
- Calcul de distances

### 3. **ApiFormatTrait** (`app/Traits/ApiFormatTrait.php`)
- Formatage standardisé des réponses API
- Structure JSON cohérente
- Gestion des erreurs

## 📦 Nouveaux Jobs

### 1. **BookingInvoiceJob**
- Génération automatique de factures
- Envoi différé des tickets par email
- Pour les tickets non-instantanés

### 2. **PushNotificationJob**
- Envoi de notifications push via Firebase
- Notifications asynchrones
- Ne bloque pas l'interface utilisateur

## 🛣️ Nouvelles Routes

### Routes API (`routes/api.php`)
- ✅ Routes client (inscription, login, dashboard, bookings, wishlist)
- ✅ Routes événements (liste, détails, réservation)
- ✅ Routes shop (produits, commandes)
- ✅ Routes support (tickets)
- ✅ Routes scanner (login, scan QR code)

### Routes Scanner API (`routes/scanner_api.php`)
- ✅ Routes scanner organisateur
- ✅ Routes scanner admin
- ✅ Routes de base (langues, configuration)

## 📱 Fonctionnalités Mobile

### Notifications Push (FCM)
- Enregistrement des tokens d'appareils
- Envoi de notifications pour :
  - Confirmation de réservation
  - Changement de statut de paiement
  - Rappels d'événements
  - Nouvelles offres

### Gestion Multilingue
- Support de multiples langues
- Récupération dynamique des traductions
- Détection automatique de la langue

### Authentification Sociale
- Connexion Facebook
- Connexion Google
- Simplification de l'inscription

## 🔄 Différences Clés : Organisateur vs Admin Scanner

| Fonctionnalité | Organisateur | Admin |
|----------------|--------------|-------|
| **Accès événements** | Seulement ses événements | Tous les événements |
| **Scan QR code** | Tickets de ses événements uniquement | Tous les tickets |
| **Statistiques** | Par événement | Globale |
| **Gestion** | Limitée à ses créations | Accès complet |
| **Permission** | Vérifie `organizer_id` | Pas de vérification |

## 🎯 Cas d'Usage Concrets

### Scénario 1 : Scanner Organisateur
```
1. Jean organise un concert "Rock Festival 2024"
2. Il se connecte à l'app scanner avec son compte organisateur
3. Il voit seulement ses événements (Rock Festival 2024)
4. Le jour J, il scanne les QR codes à l'entrée
5. Seuls les tickets de son concert peuvent être scannés
6. S'il essaie de scanner un ticket d'un autre événement → Refus
```

### Scénario 2 : Scanner Admin
```
1. Marie est admin de la plateforme
2. Elle se connecte avec son compte admin
3. Elle voit TOUS les événements (Rock Festival, Jazz Night, etc.)
4. Elle peut scanner n'importe quel ticket de n'importe quel événement
5. Utile pour le support ou les événements organisés par la plateforme
```

### Scénario 3 : Système de Sièges
```
1. Un événement "Cinéma" est créé
2. L'organisateur définit 100 sièges (Rangée A: 1-20, Rangée B: 1-20, etc.)
3. Un client réserve 2 tickets et choisit les sièges A5 et A6
4. Ces sièges sont marqués comme "réservés"
5. Si le client annule, les sièges A5 et A6 sont automatiquement libérés
```

### Scénario 4 : Anti-Fraude QR Code
```
1. Un client achète un ticket → QR code généré: BK123__TICKET001
2. À l'entrée, le ticket est scanné → Statut: "Verified", TICKET001 ajouté à scanned_tickets
3. Quelqu'un essaie d'utiliser le même QR code → Statut: "Already Scanned"
4. L'accès est refusé → Prévention de la fraude
```

## 📈 Statistiques Disponibles

Pour chaque événement, le système peut maintenant fournir :

- ✅ **Total des tickets vendus** : Nombre total de billets
- ✅ **Tickets scannés** : Nombre de personnes déjà entrées
- ✅ **Tickets non scannés** : Nombre de personnes en attente
- ✅ **Liste détaillée** : Chaque ticket avec son statut (scanné/non scanné)
- ✅ **Informations client** : Nom, téléphone, statut de paiement

## 🔒 Sécurité et Validation

1. **Authentification** : Token Sanctum requis pour toutes les opérations
2. **Permissions** : Organisateur ne peut scanner que ses événements
3. **Validation de paiement** : Tickets non payés ne peuvent pas être scannés
4. **Prévention du double scan** : Un ticket ne peut être scanné qu'une fois
5. **Format QR code** : Validation du format `booking_id__unique_id`

## 🚀 Avantages de ces Changements

1. **Expérience Utilisateur** : Application mobile complète
2. **Gestion Événements** : Scanner QR code pratique et sécurisé
3. **Anti-Fraude** : Système robuste de vérification
4. **Suivi en Temps Réel** : Statistiques instantanées
5. **Flexibilité** : Support sièges réservés + tickets classiques
6. **Évolutivité** : Architecture API prête pour extensions futures

---

**En résumé** : Cette mise à jour transforme la plateforme en une solution complète de gestion d'événements avec support mobile, système de scan QR code sécurisé, gestion de sièges, et API REST complète pour une expérience utilisateur moderne.


