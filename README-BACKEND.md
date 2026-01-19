# Ceremony Backend API

## Prérequis
- PHP 8.2+
- Composer
- MySQL
- Redis (pour les Jobs/Queues)

## Installation

1. **Installer les dépendances**
   ```bash
   composer install
   ```

2. **Configuration Environment**
   Copier `.env.example` vers `.env` et configurer la base de données (PostgreSQL) et Redis.
   Assurez-vous de configurer les clés WhatsApp Meta API :
   ```env
   SERVICES_WHATSAPP_TOKEN="votre_token"
   SERVICES_WHATSAPP_PHONE_NUMBER_ID="votre_phone_id"
   ```

3. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

4. **Migrations et Seeders**
   ```bash
   php artisan migrate --seed
   ```
   Cela créera l'utilisateur de test et un template de base.

5. **Lancer le serveur**
   ```bash
   php artisan serve
   ```

6. **Lancer les Workers (pour l'envoi WhatsApp)**
   ```bash
   php artisan queue:work
   ```

## Architecture
- **Auth**: Laravel Sanctum (Bearer Token).
- **Database**: MySQL avec UUIDs pour les IDs principaux.
- **WhatsApp**: Service dédié `App\Services\WhatsAppService` et Job `App\Jobs\SendWhatsAppInvite`.
- **PDF**: Service `App\Services\PdfGeneratorService` (Stub pour Browsershot).

## Endpoints Principaux
- `POST /api/register` & `/api/login` : Authentification.
- `GET /api/templates` : Liste des designs disponibles.
- `POST /api/events` : Création d'événement (débite 1 crédit du pack).
- `POST /api/events/{id}/import` : Import invités (JSON ou CSV).
- `POST /api/events/{id}/send-invites` : Déclenche l'envoi WhatsApp asynchrone.
- `PATCH /api/rsvp/{token}` : Confirmation présence (Public).
- `POST /api/credits/purchase` : Achat de crédits (Stub).
