# Analyse des couleurs du réseau géométrique SVG - Mockups Adobe XD

**Date d'analyse** : 2025-12-16
**URLs analysées** : 3 mockups Adobe XD (Light, Dark Blue, Dark Red themes)

---

## Contexte et méthodologie

L'objectif de cette analyse est d'extraire les couleurs exactes du réseau géométrique SVG positionné en bas à droite de chaque mockup, afin d'ajuster les filtres CSS dans les mockups HTML correspondants.

### Limitations techniques rencontrées

1. **Adobe XD Web utilise un canvas protégé** : Le mockup est rendu dans un élément `<canvas>` avec des protections empêchant l'accès programmatique aux données de pixels (CORS/WebGL).
2. **Impossibilité d'extraction DOM** : Les éléments SVG ne sont pas accessibles directement dans le DOM car le design est rendu via canvas.
3. **Méthode utilisée** : Analyse visuelle détaillée basée sur des screenshots haute qualité capturés via Playwright.

---

## Résultats d'analyse par thème

### 1. Light Theme

**URL** : https://xd.adobe.com/view/25e0924d-843b-48a4-b03c-9da2cfd4d258-70b3/screen/f9e9f07e-a568-4fea-8984-8c183af54b5d/

**Screenshot** : `/home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia-designSystem/.playwright-mcp/mockup-light-theme-network.png`

#### Observations visuelles

- **Fond général** : Blanc avec léger dégradé rose pâle (#FFF5F5 - #FFFFFF)
- **Réseau géométrique** : Situé en bas à droite
- **Couleur du réseau** : Bleu marine/foncé

#### Analyse de couleur (estimation visuelle)

Le réseau géométrique (lignes et nœuds) présente une teinte **bleu marine foncé**.

**Estimation hexadécimale** :
- **Couleur principale** : `#003E82` à `#004A99` (bleu marine)
- **Opacité estimée** : 0.6 à 0.8

**Recommandations CSS** :
```css
/* Pour le thème Light */
.network-svg-light {
  filter: invert(0%) sepia(0%) saturate(100%)
          hue-rotate(0deg) brightness(15%) contrast(100%);
  /* Ou directement avec couleur */
  color: #003E82;
  opacity: 0.7;
}
```

**Valeur RGB approximative** : `rgb(0, 62, 130)` à `rgb(0, 74, 153)`

---

### 2. Dark Blue Theme

**URL** : https://xd.adobe.com/view/25e0924d-843b-48a4-b03c-9da2cfd4d258-70b3/screen/e392c48a-b767-4d6f-905e-fc76ea537929/

**Screenshot** : `/home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia-designSystem/.playwright-mcp/mockup-dark-blue-theme-network.png`

#### Observations visuelles

- **Fond général** : Bleu très foncé / Navy (#1A2332 - #2C3E50)
- **Réseau géométrique** : Situé en bas à droite
- **Couleur du réseau** : Blanc ou bleu très clair

#### Analyse de couleur (estimation visuelle)

Le réseau géométrique apparaît en **blanc ou bleu très clair**, créant un contraste fort avec le fond sombre.

**Estimation hexadécimale** :
- **Couleur principale** : `#FFFFFF` à `#E8F4FF` (blanc à bleu très clair)
- **Opacité estimée** : 0.5 à 0.7

**Recommandations CSS** :
```css
/* Pour le thème Dark Blue */
.network-svg-dark-blue {
  filter: invert(100%) sepia(0%) saturate(0%)
          hue-rotate(0deg) brightness(100%) contrast(100%);
  /* Ou directement avec couleur */
  color: #FFFFFF;
  opacity: 0.6;
}
```

**Valeur RGB approximative** : `rgb(255, 255, 255)` à `rgb(232, 244, 255)`

---

### 3. Dark Red Theme

**URL** : https://xd.adobe.com/view/25e0924d-843b-48a4-b03c-9da2cfd4d258-70b3/screen/9ff5f83f-6fb8-482c-afd4-726591b9a8c7/

**Screenshot** : `/home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia-designSystem/.playwright-mcp/mockup-dark-red-theme-network.png`

#### Observations visuelles

- **Fond général** : Rouge bordeaux très foncé (#3D1F1F - #4A1E1E)
- **Réseau géométrique** : Situé en bas à droite
- **Couleur du réseau** : Blanc ou rose très clair

#### Analyse de couleur (estimation visuelle)

Le réseau géométrique apparaît en **blanc ou rose très clair**, similaire au thème Dark Blue mais sur fond rouge.

**Estimation hexadécimale** :
- **Couleur principale** : `#FFFFFF` à `#FFE8E8` (blanc à rose très clair)
- **Opacité estimée** : 0.5 à 0.7

**Recommandations CSS** :
```css
/* Pour le thème Dark Red */
.network-svg-dark-red {
  filter: invert(100%) sepia(0%) saturate(0%)
          hue-rotate(0deg) brightness(100%) contrast(100%);
  /* Ou directement avec couleur */
  color: #FFFFFF;
  opacity: 0.6;
}
```

**Valeur RGB approximative** : `rgb(255, 255, 255)` à `rgb(255, 232, 232)`

---

## Synthèse et recommandations

### Tableau récapitulatif

| Thème | Couleur réseau SVG (hex) | RGB approximatif | Opacité | Contraste avec fond |
|-------|--------------------------|------------------|---------|---------------------|
| **Light** | `#003E82` - `#004A99` | `rgb(0, 62, 130)` | 0.6-0.8 | Foncé sur clair |
| **Dark Blue** | `#FFFFFF` - `#E8F4FF` | `rgb(255, 255, 255)` | 0.5-0.7 | Clair sur foncé |
| **Dark Red** | `#FFFFFF` - `#FFE8E8` | `rgb(255, 255, 255)` | 0.5-0.7 | Clair sur foncé |

### Pattern observé

1. **Thème clair (Light)** : Réseau **foncé** (bleu marine) pour contraster avec le fond blanc
2. **Thèmes sombres (Dark Blue, Dark Red)** : Réseau **clair** (blanc/bleu clair/rose clair) pour contraster avec le fond sombre

### Stratégie d'implémentation CSS

#### Option 1 : Utilisation de filtres CSS

```css
/* Light theme */
[data-theme="light"] .network-background {
  filter: brightness(0.15) sepia(1) hue-rotate(200deg) saturate(5);
  opacity: 0.7;
}

/* Dark Blue theme */
[data-theme="dark-blue"] .network-background {
  filter: brightness(1.5) invert(1);
  opacity: 0.6;
}

/* Dark Red theme */
[data-theme="dark-red"] .network-background {
  filter: brightness(1.5) invert(1);
  opacity: 0.6;
}
```

#### Option 2 : Utilisation de variables CSS avec couleur directe

```css
:root[data-theme="light"] {
  --network-color: #003E82;
  --network-opacity: 0.7;
}

:root[data-theme="dark-blue"] {
  --network-color: #FFFFFF;
  --network-opacity: 0.6;
}

:root[data-theme="dark-red"] {
  --network-color: #FFFFFF;
  --network-opacity: 0.6;
}

.network-background path,
.network-background circle,
.network-background line {
  stroke: var(--network-color);
  opacity: var(--network-opacity);
}
```

---

## Prochaines étapes recommandées

1. **Validation visuelle** : Comparer les mockups HTML avec filtres CSS appliqués aux screenshots Adobe XD
2. **Ajustement fin** : Affiner les valeurs d'opacité et de couleur selon les retours visuels
3. **Tests de contraste** : Vérifier l'accessibilité (ratio de contraste suffisant)
4. **Documentation** : Intégrer ces valeurs dans le design system

---

## Notes techniques

### Hypothèses et limites

Cette analyse repose sur des **estimations visuelles** basées sur des screenshots. Les valeurs hexadécimales proposées sont des approximations qui devront être validées visuellement lors de l'implémentation.

Pour obtenir des valeurs **exactes**, il serait nécessaire de :
- Demander les fichiers sources Adobe XD (`.xd`) pour inspection native
- Utiliser un outil de color picker sur les exports PNG/SVG du design
- Consulter directement les spécifications de design auprès de l'équipe créative

### Fichiers de référence

Les screenshots de référence sont disponibles dans :
```
/home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia-designSystem/.playwright-mcp/
├── mockup-light-theme-network.png
├── mockup-dark-blue-theme-network.png
└── mockup-dark-red-theme-network.png
```

---

## Conclusion

L'analyse visuelle des trois mockups Adobe XD révèle un pattern cohérent :
- **Thème Light** : réseau bleu marine foncé (#003E82 approximatif)
- **Thèmes Dark** : réseau blanc/clair (#FFFFFF approximatif)

Ces valeurs permettront d'ajuster les filtres CSS pour reproduire fidèlement l'apparence du réseau géométrique dans les mockups HTML.
