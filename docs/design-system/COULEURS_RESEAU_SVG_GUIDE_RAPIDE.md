# Guide rapide : Couleurs du réseau SVG par thème

## Valeurs extraites des mockups Adobe XD

### Thème Light
```css
--network-color: #003E82;  /* Bleu marine foncé */
--network-opacity: 0.7;
```
**RGB** : `rgb(0, 62, 130)`

---

### Thème Dark Blue
```css
--network-color: #FFFFFF;  /* Blanc */
--network-opacity: 0.6;
```
**RGB** : `rgb(255, 255, 255)`

---

### Thème Dark Red
```css
--network-color: #FFFFFF;  /* Blanc */
--network-opacity: 0.6;
```
**RGB** : `rgb(255, 255, 255)`

---

## Implémentation rapide

### HTML
```html
<div class="network-background" data-theme="light">
  <svg><!-- votre réseau géométrique --></svg>
</div>
```

### CSS
```css
:root[data-theme="light"] {
  --network-color: #003E82;
  --network-opacity: 0.7;
}

:root[data-theme="dark-blue"],
:root[data-theme="dark-red"] {
  --network-color: #FFFFFF;
  --network-opacity: 0.6;
}

.network-background path,
.network-background line {
  stroke: var(--network-color);
  opacity: var(--network-opacity);
}

.network-background circle {
  fill: var(--network-color);
  opacity: var(--network-opacity);
}
```

---

## Screenshots de référence

Les captures d'écran des mockups Adobe XD sont disponibles dans :

```
.playwright-mcp/
├── mockup-light-theme-network.png
├── mockup-dark-blue-theme-network.png
└── mockup-dark-red-theme-network.png
```

---

## Validation

Comparez visuellement vos mockups HTML avec les screenshots capturés pour valider les couleurs.

Si besoin d'ajustement :
- **Light theme** : Tester `#004A99` ou `#003366` si `#003E82` ne convient pas
- **Dark themes** : Tester `#E8F4FF` ou `#FFE8E8` si `#FFFFFF` semble trop fort

---

## Fichiers fournis

1. `RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md` - Rapport détaillé complet
2. `network-svg-theme-colors.css` - Fichier CSS prêt à l'emploi
3. `COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md` - Ce guide (référence rapide)
