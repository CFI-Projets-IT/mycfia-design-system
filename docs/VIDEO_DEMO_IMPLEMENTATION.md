# Implémentation Lecteur Vidéo Démo

## Vue d'ensemble

Ce système crée une **démo vidéo interactive** qui présente le flow complet de création de campagne en 45 secondes, avec 23 étapes.

Au lieu d'une vraie vidéo, le système charge séquentiellement les vues HTML existantes dans un iframe et les fait défiler automatiquement avec des transitions fluides, comme une vidéo.

## Architecture

### 1. Fichiers créés

```
assets/
├── css/
│   └── video-demo.css              # Styles du lecteur (YouTube-like)
└── js/
    └── components/
        └── video-demo.js            # Module ES6 du lecteur

campaign_generation/
└── dashboard_light.html             # Modal + CTA intégrés
```

### 2. Intégration

Le module est automatiquement chargé via `main.js` :

```javascript
import { initVideoDemo } from "./components/video-demo.js";

// Auto-initialisation au chargement du modal
if (document.getElementById("videoDemoModal")) {
  initVideoDemo();
}
```

## Fonctionnalités

### ✅ Lecteur vidéo complet

- **Play/Pause** : Bouton central + overlay cliquable
- **Barre de progression** : Style YouTube avec scrubbing (clic pour naviguer)
- **Timer** : Affichage temps courant / durée totale (0:00 / 0:45)
- **Restart** : Bouton pour relancer la démo
- **Auto-play** : Défilement automatique des slides

### ✅ Tooltips contextuels

Chaque étape affiche un tooltip explicatif :
- Apparition automatique à chaque slide
- Auto-hide après 5 secondes
- Animation douce en haut du lecteur

### ✅ CTA Dashboard

Bouton **"Voir la démo"** avec :
- Icône play-circle
- Animation pulse pour attirer l'attention
- Gradient primary/secondary
- Effet hover avec élévation

### ✅ Responsive

- Desktop : Modal 90vw × 90vh
- Mobile : Plein écran avec contrôles toujours visibles
- Adaptation des tailles de police et boutons

## Séquence des slides (23 étapes)

```javascript
1.  dashboard_light.html              → Tableau de bord
2.  step1_create_light.html           → Création campagne
3.  step1_loading_light.html          → Analyse IA
4.  step1_review_light.html           → Validation brief
5.  step2_loading_light.html          → Génération personas
6.  step2_validate_light.html         → Validation personas
7.  step3_loading_light.html          → Analyse canaux
8.  step3_select_light.html           → Sélection canaux
9.  contact_upload_empty_light.html   → Import contacts (vide)
10. contact_upload_validating_light   → Validation fichier
11. contact_upload_analyzing_light    → Analyse structure
12. contact_upload_suggestions_light  → Suggestions mapping
13. contact_upload_mapping_light      → Mapping colonnes
14. contact_upload_preview_light      → Aperçu import
15. step5_loading_light.html          → Génération contenus
16. step5_recap_light.html            → Récap contenus
17. step5_result_light.html           → Résultats contenus
18. step6_select_light.html           → Sélection assets
19. step7_config_light.html           → Configuration
20. step7_loading_light.html          → Finalisation
21. step7_validate_light.html         → Validation finale
22. step8_schedule_light.html         → Planification
23. campaign_show_light.html          → Campagne créée !
```

**Durée totale** : 45 secondes (~2s par slide)

## Utilisation

### Pour l'utilisateur final

1. **Ouvrir le dashboard** : `campaign_generation/dashboard_light.html`
2. **Cliquer sur "Voir la démo"** (bouton avec animation pulse)
3. **Modal lecteur s'ouvre** avec la première slide
4. **Cliquer Play** ou sur l'overlay central
5. **La démo défile automatiquement** avec tooltips explicatifs
6. **Optionnel** : Cliquer sur la barre de progression pour naviguer

### Pour le développeur

#### Modifier la durée totale

```javascript
// Dans video-demo.js, ligne 18
this.duration = 45000; // 45 secondes (en millisecondes)
```

#### Ajouter/Modifier des slides

```javascript
// Dans video-demo.js, tableau slides
this.slides = [
    {
        url: "step1_create_light.html",
        tooltip: "Votre tooltip personnalisé"
    },
    // ... ajouter d'autres slides
];
```

#### Personnaliser les styles

Modifier `assets/css/video-demo.css` :
- `.video-demo-controls` : Contrôles vidéo
- `.video-demo-progress-bar` : Barre de progression
- `.video-demo-tooltip` : Tooltips contextuels
- `.btn-video-demo` : CTA dashboard

## Design System

### Couleurs utilisées

- **Primary** : `var(--color-primary)` (barre progression, accents)
- **Secondary** : `var(--color-secondary)` (gradient CTA)
- **Background** : `var(--color-background)` (modal)
- **Surface** : `var(--color-surface)` (header modal)

### Icônes Bootstrap

- `bi-play-circle-fill` : CTA déclencheur
- `bi-play-fill` : Bouton play
- `bi-pause-fill` : Bouton pause
- `bi-arrow-clockwise` : Bouton restart / replay
- `bi-info-circle` : Icône tooltip

## Performance

### Optimisations

1. **Lazy loading** : Le lecteur ne s'initialise qu'au premier affichage du modal
2. **Animation frames** : Utilisation de `requestAnimationFrame` pour animations fluides
3. **Cleanup** : Arrêt automatique de l'animation à la fermeture du modal
4. **Single instance** : Un seul lecteur créé, réutilisé à chaque ouverture

### Chargement des slides

- Les vues HTML sont chargées à la demande dans l'iframe
- Transition entre slides : 0.3s (configurable dans CSS)
- Pas de préchargement pour économiser la bande passante

## Compatibilité

- ✅ Chrome, Firefox, Safari, Edge (modernes)
- ✅ Desktop & Mobile
- ✅ Bootstrap 5.3.8
- ✅ ES6 Modules

## Améliorations futures possibles

1. **Précalcul thumbnails** : Générer des miniatures pour la timeline
2. **Keyboard shortcuts** : Espace = play/pause, flèches = navigation
3. **Vitesse de lecture** : x0.5, x1, x1.5, x2
4. **Sous-titres** : Piste de sous-titres optionnelle
5. **Analytics** : Tracker quels utilisateurs regardent la démo jusqu'au bout
6. **Export réel vidéo** : Générer une vraie vidéo MP4 via headless browser

## Notes techniques

### Pourquoi pas Stimulus ?

Le projet utilise des modules ES6 classiques, pas Stimulus de façon centralisée. La solution a été adaptée pour rester cohérente avec l'architecture existante.

### Iframe sandbox

L'iframe n'a pas de restrictions sandbox pour permettre le chargement des vues locales. En production Symfony, configurer les headers CSP appropriés.

### Modal Bootstrap

Le lecteur utilise le système de modals Bootstrap 5 natif :
- Événement `shown.bs.modal` pour initialiser le lecteur
- Événement `hidden.bs.modal` pour cleanup
- Pas de conflit avec le modal d'onboarding existant

## Support

En cas de problème :

1. **Console browser** : Vérifier les logs `[video-demo]`
2. **Vérifier chemins** : Les URLs des slides doivent être relatives au HTML parent
3. **Bootstrap JS** : S'assurer que Bootstrap bundle est chargé
4. **Module ES6** : Vérifier `type="module"` sur le script main.js

---

**Dernière mise à jour** : 2026-01-23
**Version** : 1.0.0
**Auteur** : Claude Code (Assistant IA)
