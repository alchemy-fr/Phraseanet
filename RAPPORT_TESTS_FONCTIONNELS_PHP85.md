# Rapport de tests fonctionnels — Phraseanet sous PHP 8.5

**Branche :** `php-8.5` · **Date :** 2026-11-11 · **Instance :** http://localhost:8082 (stack Docker locale, datastores réutilisés, 1 databox / 4 collections)
**Compte de test :** `admin@phraseanet.local` (super-admin)
**Méthode :** pilotage navigateur réel (Chrome via Playwright) + smoke-test HTTP authentifié de toutes les routes admin/prod + corrélation avec les logs PHP-FPM et workers.

---

## 1. Synthèse

| Domaine | Résultat |
|---|---|
| Authentification (login web) | ✅ OK |
| Production — chargement | ✅ OK (0 erreur JS en contexte) |
| Recherche (Elasticsearch) | ✅ OK — 9 résultats |
| Ouverture record / preview | ✅ OK |
| Dialogue d'édition (rendu) | ✅ OK — fenêtre d'édition rendue |
| Navigation SPA admin | ✅ OK (0 erreur JS en contexte) |
| Thésaurus (chargement) | ✅ OK |
| Génération de subdefs (vidéo/MP4Box) | ✅ OK (corrigé plus tôt) |
| **Admin › Moteur de recherche** | ❌ **500** — à corriger |
| **Éditeur › preview d'une icône de substitution (`/embed/`)** | ❌ **500** — à corriger |

27 routes admin/prod testées : **25 × 200, 1 × 500** (`/admin/search-engine/`). Les « 404 » d'un premier passage étaient des URL erronées de mon harnais, pas des bugs.
Le `ReferenceError: $ is not defined` observé en chargeant des partials admin **directement** est un artefact de test (ces vues se chargent en AJAX dans la SPA) : **0 erreur JS** en navigation réelle.

---

## 2. Points à corriger

### 🔴 BUG-1 — `/admin/search-engine/` renvoie 500 (régression Symfony Form)

**Impact :** la page d'administration du moteur de recherche est **totalement inaccessible** (500 immédiat, en lecture).

**Cause racine (confirmée dans les logs) :**
```
phraseanet.ERROR: Can't get a way to read the property "facets:_base:limit"
in class "Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions".
```
Le form type [`ElasticsearchSettingsFormType`](lib/Alchemy/Phrasea/SearchEngine/Elastic/ElasticsearchSettingsFormType.php#L156) ajoute des champs nommés `facets:<k>:limit`. Ce nom sert de `property_path` par défaut. Avec la montée de `symfony/form` + `symfony/property-access` (passage du mode permissif de Symfony 2.8 au mode **strict**), l'initialisation du formulaire (`setData` → `mapDataToForms`) tente de lire la propriété `facets:_base:limit` sur l'objet `ElasticsearchOptions`, qui n'existe pas → `NoSuchPropertyException` → 500. Avant la migration, l'accès retournait `null` silencieusement.

Ces champs **ne sont pas censés être mappés** : le contrôleur [`SearchEngineController`](lib/Alchemy/Phrasea/Controller/Admin/SearchEngineController.php#L41) les relit **à la main** via `$request->request->all('elasticsearch_settings')` (`preg_match('/^facets:(.+):limit$/', …)`).

**Correctif proposé (1 ligne, faible risque) :** marquer ces pseudo-champs `'mapped' => false`, exactement comme le champ `dumpField` juste au-dessus. Dans le closure `$addAgg` :
```php
$aggs[$k] = [
    'label'              => $label,
    'choices'            => $choices,
    'mapped'             => false,          // <-- ajouter : champs relus manuellement dans le contrôleur
    'attr'               => ['class' => 'aggregate'],
    'disabled'           => $disabled,
    'help_message'       => $help,
    'translation_domain' => false,
];
```
À valider : soumission du formulaire (le POST passe déjà par `$request`, donc non impacté par `mapped=false`).

---

### 🟠 BUG-2 — `/embed/` renvoie 500 sur une icône de substitution

**Impact :** à l'ouverture de l'éditeur d'un record affiché via une **icône de substitution** (vidéo sans preview générée, documents Office…), la frame d'aperçu charge `/embed/?url=/assets/.../substitution/video_mp4.png` → **500** (aperçu cassé + bruit dans les logs). L'édition elle-même reste fonctionnelle.

**Cause racine (logs) :**
```
phraseanet.ERROR: No routes found for "/assets/common/images/icons/substitution/video_mp4.png".
  at Alchemy\Embed\Embed\EmbedController::matchResourceRequest (embed-bundle, ligne 228)
```
L'`EmbedController` tente de matcher l'URL passée en paramètre contre les routes de l'application. Quand cette URL est un **asset statique** (icône de substitution) et non une URL de record routable, `UrlMatcher::match()` lève `ResourceNotFoundException`, non rattrapée → 500.

**Pistes de correctif :**
- Rattraper `ResourceNotFoundException` dans `matchResourceRequest`/`viewAction` et répondre proprement (servir l'asset ou renvoyer un 404 léger) ; **ou**
- Côté front : ne pas router les icônes de substitution via `/embed/` (les afficher directement comme `<img>`).

À qualifier : pré-existant vs révélé par la migration (l'embed-bundle est déjà patché pour 8.5). Dans les deux cas, un 500 sur un cas d'aperçu courant mérite correction.

---

## 3. Flux validés (OK)

- **Login** web (CSRF + session) → redirection `/prod/`. *(capture `01_login.png`)*
- **Production** : chargement complet, 0 erreur JS console. *(`02_prod_load.png`)*
- **Recherche** via la vraie barre (`input.search.query`) → **9 vignettes** retournées par Elasticsearch. *(`03_prod_search_all.png`)*
- **Ouverture d'un record** (double-clic) → conteneur d'aperçu présent. *(`04_record_open_preview.png`)*
- **Éditeur** : la fenêtre d'édition se construit et s'affiche (hors BUG-2 sur l'aperçu substitution). *(`05_edit_dialog.png`)*
- **SPA admin** : navigation au menu sans erreur JS en contexte. *(`06_admin_spa.png`)*
- **Pages admin 200** : dashboard, databoxes, databox/1, collections, champs, structure, status-bits, subdefs, utilisateurs (recherche/inscriptions/échecs), worker-manager, task-manager, setup, plugins, publications, inspector, phraseanet-service.
- **Thésaurus, compte, lightbox, upload, workzone** : 200.
- **Génération de subdefs vidéo** (MP4Box) et dépréciation `php-ffmpeg` : corrigées plus tôt dans la session.

Captures : `/private/tmp/claude-503/.../scratchpad/uitest/shots2/`

---

## 4. Non testé (volontairement — opérations mutantes)

Pour ne pas modifier tes datastores réutilisés sans ton accord, je n'ai **pas** exécuté les écritures suivantes. Je peux les dérouler sur demande (sur une collection/un enregistrement jetable, puis nettoyage) :

- **Édition de métadonnées** : `POST /prod/records/edit/apply/` (modifier puis vérifier la persistance + réindexation ES).
- **Création de collection / base** : `POST` admin databox → collection, puis montée des droits.
- **Manipulation du thésaurus** : ajout/renommage de terme, propagation.
- **Téléchargement/export** bout-en-bout : `prepare → execute (zip) → get` (le download a déjà été validé plus tôt dans la session).
- **Upload** d'un nouvel asset + chaîne worker (createRecord → subdefCreation → writeMetadatas).

---

## 5. Améliorations suggérées

1. **Durcir le rendu d'erreur des formulaires admin** : un champ non mappé ne devrait jamais produire un 500 — auditer les autres form types pour le même motif `property_path`/`mapped` suite à la montée Symfony (candidats : réglages databox, structure, subdefs).
2. **Grep des dépréciations PHP 8.5 restantes** en conditions réelles : rejouer les parcours avec `log_errors` et agréger par fichier vendor, pour tarir les derniers flux (même approche que php-semver / ringphp / php-ffmpeg).
3. **Embed** : rendre `matchResourceRequest` tolérant aux URL non routables (assets) — évite le 500 et nettoie les logs.
4. **Observabilité** : les traces d'erreur JSON mono-ligne sont énormes dans les logs ; envisager un canal « erreurs » séparé ou un formateur plus compact en dev.
5. **jQuery 1.11.3 → 3.x** (déjà noté, lot front séparé) : plusieurs écrans prod reposent encore dessus.
6. **Tests de non-régression automatisés** : le harnais Playwright de cette session (login + parcours + capture erreurs console/HTTP) peut être gardé comme smoke-test de la migration.

---

## 6. Correctifs prioritaires recommandés

1. **BUG-1** (`mapped => false`) — rapide, débloque toute l'admin moteur de recherche.
2. **BUG-2** (embed tolérant aux assets) — supprime un 500 récurrent à l'édition.

Je peux appliquer BUG-1 immédiatement (1 ligne) et investiguer BUG-2 dans la foulée — dis-moi si je committe sur `php-8.5`.

---

## 7. Suivi — correctifs & améliorations réalisés

| # | Sujet | Statut | Commit |
|---|---|---|---|
| BUG-1 | 500 `/admin/search-engine/` (`mapped => false`) | ✅ corrigé (page → 200) | `ecd5d1e5d1` |
| BUG-2 | 500 `/embed/` sur asset non routable → 404 propre | ✅ corrigé (patch bundle) | `6414175615` |
| #1 | Audit autres form types (motif property_path) | ✅ vérifié — aucun autre affecté | — |
| #2 | Dépréciations 8.5 en conditions réelles | ✅ vérifié — 0 sur le parcours web | — |
| #3 | Embed tolérant aux URL non routables | ✅ = BUG-2 | `6414175615` |
| #4 | Observabilité — trace compacte sur 500 | ✅ fait (`getTraceAsString()`) | `9996b964c2` |
| #5 | jQuery 1.11.3 → 3.x | 📋 cadré (non exécuté) — voir [JQUERY_MIGRATION_ASSESSMENT.md](JQUERY_MIGRATION_ASSESSMENT.md) | — |
| #6 | Smoke-test navigateur de non-régression | ✅ ajouté — `tests/ui-smoke/` (16/16 vert) | `878ba23540` |

**Smoke-test de référence :** `cd tests/ui-smoke && PPASS='…' npm run smoke` → 16 étapes, exit 0.

---

## 8. Tests fonctionnels mutants (écritures) + incidents majeurs découverts

Tests d'écriture exécutés sur l'instance (données jetables autorisées).

| Test | Méthode | Résultat |
|---|---|---|
| **Création de collection** | `POST /admin/databox/1/collection/` | ✅ OK — nouvelle collection `base_id 5`, active |
| **Édition de métadonnées** | `POST /prod/records/edit/applyjs/` (JSON) sur record 6, champ Title | ✅ OK — valeur persistée en base **après correction des incidents ci-dessous** |
| **Upload (ajout)** | `POST /prod/upload/` (multipart + token `prodUpload`) sur base 1 | ✅ OK — record 10 créé + **tous les subdefs générés** (document, preview, preview_mobile, thumbnail, thumbnail_mobile) |
| **Thésaurus (ajout de terme)** | `GET /thesaurus/xmlhttp/newts.x.php` sous `T76.2` | ✅ OK — terme créé et présent dans l'XML du thésaurus |
| **Téléchargement** | — | ✅ validé plus tôt dans la session |

### 🔴 INCIDENT A — Workers tués par le garde de version (DB non migrée)

**Symptôme :** l'édition de métadonnées ne se persistait pas ; les messages s'empilaient dans la file `mainQueue` (**0 consommateur**). Des éditions antérieures (records 7, 8, 9) étaient **silencieusement bloquées** depuis la session précédente.

**Cause racine :** la base était en version **`4.1.25`** (appbox *et* databox) alors que le code est en **`4.2.0`** — très probablement remise à 4.1.25 lors du **re-dump des datastores**. Le garde de [`bin/console`](bin/console#L105) (`ConfigurationTester::isUpToDate()`) lève alors `Phraseanet is not up-to-date` et **tue tout worker au démarrage**. Les workers lancés *avant* le bump continuaient ; tout worker **redémarré après** mourait → files sans consommateur.

**Correctif appliqué :** `php bin/setup system:upgrade -y` → versions passées à **4.2.0** (aucune migration de schéma 4.1.25→4.2.0, simple bump). Le garde passe de nouveau.

**Règle durable :** **tout bump de version OU toute restauration de datastore doit être suivi d'un `bin/setup system:upgrade`.**

### 🔴 INCIDENT B — Attribution des files worker non déterministe (setup dev)

**Symptôme :** après redémarrage simultané des workers, certaines files se retrouvent **sans consommateur** (editRecord, writeMetadatas, downloadAsync, exportMail, exposeUpload, assetsIngest, shareBasket) et d'autres **sur-abonnées** (ex. pullAssets = 4). Un conteneur nommé `w-editRecord` exécutait en réalité `worker:execute --queue-name=populateIndex`.

**Cause racine :** [`docker/phraseanet/worker/entrypoint.sh`](docker/phraseanet/worker/entrypoint.sh#L94) fait `rm -rf bin/run-worker.sh` puis régénère le script selon l'env du conteneur. Or en **dev**, `docker-compose.override.yml` monte toute la source (`.:/var/alchemy/Phraseanet`), donc `bin/` est **partagé** entre tous les conteneurs : au démarrage simultané, ils écrasent concurremment le **même** `bin/run-worker.sh` (race) → attribution des files corrompue. **En production ce bug n'existe pas** (chaque image a son propre `bin/`, non partagé).

**Correctif appliqué (opérationnel) :** redémarrage **séquentiel** des workers (chaque conteneur seul régénère et lit son script sans race) → chaque file retrouve son consommateur.

**Correctif durable proposé (dev) :** générer le script worker hors du répertoire bind-mounté — p.ex. écrire `/tmp/run-worker.sh` dans l'entrypoint et pointer la `command` du service worker dessus. Supprime la race sans impacter la prod. *(non appliqué — nécessite de modifier entrypoint + `command` compose ; à valider ensemble.)*

### Artefacts de test laissés en base (données jetables)
- Collection `base_id 5` (`TEST-CLAUDE-*`)
- Record 10 (upload `test1.jpg`)
- Record 6 : Title = `TEST-CLAUDE-EDIT-*`
- Terme thésaurus `TESTCLAUDETERM*` sous T76.2

À purger sur demande.
