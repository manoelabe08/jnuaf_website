# JNUAF — Newsletter, première version

Cette extension se base sur le Laravel 12.69.3, les routes, modèles, middleware admin, composant mail-layout et commande RunJnuafCron transmis dans la conversation.
Elle ne remplace ni les contrôleurs partenaires, ni les emails existants, ni MessageController, ni le logo. Le composant mail-layout doit déjà accepter webUrl et un slot footer, comme dans la dernière version utilisée.
Les mails sont individuels : pas de liste collective To/CC/BCC. Chaque message contient un lien personnel de lecture web et de désinscription.
IMPORTANT : PHP et votre application complète ne sont pas disponibles dans l'environnement où ce paquet a été créé. Le code n'a donc pas été exécuté sous Laravel ici. Sauvegarde, installation sur copie, contrôles locaux et essai réel sont obligatoires avant une campagne réelle.

## 0. Sécurité et sauvegarde

Limiter les partages Drive des anciennes archives contenant .env, dossiers privés, Git et sauvegardes. Ne jamais placer cette archive, le SQL ou des copies de .env dans public.
Sauvegarder la base, routes/web.php, resources/views/layouts/admin.blade.php et app/Console/Commands/RunJnuafCron.php sur un espace privé.
Conserver APP_KEY : il chiffre les jetons des destinataires et les données existantes.
APP_DEBUG=false en production, HTTPS et cookies de session protégés doivent rester en place.

## 1. Contenu et fonctionnement

Nouvelles tables : newsletters (adresses), newsletter_campaigns (campagnes), newsletter_attachments (pièces privées), newsletter_deliveries (destinataires et résultats).
Nouveaux fichiers : modèles dédiés, service, mailable, contrôleurs, routes/newsletter.php, vues admin/mail/web, commande newsletter:send-batch.
Seul fichier existant fourni à remplacer : app/Console/Commands/RunJnuafCron.php, qui conserve partenaires puis galerie et ajoute les newsletters en dernier.
Aucune dépendance Composer, API d'email externe ou étape de build frontend supplémentaire.
Le stockage réutilise PARTNER_PRIVATE_ROOT existant, hors racine web, dans un sous-dossier newsletters créé avec des permissions restrictives.
L'envoi réutilise la configuration SMTP de mail.mailers.smtp (MAIL_*). Le transport newsletter est séparé du choix par défaut afin de ne jamais basculer silencieusement vers un fichier log comme si un email était réellement envoyé.

## 2. Copier les nouveaux fichiers

Fusionner app/, config/, database/ (si méthode migration), resources/, routes/ et tools/ avec le projet /htdocs/me.
Ne pas écraser routes/web.php ni layouts/admin.blade.php : les ajouts sont fournis dans integration/.
Ne remplacer RunJnuafCron.php qu'après installation des tables et présence de la nouvelle commande.
Conserver tous les fichiers existants qui ne font pas partie de ce paquet.

## 3. Installer les tables : choisir UNE méthode

### Méthode A — migrations Laravel (recommandée)

Le fichier database/migrations/2026_10_22_150000_create_newsletter_tables.php est fourni.
Après sauvegarde et depuis /htdocs/me :

    php artisan migrate:status
    php artisan migrate --path=database/migrations/2026_10_22_150000_create_newsletter_tables.php --force

La migration cible MySQL/MariaDB et crée les quatre tables. Ne pas utiliser migrate:fresh ou migrate:refresh.
Son retour arrière destructif est volontairement désactivé : les listes, campagnes et preuves ne doivent pas être supprimées par un rollback improvisé.
En cas d'échec partiel, arrêter et examiner les tables : ne pas lancer DROP TABLE au hasard.

### Méthode B — SQL via phpMyAdmin

Ouvrir la bonne base du site puis importer sql/INSTALLATION_NEWSLETTER.sql, fourni également comme fichier SQL séparé.
Vérifier avant import qu'aucune des quatre tables n'existe. Le script ne supprime ni ne remplace une table existante.
Ne pas exécuter ensuite la méthode A pour recréer les tables. Vous pouvez ne pas copier la migration si vous choisissez SQL.
Si elle est copiée ultérieurement, sa détection des quatre tables existantes contrôle les colonnes principales et évite la recréation ; ce contrôle n'est pas une vérification exhaustive des index et contraintes.
Le nom demandé est newsletters, en minuscules, y compris si l'interface de phpMyAdmin affiche une autre casse dans des noms voisins.

## 4. Ajouter les routes

À la fin de routes/web.php, EN DEHORS des groupes existants, ajouter UNE SEULE FOIS :

    require __DIR__.'/newsletter.php';

Conserver le require __DIR__.'/partner-portal.php' déjà en place.
Le nouveau fichier définit auth + admin pour toutes les fonctions administratives. Un coordinateur ne doit pas y accéder.
Les routes publiques servent seulement la version web de newsletter et la désinscription par jeton aléatoire ; elles ne donnent aucun accès à l'admin ou à l'espace partenaire.

## 5. Ajouter le lien au menu

Dans resources/views/layouts/admin.blade.php, à l'intérieur du bloc réservé au Bureau de Pilotage (@if ($u->isAdmin())), ajouter le lien de integration/02_menu_admin.blade.txt.
Il utilise route('admin.newsletter.index') et les classes du menu existant.
La page sera : https://jnuaf.org/me/public/admin/newsletter
La liste d'adresses sera : https://jnuaf.org/me/public/admin/newsletter/abonnes

## 6. Vérifier avant de lancer le cron

Depuis /htdocs/me :

    php artisan config:clear
    php artisan view:clear
    php artisan route:clear
    php artisan route:list --path=admin/newsletter -v
    php artisan route:list --path=newsletter
    php tools/check-newsletter.php

Le contrôle vérifie sans email envoyé : routes, rendu HTML/texte, échappement du contenu, présence des liens web/désinscription dans le composant commun, présence des tables et stockage privé. Il crée uniquement le sous-dossier privé si nécessaire, ne crée aucun destinataire et n'insère aucune donnée en base. Il charge les paramètres SMTP mais ne teste pas la connexion.
Le fichier tools/check-newsletter.php refuse tout accès HTTP (CLI seulement).
Si le composant ne prend pas en charge webUrl/footer, le contrôle s'arrête avec un message explicite. Corriger le composant AVANT tout envoi.
Vérifier aussi la syntaxe des fichiers PHP copiés, au minimum :

    php -l app/Services/NewsletterService.php
    php -l app/Mail/NewsletterMessage.php
    php -l app/Http/Controllers/Admin/NewsletterController.php
    php -l app/Http/Controllers/Admin/NewsletterSubscriberController.php
    php -l app/Http/Controllers/NewsletterPublicController.php
    php -l app/Console/Commands/SendNewsletterBatch.php
    php -l app/Console/Commands/RunJnuafCron.php
    php -l routes/newsletter.php

Le contrôle ne remplace pas l'essai d'envoi SMTP et la recette fonctionnelle.

## 7. Conserver UNE tâche cron

Remplacer app/Console/Commands/RunJnuafCron.php par la version fournie, après les contrôles précédents.
Votre unique tâche LWS reste inchangée :

    php /htdocs/me/artisan jnuaf:cron >> /htdocs/me/storage/logs/jnuaf-cron.log 2>&1

Actif, cinq champs de fréquence à * (chaque minute).
Ordre de traitement : partners:maintain, gallery:sync-folders, newsletter:send-batch.
Aucune activation implicite des rappels ou de la purge. Ne pas ajouter en parallèle schedule:run ni une seconde tâche newsletter.
Tester la découverte de la nouvelle commande :

    php artisan list

ATTENTION : php artisan newsletter:send-batch ou jnuaf:cron déclenche réellement les emails qui sont en attente. Ne pas utiliser comme contrôle anodin si des campagnes réelles ont été mises en file.

## 8. Préparer les destinataires

Dans Newsletter > Gérer les adresses :
- Ajouter manuellement une adresse et son nom facultatif.
- Par défaut elle reste en attente et ne reçoit pas de campagne.
- Pour l'activer, cocher l'activation et la confirmation d'autorisation, puis renseigner la preuve/le contexte (ex. demande écrite du 22/10).
- L'import users/registrations recense les adresses, ajoute leurs sources et déduplique les emails, mais n'abonne JAMAIS automatiquement une nouvelle personne et ne réactive pas un désinscrit.
- Les inscriptions anonymisées sont exclues.
- Les personnes déjà abonnées conservent leur statut ; une même adresse peut avoir plusieurs sources.
- Réactiver une personne désinscrite requiert une autorisation renouvelée, la preuve et la case de confirmation. Ne pas réactiver un désinscrit pour contourner son choix.

Cette v1 ne modifie pas le formulaire public d'inscription et ne fournit pas d'abonnement public/double opt-in. Les autorisations sont gérées dans l'admin. Une extension de collecte publique peut être ajoutée après choix du texte et du parcours.
Le module ne vérifie pas lui-même la validité juridique d'une autorisation saisie par l'administrateur. La simple existence d'un compte ou le consentement aux données d'inscription n'est pas une preuve d'abonnement newsletter.

## 9. Premier essai complet avec votre propre adresse

1. Ajouter une adresse que vous contrôlez comme abonné actif, source manuelle, avec une note « Test demandé par le titulaire ».
2. Créer un brouillon avec la seule source manuelle, contenu de test et petite pièce jointe factice.
3. Vérifier l'aperçu (c'est le contenu enregistré, pas les modifications non sauvegardées).
4. Cliquer « Envoyer un test ». Seul l'admin connecté reçoit cet email, sans lien personnel web/désinscription : le test n'est pas une inscription réelle.
5. Vérifier le design, logo centré et pièce jointe.
6. Sur le brouillon, vérifier le nombre d'abonnés actifs, cocher la confirmation puis « Envoyer la newsletter ».
7. Attendre le cron ou lancer newsletter:send-batch en sachant qu'il traite les campagnes en attente.
8. Vérifier le message réel : une seule adresse dans À, pas de CC/CCI collectif, lien de lecture web et de désinscription.
9. Ouvrir « Lire dans votre navigateur » et télécharger la pièce factice.
10. Ouvrir la désinscription : rien ne change sur simple GET. Confirmer via le bouton POST ; l'adresse devient désinscrite.
11. Faire une deuxième campagne de test : cette adresse ne doit plus être retenue. Ne pas la réactiver sans accord.
12. Tester l'admin avec un compte de coordination : 403 attendu.

## 10. Cadence, erreurs et priorités

Valeurs par défaut prudentes : au maximum 5 tentatives par fenêtre de 60 secondes et 100 par fenêtre de 3 600 secondes, globalement pour les campagnes (pas par campagne). Ce ne sont pas les limites contractuelles vérifiées de LWS.
Les emails partenaires, les tests et les autres usages du SMTP s'ajoutent au volume réel de l'hébergement. Vérifier l'offre LWS avant d'augmenter la cadence.
Paramètres optionnels dans .env : NEWSLETTER_PER_MINUTE et NEWSLETTER_PER_HOUR, puis artisan config:clear. Ne pas réinitialiser le cache en pleine campagne : les compteurs de limitation y résident.
Le temps d'un lot est limité à environ 25 s plus le dernier envoi SMTP en cours (timeout de transport 10 s, sans garantie sur tous les délais réseau). Le lot peut envoyer moins que le maximum. La tâche galerie/partenaires est lancée avant le lot newsletter dans jnuaf:cron.
Les pièces : 5 Mo par fichier, 10 Mo au total, 5 fichiers, formats limités PDF/PNG/JPEG/DOCX/XLSX/PPTX. Pas de fichier ZIP, HTML, SVG ni exécutable. Les limites PHP web peuvent différer du CLI. LWS peut aussi compter le volume MIME encodé, plus gros que le fichier brut.
Aucun antivirus n'est embarqué : n'utiliser que des documents fiables. Aucun droit d'accès à un document partenaire n'est accordé via une newsletter.
Un test SMTP ne garantit pas la livraison : spam, rebonds, SPF/DKIM/DMARC et quotas restent à contrôler.

États : en attente, en traitement, accepté par le transport, échec, incertain, ignoré.
Un traitement marqué en cours depuis plus de 15 min est marqué incertain. Aucune relance automatique d'un échec/incertain, pour éviter des doublons non maîtrisés.
La relance manuelle exige confirmation du risque : un email peut avoir été accepté par SMTP juste avant une coupure. Il n'existe pas ici de garantie absolue exactement-une-fois.
Arrêter une campagne supprime les envois non commencés mais ne rappelle pas les emails déjà transmis ; un envoi en cours peut encore aboutir.
Le contenu et les pièces sont figés à la mise en envoi. Pour envoyer un contenu corrigé, créer une nouvelle campagne. Un brouillon existant peut être édité et ses pièces retirées avant envoi.

## 11. Version web et confidentialité

L'URL web est personnelle, longue et aléatoire ; seul son hash est utilisé pour la recherche. Le jeton est aussi chiffré avec APP_KEY pour construire l'email au moment de l'envoi.
Version web et téléchargements expirent 90 jours après mise en file. La désinscription continue de fonctionner après l'expiration web.
Le lien peut être transféré : son détenteur peut consulter le contenu et les pièces, et demander une désinscription. Il ne s'agit pas d'une authentification forte. Ne PAS utiliser pour CV, conventions confidentielles ou documents réservés à un partenaire ; utiliser le portail documentaire avec code pour ces cas.
Les pages web n'affichent pas l'adresse du destinataire et ne sont pas indexables ; no-store/no-referrer sont appliqués. Pas de pixel de suivi ajouté et pas d'état « lu » déduit d'une ouverture.
Les liens peuvent apparaître dans les journaux d'accès de l'hébergeur. Protéger les journaux et limiter leur conservation.
Le corps est du texte avec retours à la ligne, échappé dans les vues HTML. Pas d'éditeur HTML riche dans cette première version.
La politique de confidentialité existante doit être mise à jour pour décrire les newsletters, les preuves, l'opposition et leur conservation. Pas de purge automatique imposée : définir une politique avant production.

## 12. Recette technique complémentaire

- Adresses doublonnées dans plusieurs sources : un seul destinataire par campagne.
- Désinscription après mise en file : refus au moment du traitement (un SMTP déjà en cours ne peut être annulé).
- Réimport d'un désinscrit : reste désinscrit.
- Validation répétée de mise en file : pas de deuxième lot pour la même campagne.
- Pièce d'une autre campagne dans l'URL : accès refusé.
- Identifiant de campagne seul sans jeton : aucune lecture web publique.
- Injection HTML dans le corps : affichée comme texte, jamais exécutée.
- Relance : seuls échecs/incertains, pas les destinataires marqués transmis.
- Fin de campagne : « Traitement terminé » peut inclure des erreurs ; lire les compteurs.
- Une campagne de plus de 5 000 abonnés est refusée pour éviter une préparation web trop lourde ; segmenter ou prévoir une architecture dédiée.

## Retour arrière

Avant tout envoi, revenir aux fichiers sauvegardés et retirer le require newsletter et le lien de menu suffit à désactiver l'interface ; arrêter aussi la commande newsletter dans jnuaf:cron. Garder les tables/archives de manière privée pour analyse.
Ne supprimer aucune table ni pièce jointe sans sauvegarde et politique de conservation. Un retour arrière du code peut rendre les liens de désinscription inaccessibles : ne pas le faire durablement après une campagne réelle sans prévoir un moyen de désinscription de remplacement.
