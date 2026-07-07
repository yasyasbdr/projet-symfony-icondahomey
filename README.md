# Icon Dahomey — Plateforme e-commerce (Symfony 7)

Boutique de créations au crochet : catalogue (créations physiques + patrons PDF),
panier persisté, commandes avec suivi de fabrication, personnalisation, messagerie
interne, espace client et back-office d'administration.

> **Important** : ce dépôt contient le **code source** du projet, **sans le dossier
> `vendor/`** (les dépendances). C'est normal : on ne versionne jamais `vendor/`.
> Suis les étapes ci-dessous pour installer et lancer le projet en local.

---

## 1. Prérequis

- PHP **8.2+** (avec extensions `pdo_mysql`, `intl`, `mbstring`, `bcmath`)
- **Composer**
- **MySQL 8** (ou MariaDB) — ou modifie `DATABASE_URL` pour PostgreSQL/SQLite
- (recommandé) la **CLI Symfony** : https://symfony.com/download
- (recommandé) **Mailpit** ou **MailCatcher** pour voir les emails en local

## 2. Installation

```bash
# 1. Installer les dépendances (génère le dossier vendor/)
composer install

# 3. Créer la base de données
php bin/console doctrine:database:create

# 4. Générer la migration À PARTIR des entités, puis l'exécuter
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 5. Charger les données de démonstration (fixtures)
sudo apt update
sudo apt install -y php8.3-bcmath

php bin/console doctrine:fixtures:load

# 6. Lancer le serveur
symfony serve
#    ou : php -S localhost:8000 -t public
```

Le site est alors accessible sur `https://localhost:8000` ou 'http://127.0.0.1:8000/'. 

## 3. Comptes de test (créés par les fixtures)

| Rôle              | Email                          | Mot de passe |
|-------------------|--------------------------------|--------------|
| Client            | `cliente@icon-dahomey.local`   | `password`   |
| Administrateur    | `admin@icon-dahomey.local`     | `password`   |
| Super-admin       | `super@icon-dahomey.local`     | `password`   |

- Espace client : `/mon-compte/commandes`
- Back-office : `/admin`

## 4. Architecture (points clés)

- **Héritage** : `Product` est une classe **abstraite** en *Single Table Inheritance*
  (une seule table `product`, colonne discriminante `product_type`). Sous-types :
  `PhysicalCreation` et `DigitalPattern`.
- **`Order`** : `order` étant un mot réservé SQL, la table est nommée `customer_order`
  (`#[ORM\Table(name: 'customer_order')]`).
- **Sécurité** : 3 rôles hiérarchisés (`ROLE_CLIENT` < `ROLE_ADMIN` < `ROLE_SUPER_ADMIN`),
  un `UserChecker` (bannissement) et un **Voter** `OrderVoter` (accès aux commandes).
- **Panier persisté** en base (`Cart` / `CartItem`), pas en session.
- **Formulaire dynamique** : `AddToCartType` ajoute les champs de mensurations via
  les *Form Events* selon le produit.
- **API JSON** en lecture : `/api/products` et `/api/products/{slug}`.
- **Mailer** : email de confirmation de commande (`OrderMailer`, routé en asynchrone via Messenger).
- **API externe** : `SmsSender` consomme une API SMS via `HttpClient`.
- **Requêtes personnalisées** : `ProductRepository::search()` (filtres + pagination + jointures anti N+1).

## 5. Tests

```bash
php bin/phpunit
```

- `tests/Unit/CartTest.php` — calcul du total du panier (sans BDD).
- `tests/Functional/SmokeTest.php` — la page de connexion répond.

---

Projet étudiant — Symfony 7 / Doctrine ORM 3.
