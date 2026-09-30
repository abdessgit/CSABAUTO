# Guide de Déploiement en Production — CS AB AUTO

Ce guide détaille la procédure et les variables d'environnement obligatoires pour le déploiement sécurisé du backend Symfony et du frontend React en environnement de production (MVP).

---

## 1. Variables d'environnement de Production (Backend Symfony)

Sur le serveur de production, les variables d'environnement ne doivent **jamais** être commitées dans Git. Elles doivent être définies soit dans le système d'exploitation du serveur (variables d'environnement système / conteneur Docker / panneau d'hébergement), soit dans un fichier non commité `.env.local`.

### Tableau des variables obligatoires

| Variable            | Valeur attendue en Production                                               | Description / Rôle sécuritaire                                                            |
| :------------------ | :-------------------------------------------------------------------------- | :---------------------------------------------------------------------------------------- |
| `APP_ENV`           | `prod`                                                                      | Désactive le mode debug et active les optimisations de cache Symfony.                     |
| `APP_DEBUG`         | `0`                                                                         | **Critique :** Empêche l'affichage des stack traces et données sensibles en cas d'erreur. |
| `APP_SECRET`        | Chaîne aléatoire sécurisée de 32 octets                                     | Clé secrète Symfony (ex: générée via `openssl rand -hex 32`).                             |
| `DATABASE_URL`      | `postgresql://user:password@host:port/dbname?serverVersion=16&charset=utf8` | Chaîne de connexion à la base de données PostgreSQL de production.                        |
| `CORS_ALLOW_ORIGIN` | `'^https://(www\.)?csabauto\.fr$'`                                          | **Sécurité :** Restreint les requêtes API inter-domaines au domaine HTTPS du frontend.    |
| `JWT_SECRET_KEY`    | `%kernel.project_dir%/config/jwt/private.pem`                               | Chemin vers la clé privée de signature des tokens JWT.                                    |
| `JWT_PUBLIC_KEY`    | `%kernel.project_dir%/config/jwt/public.pem`                                | Chemin vers la clé publique de vérification des tokens JWT.                               |
| `JWT_PASSPHRASE`    | Mot de passe complexe                                                       | Passphrase protégeant la clé privée JWT.                                                  |
| `MAILER_DSN`        | `smtp://user:pass@smtp.provider.com:587`                                    | Transport SMTP réel (ex: Brevo, Mailjet, Sendgrid, Infomaniak).                           |
| `MAILER_FROM`       | `"no-reply@csabauto.fr"`                                                    | Adresse expéditrice légitime affichée dans les e-mails.                                   |
| `FRONTEND_URL`      | `"https://csabauto.fr"`                                                     | URL publique du site pour les liens d'activation de compte et de réinitialisation.        |
| `COMPANY_NAME`      | `"CSAB AUTO"`                                                               | Raison sociale de l'entreprise.                                                           |
| `COMPANY_ADDRESS`   | `"3 Bis Rue Albert Thomas, 62410 Meurchin"`                                 | Adresse du siège social.                                                                  |
| `COMPANY_PHONE`     | `"06 63 40 76 94"`                                                          | Numéro de téléphone professionnel.                                                        |
| `COMPANY_EMAIL`     | `"contact@csabauto.fr"`                                                     | Adresse e-mail de contact du garage.                                                      |
| `COMPANY_SIRET`     | `"851 442 152 00019"`                                                       | Numéro SIRET de l'entreprise.                                                             |

---

## 2. Compilation des variables d'environnement (`.env.local.php`)

Pour des performances maximales et une sécurité renforcée en production, Symfony recommande de compiler les variables dans un fichier PHP optimisé, évitant l'analyse de fichiers `.env` à chaque requête :

```bash
composer dump-env prod
```

Cette commande génère le fichier `csab-auto-backend/.env.local.php` contenant le tableau PHP figé des variables de production.

---

## 3. Sécurisation des clés JWT et des répertoires

Sur le serveur hôte :

```bash
# 1. Restreindre l'accès à la clé privée JWT (lecture seule par l'utilisateur système web)
chmod 750 config/jwt
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem

# 2. Permissions des répertoires de cache et logs
chmod -R 775 var/

# 3. Permissions du répertoire des uploads d'images d'annonces
mkdir -p public/uploads/annonces
chmod 755 public/uploads
chmod 755 public/uploads/annonces
```

---

## 4. Initialisation du premier compte Administrateur

En production, **n'exécutez pas `doctrine:fixtures:load`** afin d'éviter d'insérer des comptes de test aux identifiants connus.

Pour créer le premier compte administrateur en toute sécurité, utilisez la commande Symfony dédiée :

```bash
php bin/console app:create-user <email_admin> <Nom> <Prenom> ROLE_ADMIN <MotDePasseComplexe>
```

Exemple :

```bash
php bin/console app:create-user admin@csabauto.fr Bouzaroura Abdesslam ROLE_ADMIN "VotreMotDePasseUltraSecurise!"
```

Les administrateurs ainsi créés peuvent ensuite se connecter au dashboard et créer d'autres modérateurs via la route protégée `POST /api/utilisateurs/admin` ou depuis l'espace de gestion.

---

## 5. Exécution des migrations de base de données

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 6. Déploiement du Frontend (React / Vite)

Dans le dossier `csab-auto-frontend` :

1. Définir la variable d'environnement de production dans `.env.production` (ou injectée par votre outil CI/CD) :
    ```env
    VITE_API_URL=https://csabauto.fr/api
    ```
2. Compiler les assets pour la production :
    ```bash
    npm run build
    ```
3. Servir le dossier généré `dist/` via votre serveur web (Nginx, Apache ou hébergement statique) avec redirection HTTPS et routage SPA vers `index.html`.
