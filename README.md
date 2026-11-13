# JNUAF 2027 — Plateforme d'inscription

Site public de la Journée du Numérique au Féminin et espace d'administration du Bureau de Pilotage. Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade sans étape de build front.

## Installation

```bash
composer install
cp .env.example .env && php artisan key:generate
# Créer la base MySQL (utf8mb4) :
#   CREATE DATABASE jnuaf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# Renseigner DB_* (hôte, utilisateur, mot de passe), JNUAF_ADMIN_PASSWORD et le SMTP (MAIL_*)
php artisan migrate --seed      # territoires, programme type, galerie, compte admin
php artisan storage:link
php artisan serve               # http://127.0.0.1:8000 — admin : /admin/connexion
php artisan test                # les tests utilisent une base SQLite en mémoire (phpunit.xml)
```

En local, `MAIL_MAILER=log` écrit les emails (avec le lien personnel) dans `storage/logs/laravel.log`.

## Fonctionnalités

**Public**
- Présentation de l'organisation #JNUAF.ORG avant l'événement : qui sommes-nous, historique 2021 → 2027, modèle d'intervention (Sensibiliser, Inspirer, Former, Connecter, Insérer), programmes, gouvernance et équipes opérationnelles. Ces contenus se modifient dans `config/jnuaf.php`.
- « Devenir partenaire » : bénéfices, formes de partenariat et formulaire de contact (accusé de réception par email, suivi dans l'admin).
- Inscription multi-profils (participante, bénévole, mentor, intervenante) rattachée à un territoire ; unicité de l'email par édition, champ piège anti-robots, limitation à 6 envois/min/IP.
- Email de confirmation avec badge PDF A6 joint (QR code + code lisible) et lien personnel.
- Lien personnel (`/mon-inscription/{jeton}`) : consulter, modifier, télécharger le badge, annuler, supprimer ses données. Seule l'empreinte SHA-256 du jeton est stockée.
- Candidature au comité avec CV et lettre en PDF (2 Mo max), stockés hors de l'espace public.
- Programme par territoire, intervenantes, galerie filtrable avec visionneuse, contenus gérés depuis l'admin.

**Administration** (`/admin`)
- **Paramètres** : date de l'événement, ouverture des inscriptions, durées de conservation, adresse d'alerte, mentions légales (sans toucher au code).
- **Territoires** : ajout, désactivation, capacité d'accueil. Au-delà de la capacité, les inscriptions passent en **liste d'attente** ; une place libérée est attribuée automatiquement à la première personne en attente, qui reçoit son badge.
- **Attestations de participation** (PDF) pour les personnes pointées : téléchargement dans l'espace personnel, envoi groupé depuis la page Pointage (`php artisan jnuaf:send-attestations`).
- **Alertes email** au Bureau à chaque candidature au comité et demande de partenariat ; exports CSV journalisés (inscriptions, candidatures, partenaires).
- **Mot de passe oublié** pour les comptes de l'équipe.
- **Conservation des données** appliquée chaque nuit (`php artisan jnuaf:purge-data [--dry-run]`) : inscriptions anonymisées, candidatures supprimées avec leurs pièces.
- Rôles : *admin* (Bureau de Pilotage, accès complet) et *coordination* (limitée à son territoire).
- Tableau de bord par territoire et par profil, liste filtrable, fiche, renvoi du badge, export CSV journalisé.
- Pointage : caméra (BarcodeDetector), douchette ou saisie du code. Le QR encode une URL avec un jeton opaque, sans donnée personnelle ; il est refusé s'il est annulé, déjà utilisé ou d'un autre territoire.
- Candidatures comité (statut, notes, pièces), programme, intervenantes, galerie, comptes.

## Production

**Hébergement LWS (test.jnuaf.org)** : suivre [deploy/DEPLOIEMENT-LWS.md](deploy/DEPLOIEMENT-LWS.md). Sans SSH : envoi automatique par GitHub Actions + FTP (`.github/workflows/deploy-test.yml`), installation via l'adresse protégée `POST /_deploy` (jeton `DEPLOY_TOKEN`). Alternative manuelle : paquet zip produit par `deploy\build.ps1`.

- Utiliser un utilisateur MySQL dédié avec mot de passe (pas `root` sans mot de passe comme sous XAMPP).
- Cron : `* * * * * php artisan schedule:run` → rappel avec badge envoyé chaque jour à 9 h à partir de J-3 (`JNUAF_EVENT_DATE`, `config/jnuaf.php`). Commande idempotente : `php artisan jnuaf:send-reminders [--force]`.
- Passer `QUEUE_CONNECTION=database` et lancer `php artisan queue:work` si l'envoi des emails doit être différé.
- `APP_ENV=production`, `APP_DEBUG=false` (HTTPS forcé).
- À compléter avant mise en ligne : mentions légales, responsable du traitement, contact données (`resources/views/home/about.blade.php`).
