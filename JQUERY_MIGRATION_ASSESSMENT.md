# Cadrage migration jQuery 1.11.3 → 3.x

**But :** sortir de jQuery 1.11.3 (fin de vie, failles connues, incompatible avec l'écosystème moderne) vers jQuery 3.x, **sans régression** de l'UI prod/admin.
**Statut :** cadrage only — **non exécuté** (lot front à valider à part). Ce document pose le plan.

## 1. État des lieux

- **Version actuelle :** `jquery: ~1.11.3` (`package.json`).
- **Build front :** pipeline Gulp, sources dans `resources/gulp/components/vendors/`.
- **Plugins jQuery embarqués (10) :** `jquery-ui`, `jquery-mobile`, `jquery-file-upload`, `jquery-galleria`, `jquery-treeview`, `jquery-contextmenu`, `jquery-datetimepicker`, `jquery-lazyload`, `jquery-cookie`, (+ core).

## 2. Bonne nouvelle : le code applicatif est quasi compatible

Scan des APIs **supprimées en jQuery 3** dans `resources/www` + `www/skins` :

| API retirée en 3.x | Occurrences (code app) |
|---|---|
| `.size()` | 0 |
| `.andSelf()` | 0 |
| `$.browser` | 0 |
| `.live()` / `.die()` | 0 |
| `.load(fn)` (event) | 0 |
| `.bind()` / `.unbind()` / `.delegate()` | 0 |
| `.success()/.error()/.complete()` chaînés sur jqXHR | 0 (le seul cas est dans `jquery.mobile-1.4.5.min.js`, vendor) |

Les 23 `success:` / 11 `error:` détectés sont des **options de `$.ajax`** (toujours valides en 3.x), pas les méthodes dépréciées. ~27 appels `$.ajax/get/post` au total : surface modérée.

**Conclusion :** le blocage n'est **pas** le JS maison, mais la **compatibilité des plugins**.

## 3. Le vrai risque : les plugins

| Plugin | Risque 3.x | Action |
|---|---|---|
| **jquery-mobile 1.4.5** | ❌ **Bloquant** — abandonné, exige jQuery ≤ 1.x/2.x | Vérifier s'il est réellement utilisé ; si oui, remplacer/retirer ; sinon supprimer du bundle |
| **jquery-ui** (ancien) | ⚠️ < 1.12 casse sur jQuery 3 | Monter à jQuery UI ≥ 1.12.1 |
| jquery-file-upload | ⚠️ versions anciennes KO | Monter à une version compatible 3.x |
| jquery-datetimepicker | ⚠️ à vérifier | Tester / mettre à jour |
| galleria, treeview, contextmenu, lazyload, cookie | ⚠️ à auditer un par un | Tester sous 3.x, remplacer les abandonnés |

## 4. Plan proposé (lot dédié)

1. **Confirmer l'usage réel de jquery-mobile** (grep des composants prod/lightbox). S'il est mort, le retirer — ça débloque le plus gros.
2. **Remplacer `jquery.js` par jQuery 3.7.1** dans le bundle Gulp + monter jQuery UI à 1.12.1+.
3. **Activer `jquery-migrate` 3.x** en dev : il journalise à l'exécution chaque usage déprécié/cassé → liste de travail exacte et exhaustive.
4. **Mettre à jour les plugins** un par un à leur version compatible 3.x ; remplacer les abandonnés.
5. **Rejouer le smoke-test** [`tests/ui-smoke`](tests/ui-smoke/) (0 erreur console attendue) + passage manuel : upload, édition, lightbox, thésaurus, galleria.
6. **Retirer `jquery-migrate`** une fois les warnings à zéro.

## 5. Estimation

- **Effort :** moyen — dominé par l'audit/MAJ des 10 plugins, pas par le code app.
- **Risque :** moyen, concentré sur jquery-mobile et jquery-ui ; `jquery-migrate` le rend mesurable et progressif.
- **Pré-requis :** ce lot doit passer **après** la stabilisation fonctionnelle PHP 8.5 (en cours), et être validé écran par écran.
