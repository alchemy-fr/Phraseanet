# Tests end-to-end Phraseanet (Cypress 16.3.0)

Suite de tests automatisés qui couvre les fonctionnalités listées dans
[FONCTIONNALITES_PHRASEANET.md](../../FONCTIONNALITES_PHRASEANET.md).

Elle cible une instance Phraseanet **déjà démarrée** et se lance de deux façons :

| Mode | Quand l'utiliser | Commande |
|---|---|---|
| **Docker** (sans affichage) | CI, passage complet, aucune installation sur le poste | `docker compose -f docker-compose.cypress.yml run --rm cypress` |
| **Local, interactif** | écrire ou déboguer un test en regardant le navigateur | `cd tests/cypress && npm run cy:open` |

---

## 1. Lancer les tests dans Docker

Depuis la racine du dépôt, avec la stack Phraseanet démarrée :

```bash
# toute la suite
docker compose -f docker-compose.cypress.yml run --rm cypress

# une section
docker compose -f docker-compose.cypress.yml run --rm cypress --spec 'cypress/e2e/03-admin/*.cy.js'

# un seul fichier
docker compose -f docker-compose.cypress.yml run --rm cypress --spec 'cypress/e2e/00-login/login.cy.js'

# un autre navigateur (chrome, firefox, edge, electron)
docker compose -f docker-compose.cypress.yml run --rm cypress --browser firefox
```

- L'image `cypress/included:16.3.0` contient Node 26, Chrome, Firefox et Edge : rien à installer.
- Le compte admin et son mot de passe sont lus dans `.env` (`PHRASEANET_ADMIN_ACCOUNT_EMAIL`,
  `PHRASEANET_ADMIN_ACCOUNT_PASSWORD`).
- Les tests sont montés en volume : modifier un test ne demande pas de reconstruire l'image.
  Après une modification du `Dockerfile` ou de `entrypoint.sh`, lancer
  `docker compose -f docker-compose.cypress.yml build`.
- Le code de sortie vaut `0` si tout passe, sinon le nombre de tests en échec.

---

## 2. Suivre les tests visuellement

### 2.1 Mode interactif (recommandé)

Cypress tourne directement sur le poste, hors Docker, et affiche le navigateur qu'il pilote.

**Prérequis :** Node.js ≥ 20 et un navigateur (Chrome, Firefox ou Edge ; Electron est fourni par Cypress).

```bash
cd tests/cypress
npm run cy:open
```

Au premier lancement, le script installe Cypress (`npm install`). Ensuite :

1. Choisir **E2E Testing**.
2. Choisir un navigateur, puis **Start E2E Testing**.
3. Cliquer sur un spec (par exemple `01-prod/01-ecran-selection.cy.js`).

L'écran affiche à gauche la liste des commandes et à droite l'application testée :

- **Lecture en direct** : chaque clic, saisie ou requête est visible au moment où il se produit.
- **Retour dans le temps** : survoler ou cliquer une commande montre la page telle qu'elle était à cette étape (avant et après).
- **Requêtes réseau** : les appels interceptés (`cy.intercept`) et les `cy.request` apparaissent dans la liste avec leur statut.
- **Rechargement automatique** : enregistrer le fichier relance le test.
- **Débogage** : les DevTools du navigateur restent disponibles. Ajouter `cy.pause()` dans un test pour l'arrêter et avancer pas à pas.

Le script `scripts/local.sh` lit l'URL, le compte et le mot de passe dans le fichier `.env` du
dépôt. Pour les fixer soi-même :

```bash
CYPRESS_BASE_URL=http://localhost:8082 \
CYPRESS_ADMIN_LOGIN=admin@phraseanet.local \
CYPRESS_ADMIN_PASSWORD='***' \
npm run cy:open
```

Le même script lance aussi un passage sans fenêtre, hors Docker :

```bash
npm run cy:run -- --spec 'cypress/e2e/01-prod/*.cy.js'
```

**Dépannage du mode local**

| Symptôme | Cause | Solution |
|---|---|---|
| `bad option: --no-sandbox` au démarrage | Le terminal intégré d'un éditeur basé sur Electron (VS Code…) exporte `ELECTRON_RUN_AS_NODE` | Passer par `npm run cy:open`, qui retire cette variable, ou lancer `unset ELECTRON_RUN_AS_NODE` |
| `npm ERR! cb.apply is not a function` | Un ancien `npm` présent dans un `node_modules` parent passe devant celui du système | Passer par `npm run cy:open` / `cy:run` : le script utilise le bon `npm` et le binaire Cypress local |
| Retour permanent sur la page de connexion | L'URL n'est pas `localhost` et le cookie de session porte le drapeau `Secure` sur une instance en HTTP | Utiliser `http://localhost:<port>` |

### 2.2 Vidéos des passages lancés dans Docker

Le conteneur n'a pas d'écran, mais il peut enregistrer une vidéo par spec :

```bash
docker compose -f docker-compose.cypress.yml run --rm cypress --config video=true --spec 'cypress/e2e/01-prod/*.cy.js'
```

| Sortie | Emplacement |
|---|---|
| Vidéos (une par spec) | `tests/cypress/cypress/videos/` |
| Captures des échecs (toujours actives) | `tests/cypress/cypress/screenshots/` |

### 2.3 Fenêtre Cypress depuis Docker

C'est possible, mais il faut fournir au conteneur un serveur graphique X11 (natif sous Linux,
XQuartz ou VcXsrv sur les autres systèmes) et lancer `cypress open` à la place de `cypress run`.
Le mode interactif local (2.1) donne le même résultat plus simplement.

---

## 3. Configuration

### Variables

| Variable | Défaut | Rôle |
|---|---|---|
| `CYPRESS_BASE_URL` | `http://localhost:${PHRASEANET_APP_PORT}` | URL ouverte par le navigateur |
| `PHRASEANET_RELAY` | `host.docker.internal:${PHRASEANET_APP_PORT}` | Docker seulement : cible du relais `socat` (vide = pas de relais) |
| `CYPRESS_ADMIN_LOGIN` | `PHRASEANET_ADMIN_ACCOUNT_EMAIL` | Compte administrateur |
| `CYPRESS_ADMIN_PASSWORD` | `PHRASEANET_ADMIN_ACCOUNT_PASSWORD` | Mot de passe, lu par `cy.env()` et jamais journalisé |
| `CYPRESS_ALLOW_RECORD_WRITES` | `0` | `1` active les tests qui écrivent sur des enregistrements existants |
| `CYPRESS_RETRIES` | `1` | Nombre de relances d'un test en échec (mode `run`) |

### Cibler une autre instance

```bash
CYPRESS_BASE_URL=https://phraseanet.example.com PHRASEANET_RELAY= \
CYPRESS_ADMIN_LOGIN=admin@example.com CYPRESS_ADMIN_PASSWORD='***' \
docker compose -f docker-compose.cypress.yml run --rm cypress
```

### Pourquoi un relais `socat` dans le conteneur

En local, PHP peut émettre le cookie de session avec le drapeau `Secure` alors que le site est
servi en HTTP. Les navigateurs n'acceptent un tel cookie que sur `http://localhost`. Le conteneur
ouvre donc `http://localhost:<port>`, et `socat` relaie ce port vers la stack publiée sur l'hôte.
En mode local (2.1), le navigateur est déjà sur `localhost` : aucun relais n'est nécessaire.

---

## 4. Prérequis sur l'instance

- **Index Elasticsearch à jour.** Un index désynchronisé renvoie des enregistrements supprimés,
  et les actions échouent sur ces fantômes. Pour le reconstruire :
  `docker compose exec phraseanet bin/console searchengine:index -p -t -n`.
- **Worker démarré** (service `worker`) pour les fonctions asynchrones : participants d'un partage
  (Lightbox), sous-définitions, mails, indexation. Sans worker, le spec Lightbox échoue avec un
  message qui le dit.
- Au moins un enregistrement image et une base avec des collections.
- **Thésaurus (optionnel).** Sans document thésaurus sur la base, les tests qui créent des termes
  sont marqués *pending*.

## 5. Données de test

Chaque spec crée ses propres objets, préfixés `cy-` (paniers, listes, reportages, fils,
utilisateurs, applications OAuth, collections `cycoll…`), et les supprime à la fin. Le dernier spec,
`99-nettoyage`, retire ce qu'un passage interrompu aurait laissé.

Par défaut, aucun enregistrement existant n'est modifié : les fenêtres d'édition, de rotation, de
déplacement ou de suppression sont ouvertes puis annulées. Les écritures réelles passent par
`CYPRESS_ALLOW_RECORD_WRITES=1`.

---

## 6. Couverture

| Section du document | Spec | Ce qui est vérifié |
|---|---|---|
| 1.0 Écran et sélection | `01-prod/01-ecran-selection` | 4 zones, onglets WorkZone, vignettes, clic / Maj / Ctrl, sélection par type, vue détaillée et ses onglets, Échap, flèches et answer-train |
| 1.1 Recherche | `01-prod/02-recherche` | recherche simple (API et UI), casse, documents ou reportages, opérateurs, `Champ:` / `Champ=`, AST, recherche avancée, filtres type et dates, tri, facettes, onglet Thésaurus |
| 1.2 à 1.6 Actions | `01-prod/03-actions` | palette selon les droits, refus sans sélection ; export (onglets, sous-définitions et poids, préparation réelle de l'archive) ; impression (6 modèles et options, PDF réellement généré pour chaque modèle) ; éditeur (zones, rechercher / remplacer, annulation, presets créés / chargés / supprimés, vocabulaire) ; propriétés ; déplacement ; suppression annulée ; outils (rotation, sous-définitions, métadonnées, éditeur vidéo) |
| 1.7 Paniers, Push, Share, listes | `01-prod/04-paniers-push-listes` | cycle complet d'un panier (création, WorkZone, menu, ajout, renommage, tri, retrait, archivage, parcours, vol d'élément, suppression sans perte de contenu) ; formulaires Push et Share avec droits par participant ; recherche de destinataires ; Push réel ; listes (CRUD, membres, partage) ; partage d'un enregistrement |
| 1.8 à 1.10 Reportages, WorkZone, preview | `01-prod/05-reportages-workzone` | reportage créé, recherchable, enrichi, réordonné, détaché / rattaché, supprimé ; WorkZone masquable, filtres, préférences ; infobulles, lecteur, renew-url |
| 1.11 à 1.15 Publications, Expose, upload, commandes, divers | `01-prod/06-publications-upload-commandes` | entrée de publication créée, éditée, consultée, supprimée ; abonnements RSS / Atom ; Expose ; fenêtre d'upload (fichier ajouté puis retiré, getHead) ; quarantaine ; commandes ; CGU, préférences, raccourcis, notifications, langue |
| 2 Lightbox | `02-lightbox/lightbox` | demande de validation réelle, liste, écran de validation, éléments, comparaison, votes, annotation, rapport, envoi du rapport, diaporama, entrée de publication |
| 3.1 et 3.2 Tableau de bord, paramétrage | `03-admin/01-dashboard-setup` | navigation Admin, vidage du cache, mail de test, administrateurs, état de la configuration, 12 sections du paramétrage |
| 3.3 à 3.6 Bases, collections, structure, sous-définitions | `03-admin/02-bases-collections-structure` | écrans de base (CGU, ordre, informations) ; cycle complet d'une collection de test ; champs (API, tags, vocabulaires, Dublin Core, création / suppression) ; statuts ; XML ; sous-définitions |
| 3.7 et 3.8 Utilisateurs | `03-admin/03-utilisateurs` | recherche et filtres, typeahead, création, droits accordés puis retirés, quotas / durée / masques, options de compte, données utilisateur, modèle créé et appliqué, export CSV, import, suppression, inscriptions, échecs d'authentification, sessions connectées |
| 3.9 à 3.13 Publications, moteur, tâches, workers, autres | `03-admin/04-publications-moteur-taches-workers` | fil créé, rendu public, publié en RSS / Atom, publieurs, supprimé ; réglages Elasticsearch ; ordonnanceur, jobs, validation XML, tâche créée / supprimée ; 9 écrans du Worker Manager ; Phraseanet Service, plugins, inspecteur, test des chemins |
| 4 Thésaurus | `04-thesaurus/thesaurus` | accès, deux volets, terme et synonyme créés, propriétés, recherche de candidats, lien champ ↔ branche, export texte et topics, import, onglet Prod, suppression |
| 5 Report | `05-report/report` | tableau de bord, 3 rapports × CSV / XLSX / ODS réellement produits, regroupements, champs, accès protégé |
| 6.1 à 6.4 Client, compte, développeurs, API | `06-autres/01-compte-developpeurs-api` | redirection Client ; barre de menu ; onglets du compte et enregistrement ; application OAuth créée, jeton, callback, grant password, webhooks et événements ; API v1 (Me, Databox, Search, Records, Baskets, Feeds, Quarantine, Monitor), v2 (Search, Orders), v3 ; `oauth_token` ; révocation |
| 6.5 Connexion | `00-login/login` | formulaire, langue, mot de passe oublié, refus d'un mauvais mot de passe, connexion, redirection sans session, inscription, déconnexion |
| 6.6 Diffusion | `06-autres/02-diffusion` | permaliens publics (GET, HEAD + en-tête `Link`, `&download`, page), embed, flux agrégés RSS / Atom |

### Non couvert

- **6.7 Contenu des mails** : il faudrait lire une boîte de test (MailHog).
- **6.8 Ligne de commande** : hors du navigateur. À tester par un script shell dans le conteneur `phraseanet`.
- **Expose, sous-titrage automatique, Bridge** : ils dépendent de services externes. Seuls les écrans et les routes sont vérifiés.
- **Autocomplétion de la recherche Prod** (`POST /prod/query/completion/`) : la route existe encore, mais aucune interface ne l'appelle et l'index n'a plus le sous-champ `suggest` qu'elle interroge (supprimé en 2019) ; elle n'est pas testée.
- **Glisser-déposer** (WorkZone, tri, thésaurus) : remplacé par les appels équivalents.

---

## 7. Structure

```
tests/cypress/
├── Dockerfile            # cypress/included:16.3.0 + socat
├── entrypoint.sh         # relais localhost -> instance, puis `cypress run`
├── cypress.config.js
├── package.json          # npm run cy:open / cy:run (mode local)
├── scripts/local.sh      # lit .env et lance Cypress hors Docker
└── cypress/
    ├── e2e/              # un dossier par section du document
    ├── fixtures/         # image PNG pour l'upload
    └── support/
        ├── commands.js   # connexion (cy.session), jetons CSRF, recherche, helpers Prod / Admin
        └── phraseanet.js # création / suppression des objets de test
```

Phraseanet protège ses formulaires par un jeton de session `<formulaire>_token`, rendu dans la page
qui porte le formulaire. `cy.formToken(page, nom)` le lit avant chaque action protégée. Les réponses
JSON exigent `Accept: application/json` et `X-Requested-With`, comme le fait le client Prod.
