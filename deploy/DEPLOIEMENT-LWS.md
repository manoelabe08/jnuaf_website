# Mettre la plateforme en ligne sur https://test.jnuaf.org (LWS cPanel, sans SSH)

Aucun accès SSH n'est nécessaire. Le principe :

1. **Les fichiers** arrivent sur l'hébergement par **FTP** : automatiquement depuis GitHub
   (recommandé), ou à la main avec un fichier zip.
2. **L'installation** (clé de chiffrement, tables, données de départ, caches) se lance en
   appelant l'adresse protégée `https://test.jnuaf.org/_deploy` avec un jeton secret.
3. **Les rappels automatiques** passent par une **tâche Cron** de cPanel.

Les étapes A à F se font une seule fois, dans le cPanel LWS (https://panel.lws.fr → votre
hébergement → cPanel). Les intitulés de menus peuvent varier un peu : utilisez la barre de
recherche de cPanel avec les mots indiqués.

---

## A. Version de PHP

cPanel → **Sélectionner une version de PHP** (ou **MultiPHP Manager**) :

- **PHP 8.3** (8.2 minimum) pour `test.jnuaf.org` ;
- extensions cochées : `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `openssl`, `zip`, `intl`, `dom`.

## B. Sous-domaine

cPanel → **Domaines** (ou **Sous-domaines**) → **Créer un domaine** :

| Champ | Valeur |
|---|---|
| Domaine | `test.jnuaf.org` |
| Racine du document | `test.jnuaf.org/public` ← **doit se terminer par `/public`** |

Seul le dossier `public` doit être visible depuis le web. Avec une racine `test.jnuaf.org`,
le fichier `.env` (mots de passe) serait exposé.

Si le DNS de `jnuaf.org` n'est pas géré chez LWS, ajoutez chez votre gestionnaire DNS un
enregistrement **A** `test` vers l'adresse IP de l'hébergement (cPanel, colonne de droite).

Puis cPanel → **SSL/TLS Status** (ou **Let's Encrypt**) : émettez le certificat pour
`test.jnuaf.org`, et dans **Domaines**, activez **Forcer la redirection HTTPS**.

## C. Base de données

cPanel → **Bases de données MySQL** :

1. créer une base `jnuaftest` (cPanel la préfixe : `identifiant_jnuaftest`) ;
2. créer un utilisateur `jnuaftest` avec un mot de passe généré ;
3. **ajouter l'utilisateur à la base** avec **TOUS LES PRIVILÈGES**.

## D. Email d'envoi

cPanel → **Comptes de messagerie** → créer `no-reply@jnuaf.org`. Sur ce compte,
**Connecter les appareils** : notez le serveur SMTP et le port SSL (souvent `mail.jnuaf.org`, `465`).

## E. Compte FTP dédié

cPanel → **Comptes FTP** → créer un compte, par exemple `deploy@jnuaf.org`, avec :

- **Répertoire** : `test.jnuaf.org` (le compte ne verra que ce dossier) ;
- un mot de passe généré.

Notez l'**hôte FTP** indiqué par cPanel (bouton **Configurer le client FTP** : souvent
`ftp.jnuaf.org`).

## F. Fichier de configuration `.env`

1. Générez le **jeton secret** de l'adresse d'installation. Sur votre PC, dans PowerShell :

   ```powershell
   -join ((48..57)+(65..90)+(97..122) | Get-Random -Count 48 | ForEach-Object {[char]$_})
   ```

   Gardez ce jeton : il sert ici et dans GitHub.

2. cPanel → **Gestionnaire de fichiers** → dossier `test.jnuaf.org` → **+ Fichier** → nom `.env`.
   (Pour voir les fichiers commençant par un point : **Paramètres** → **Afficher les fichiers cachés**.)

3. **Modifier** ce fichier, y coller le contenu de [`deploy/env.test.jnuaf.org`](env.test.jnuaf.org)
   et remplacer chaque `À_REMPLIR` :
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` → étape C (noms avec le préfixe) ;
   - `MAIL_HOST`, `MAIL_PORT`, `MAIL_PASSWORD` → étape D ;
   - `DEPLOY_TOKEN` → le jeton généré ci-dessus ;
   - `JNUAF_ADMIN_EMAIL`, `JNUAF_ADMIN_PASSWORD` → compte du Bureau de Pilotage (première connexion).

   Laissez `APP_KEY=` vide : elle est générée automatiquement à l'installation.

---

## G1. Mise en ligne automatique avec GitHub (recommandé)

### Une seule fois

1. Sur https://github.com → **New repository** : nom `jnuaf-plateforme`, **Private**,
   sans README ni .gitignore (le projet les a déjà).
2. Dans le dépôt → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**,
   créez les 4 secrets :

   | Nom | Valeur |
   |---|---|
   | `FTP_SERVER` | l'hôte FTP (étape E), ex. `ftp.jnuaf.org` |
   | `FTP_USERNAME` | le compte FTP (étape E), ex. `deploy@jnuaf.org` |
   | `FTP_PASSWORD` | son mot de passe |
   | `DEPLOY_TOKEN` | le jeton secret (étape F) |

3. Sur votre PC, dans le dossier `jnuaf` (Git y est déjà initialisé) :

   ```bash
   git add .
   git commit -m "Plateforme JNUAF"
   git remote add origin https://github.com/VOTRE-COMPTE/jnuaf-plateforme.git
   git push -u origin main
   ```

### Ensuite, à chaque modification

```bash
git add .
git commit -m "Description de la modification"
git push
```

GitHub lance alors l'onglet **Actions** du dépôt : tests → préparation → envoi FTP → installation.
Une coche verte signifie que le site est à jour. Une croix rouge : cliquez dessus pour lire l'étape
en échec. Si ce sont les tests, rien n'a été envoyé et le site en ligne n'a pas changé.

Le premier envoi transfère environ 8 000 fichiers et peut prendre 10 à 20 minutes ; les suivants
ne transfèrent que les fichiers modifiés.

> Si l'étape FTP échoue sur une erreur de connexion chiffrée, ajoutez dans **Settings → Secrets
> and variables → Actions → Variables** une variable `FTP_PROTOCOL` = `ftp`.

## G2. Mise en ligne manuelle avec un zip (sans GitHub)

1. Sur votre PC, dans le dossier `jnuaf` : `powershell -ExecutionPolicy Bypass -File deploy\build.ps1`
   → produit `deploy\jnuaf-test.zip`.
2. cPanel → **Gestionnaire de fichiers** → `test.jnuaf.org` → **Charger** le zip → clic droit
   → **Extraire** dans `/test.jnuaf.org`. Vérifiez que `test.jnuaf.org/artisan` existe
   (pas de sous-dossier intermédiaire). Supprimez le zip.
3. Lancez l'installation depuis votre PC (PowerShell), en mettant votre jeton :

   ```powershell
   Invoke-WebRequest -Method Post -Uri https://test.jnuaf.org/_deploy -Headers @{ 'X-Deploy-Token' = 'VOTRE_JETON' } -UseBasicParsing | Select-Object -ExpandProperty Content
   ```

   La réponse liste chaque étape et se termine par **« Mise à jour terminée. »**

Pour une mise à jour : refaire 1 à 3 en extrayant **par-dessus** (le `.env`, les CV et les photos
ne sont pas dans le zip et restent intacts).

---

## H. Tâche Cron (rappels avant l'événement)

Sans SSH, on identifie d'abord la bonne commande PHP grâce à une tâche Cron de diagnostic :

1. cPanel → **Tâches Cron** → ajouter, réglage **Une fois par minute**, commande :

   ```
   ls /opt/alt/*/usr/bin/php /opt/cpanel/ea-php*/root/usr/bin/php /usr/local/bin/php > $HOME/php-chemins.txt 2>&1; php -v >> $HOME/php-chemins.txt 2>&1
   ```

2. Après 2 minutes, ouvrez `php-chemins.txt` (à la racine de votre espace, dans le gestionnaire
   de fichiers) : repérez le chemin correspondant à PHP 8.3 (ex. `/opt/alt/php83/usr/bin/php`).
   **Supprimez cette tâche de diagnostic.**

3. Ajoutez la tâche définitive, **Une fois par minute** :

   ```
   cd $HOME/test.jnuaf.org && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

   (remplacez le chemin PHP par celui trouvé à l'étape 2).

## I. Vérifier

- [ ] https://test.jnuaf.org s'affiche avec le cadenas HTTPS.
- [ ] Une inscription avec votre email : la page confirme et l'**email avec le badge PDF** arrive (voir les spams).
- [ ] Le lien « Gérer mon inscription » de l'email fonctionne.
- [ ] https://test.jnuaf.org/admin/connexion : connexion avec le compte de l'étape F.
- [ ] Admin → **Comptes & exports** : création des comptes des coordinations.
- [ ] Suppression de l'inscription de test (fiche → Supprimer définitivement).

Le site de test n'est pas indexé par Google (`JNUAF_NOINDEX=true`). Pour le réserver à l'équipe :
cPanel → **Confidentialité des répertoires** → `test.jnuaf.org/public` → protéger par mot de passe.

## En cas de problème

| Symptôme | Cause probable | Solution |
|---|---|---|
| `/_deploy` répond 404 | `DEPLOY_TOKEN` absent du `.env` ou trop court (32 caractères min.) | Corriger le `.env` (étape F) |
| `/_deploy` répond 403 | jeton différent de celui du `.env` | Vérifier le secret GitHub ou la commande PowerShell |
| `/_deploy` répond 429 | trop d'essais avec un mauvais jeton | Attendre 10 minutes |
| `/_deploy` répond « ÉCHEC » | la dernière commande affichée indique la cause (souvent la base) | Vérifier `DB_*` dans le `.env` |
| Erreur 500 sur le site | `.env` incomplet ou mauvaise version de PHP | Lire `storage/logs/laravel-AAAA-MM-JJ.log` dans le gestionnaire de fichiers |
| Page « Index of / » | racine du document incorrecte | Étape B : `test.jnuaf.org/public` |
| Les emails n'arrivent pas | identifiants SMTP | Corriger `MAIL_*`, puis relancer `/_deploy` (rafraîchit la configuration) |
| Modification du `.env` sans effet | configuration en cache | Relancer `/_deploy` |
| Rappels non envoyés | tâche Cron absente ou mauvais chemin PHP | Étape H |
