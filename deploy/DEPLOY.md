# Guide de redéploiement — CelestiaWoW Website

## Prérequis serveur
- PHP 7.4+ avec extensions : mysqli, mbstring, gd, curl, json, openssl
- MySQL 8.0 ou MariaDB 10.5+
- Apache 2.4+ avec mod_rewrite activé (ou Nginx)
- Composer

---

## 1. Cloner le projet

```bash
git clone <repo-url> /var/www/website
cd /var/www/website
```

---

## 2. Dépendances PHP

```bash
composer install --no-dev
```

---

## 3. Créer les bases de données

```sql
CREATE DATABASE R0_Website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE R0_Auth     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Les bases AzerothCore (R1_World, R1_Chars, etc.) sont créées par le core WoW
```

### Importer les structures

```bash
# Site web (tables vides)
mysql -u root -p R0_Website < deploy/sql/R0_Website_structure.sql
mysql -u root -p R0_Auth    < deploy/sql/R0_Auth_structure.sql

# Tables DBC custom (données du client de jeu)
# Ces tables sont indépendantes du realm — appliquer sur chaque R*_World existant
mysql -u root -p R1_World < deploy/sql/R_World_dbc_data.sql
mysql -u root -p R2_World < deploy/sql/R_World_dbc_data.sql
mysql -u root -p R3_World < deploy/sql/R_World_dbc_data.sql

# Structure spell_dbc (données volumineuses, régénérer via le script d'import)
mysql -u root -p R1_World < deploy/sql/R_World_spell_dbc_structure.sql
mysql -u root -p R2_World < deploy/sql/R_World_spell_dbc_structure.sql
mysql -u root -p R3_World < deploy/sql/R_World_spell_dbc_structure.sql

# Structure itemdisplayinfo_dbc (données fournies par AzerothCore)
mysql -u root -p R1_World < deploy/sql/R_World_itemdisplayinfo_structure.sql
```

> **spell_dbc** : contient 56 000+ entrées extraites des fichiers .DBC du client WoW.
> Pour le repeupler, utiliser le script `App_Admin_tools/import-talent-dbc.mjs` depuis
> le poste de développement (nécessite les fichiers MPQ de CelestiaWoW).

---

## 4. Configuration

Copier les templates et remplir avec les vraies valeurs :

```bash
cp application/config/database.php.dist application/config/database.php
cp application/config/config.php.dist   application/config/config.php   # si besoin
```

### `application/config/database.php`
Configurer les connexions :
- `default` → `R0_Website` (base du site)
- `auth` → `R0_Auth` (comptes joueurs)
- `R1`, `R2`, `R3` → `R1_Chars`, `R2_Chars`, `R3_Chars` (personnages par realm)

### `application/config/email.php`
SMTP pour les emails de confirmation d'inscription, etc.

### `application/config/launcher_secrets.php`
Clés secrètes pour l'authentification du launcher.

---

## 5. Permissions

```bash
chmod -R 755 /var/www/website
chmod -R 777 /var/www/website/application/logs
chmod -R 777 /var/www/website/application/cache
chmod -R 777 /var/www/website/application/sessions
chmod -R 777 /var/www/website/assets/images/profiles
chmod -R 777 /var/www/website/assets/images/news
chmod -R 777 /var/www/website/assets/images/store
chmod -R 777 /var/www/website/assets/images/donate
```

---

## 6. Uploads utilisateurs (non versionnés)

Ces dossiers contiennent du contenu uploadé par les admins/utilisateurs.
Les restaurer depuis la sauvegarde serveur :

```
assets/images/profiles/   → avatars des joueurs
assets/images/news/        → images des actualités
assets/images/store/       → icônes des articles boutique
assets/images/donate/      → visuels des options de don
```

---

## 7. Apache — VirtualHost

```apache
<VirtualHost *:80>
    ServerName celestiawow.com
    DocumentRoot /var/www/website
    <Directory /var/www/website>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

S'assurer que `mod_rewrite` est activé : `a2enmod rewrite`

---

## 8. Vérification post-déploiement

- [ ] Page d'accueil s'affiche
- [ ] Connexion utilisateur fonctionne
- [ ] Armurerie accessible (`/armory`)
- [ ] Boutique accessible (`/store`)
- [ ] Panel admin accessible (`/admin`)
- [ ] Emails de confirmation envoyés
