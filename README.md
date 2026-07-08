# Icon Dahomey - Plateforme e-commerce Icon Dahomey
Nom / Prénom / Compte Github : BADAROU Yasmine @yasyasbdr

Boutique de créations au crochet : catalogue filtrable (créations physiques + patrons PDF),
panier persisté, tunnel de commande avec paiement Stripe, suivi de fabrication,
personnalisation sur mesure (devis), messagerie client/admin, et back-office à
trois niveaux de rôles.

**Démo en ligne :** https://projet-symfony-icondahomey.onrender.com

---

## Sommaire
1. [Stack technique](#stack-technique)
2. [Prérequis](#prérequis)
3. [Installation en local](#installation-en-local)
4. [Comptes de test](#comptes-de-test)
5. [Tests & qualité](#tests--qualité)
6. [Fonctionnalités](#fonctionnalités)
7. [Points d'architecture](#points-darchitecture)
8. [Déploiement](#déploiement)

---

## Stack technique

- **PHP 8.3**, **Symfony 7.1**
- **Doctrine ORM 3** (MySQL 8 en local, PostgreSQL en production)
- **Twig** (vues), **Stripe** (paiement), **Symfony Mailer** + **HttpClient**
- **PHPUnit** (tests), **PHPStan** niveau 5 + linters Symfony (qualité)
- **Docker** (base de données en local, image de déploiement)

---

## Prérequis

- **PHP 8.2+** avec les extensions : `pdo_mysql`, `intl`, `mbstring`, **`bcmath`**
- **Composer 2**
- **Docker** + **Docker Compose** (pour la base de données locale)
  *(ou une instance MySQL 8 / MariaDB déjà installée)*
- (optionnel) la **CLI Symfony** : https://symfony.com/download

---

## Installation en local

```bash
# 1. Cloner le dépôt
git clone https://github.com/yasyasbdr/projet-symfony-icondahomey.git
cd projet-symfony-icondahomey

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement local
cp .env.local.dist .env.local
#   -> éditer .env.local : renseigner DATABASE_URL et générer un APP_SECRET
#      (ex : php -r "echo bin2hex(random_bytes(16));")
#   -> (optionnel) renseigner STRIPE_SECRET_KEY (clé de test) pour activer Stripe.
#      Sans clé, le paiement passe automatiquement en mode simulé.

# 4. Démarrer la base de données (conteneur MySQL + Adminer)
docker compose up -d
#   MySQL est accessible sur 127.0.0.1:3306
#   Adminer (interface web de la BDD) sur http://localhost:8080

# 5. Créer la base, exécuter les migrations
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Charger les données de démonstration (produits, photos, commandes, comptes)
php bin/console doctrine:fixtures:load --no-interaction

# 7. Lancer le serveur
symfony serve
#   ou, sans la CLI Symfony :
#   php -S localhost:8000 -t public
```

Le site est alors accessible sur **https://localhost:8000**.

> **Astuce** : si `bcmath` n'est pas activé, installez-le
> (`sudo apt install php8.3-bcmath` puis `sudo phpenmod bcmath`). Il est requis
> pour les calculs monétaires (précision exacte sur les prix).

---

## Comptes de test

Créés automatiquement par les fixtures (mot de passe identique pour tous : `password`) :

| Rôle              | Email                          | Mot de passe | Accès |
|-------------------|--------------------------------|--------------|-------|
| **Client**        | `cliente@icon-dahomey.local`   | `password`   | Espace client (`/mon-compte/...`) |
| **Administrateur**| `admin@icon-dahomey.local`     | `password`   | Back-office (`/admin`) |
| **Super-admin**   | `super@icon-dahomey.local`     | `password`   | Back-office complet (produits, clients) |

---

## Tests & qualité

```bash
# Tests unitaires + fonctionnels
php bin/phpunit

# Analyse statique
vendor/bin/phpstan analyse

# Linters Symfony
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console lint:container
```

L'ensemble est également exécuté automatiquement via **GitHub Actions**
(`.github/workflows/ci.yml`) à chaque push : linters -> PHPStan -> tests.

---

## Fonctionnalités

**Front (client)**
- Catalogue avec recherche et filtres (catégorie, prix, patron PDF, personnalisable, tri) + pagination
- Fiche produit : galerie, choix du type (création / patron), saisie de mensurations,
  demande de devis sur mesure
- Panier persisté en base, tunnel de commande, **paiement Stripe** (ou simulé)
- Suivi de fabrication (timeline), favoris, messagerie liée à la commande
- Personnalisation : demande de devis -> prix proposé -> acceptation -> commande à payer

**Back-office (admin / super-admin)**
- Tableau de bord, gestion des commandes (statut + progression), historique
- Gestion des demandes de personnalisation (fixation du prix)
- Messagerie : réponse au client
- CRUD des produits (super-admin), gestion des comptes (blocage / suppression)

**Technique**
- API JSON versionnée (`/api/v1/products`) via le Serializer + groupes de normalisation
- Emails transactionnels (Symfony Mailer), notifications SMS (HttpClient)
- Pages légales (mentions, CGV, confidentialité, contact)

---

## Points d'architecture

- **Héritage (Single Table Inheritance)** : `Product` est abstraite ; `PhysicalCreation`
  et `DigitalPattern` partagent une table unique `product` avec une colonne
  discriminante `product_type`.
- **Snapshot** : `OrderItem` recopie le nom et le prix au moment de l'achat ->
  l'historique des commandes reste figé même si le produit change ou est supprimé.
- **Sécurité** : 3 rôles hiérarchisés, un `UserChecker` (bannissement) et un `OrderVoter`
  (autorisation fine par objet). Mots de passe hachés.
- **Table `customer_order`** : `order` étant un mot réservé SQL, la table est renommée.
- **Requêtes optimisées** : `ProductRepository::search()` (QueryBuilder + jointures
  anti N+1 + pagination).

Le schéma de la base (MCD / diagramme entités-relations) est disponible dans
`icon_dahomey_mcd_erd.html`.

---

## Déploiement

Le projet est déployé sur **Render** (Docker + PostgreSQL).

---
