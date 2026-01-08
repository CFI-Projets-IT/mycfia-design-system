# Plan de travail : Planification Publication Assets

**Date de creation** : 2026-01-08
**Objectif** : Creer les mockups/previews pour la fonctionnalite de planification des publications d'assets reseaux sociaux

---

## Contexte projet

### Stack application cible
- Symfony / Symfony UX
- Twig / Twig Components / Live Components
- Mercure (temps reel)

### Notre role
Creer le **Design System statique** (HTML/CSS/JS) qui servira de reference visuelle pour l'integration dans l'application Symfony.

### Travail deja accompli
- Templates de base (3 themes)
- Pages settings et profil
- Workflow campaign_generation complet (step1 a step5)
- Feature Upload & Mapping Contacts

---

## Objectif de la feature

### Placement dans le workflow
```
step5_validate_*.html (Assets generes et valides)
        |
        v
campaign_show_*.html (Vue campagne)
        |
        v
campaign_schedule_*.html (NOUVEAU - Planification)
```

### Fonctionnalite
Depuis la vue `campaign_show`, permettre de **planifier la publication** des assets reseaux sociaux generes via un calendrier interactif avec drag & drop.

---

## Specifications fonctionnelles

### Assets planifiables (reseaux sociaux uniquement)

| Asset | Planifiable | Icone Bootstrap |
|-------|-------------|-----------------|
| LinkedIn | Oui | `bi-linkedin` |
| Facebook | Oui | `bi-facebook` |
| Instagram | Oui | `bi-instagram` |
| GoogleAds | Oui | `bi-google` |
| BingAds | Oui | `bi-bing` |
| Iab | Oui | `bi-badge-ad` |
| **Email** | Non | - |
| **SMS** | Non | - |
| **Courrier** | Non | - |
| **Article** | Non (ovni) | - |

### Granularite de planification
- **Jour** : Selection dans le calendrier mensuel
- **Heure** : Selection du creneau horaire

---

## Composant calendrier : FullCalendar

### Choix technique
**FullCalendar** - Compatible avec le stack Symfony et Bootstrap

### Compatibilite Bootstrap
- Plugin `@fullcalendar/bootstrap` natif
- `themeSystem: 'bootstrap'` pour heriter des styles Bootstrap
- Classes CSS standard personnalisables avec le design system myCFiA

### Fonctionnalites cles

#### Drag & Drop depuis liste externe
```javascript
// Liste d'assets externes (draggable)
new FullCalendar.Draggable(containerEl, {
  itemSelector: '.fc-event',
  eventData: function(eventEl) {
    return { title: eventEl.innerText.trim() }
  }
});

// Calendrier (droppable)
var calendar = new FullCalendar.Calendar(calendarEl, {
  droppable: true,
  drop: function(arg) {
    // Callback apres drop
  }
});
```

#### Vues disponibles
| Vue | Code | Usage |
|-----|------|-------|
| Mois | `dayGridMonth` | Vue principale (notre besoin) |
| Semaine | `timeGridWeek` | Vue avec creneaux horaires |
| Jour | `timeGridDay` | Vue jour detaillee |

### Personnalisation CSS
- Elements `.fc-event` entierement stylisables
- Couleurs, badges, icones personnalisables
- Integration tokens design system myCFiA

---

## Conception UI

### Layout prevu
```
+-------------------------------------------------------------+
|  [Header campagne + navigation]                              |
+-------------------------------------------------------------+
|                                                              |
|  +-------------------------+  +---------------------------+  |
|  |   CALENDRIER MENSUEL    |  |  ASSETS RESEAUX SOCIAUX   |  |
|  |   (FullCalendar)        |  |  (liste draggable)        |  |
|  |                         |  |                           |  |
|  |   < Janvier 2025 >      |  |  [LinkedIn] Post 1/3      |  |
|  |  +--+--+--+--+--+--+--+ |  |  [LinkedIn] Post 2/3      |  |
|  |  |Lu|Ma|Me|Je|Ve|Sa|Di| |  |  [Facebook] Ad 1/2        |  |
|  |  +--+--+--+--+--+--+--+ |  |  [Instagram] Post         |  |
|  |  |  |  |  |  |  |  |  | |  |  [GoogleAds] Ad 1/2       |  |
|  |  |  |  |  |  |  |  |  | |  |  ...                      |  |
|  |  +--+--+--+--+--+--+--+ |  |                           |  |
|  |                         |  |  [ ] Retirer apres drop   |  |
|  +-------------------------+  +---------------------------+  |
|                                                              |
|              [Valider le planning] [Annuler]                 |
+-------------------------------------------------------------+
```

### Interactions
1. **Drag** : Prendre un asset dans la liste de droite
2. **Drop** : Deposer sur une date du calendrier
3. **Modal heure** : Selection du creneau horaire apres drop
4. **Visualisation** : L'asset apparait sur le calendrier avec son icone/couleur

---

## Structure technique

### Fichiers a creer/modifier

#### Modifications (3 fichiers)
```
campaign_generation/
  campaign_show_light.html      <- Ajouter CTA "Planifier"
  campaign_show_dark-blue.html  <- Ajouter CTA "Planifier"
  campaign_show_dark-red.html   <- Ajouter CTA "Planifier"
```

#### Nouveaux fichiers (3 fichiers)
```
campaign_generation/
  campaign_schedule_light.html
  campaign_schedule_dark-blue.html
  campaign_schedule_dark-red.html
```

#### Assets potentiels
```
assets/
  css/components/
    _campaign-schedule.css  <- Styles specifiques calendrier
  js/
    campaign-schedule.js    <- Simulation interactions drag & drop
```

### Process de creation
1. Copier le template du theme (`_template_*.html`)
2. Adapter les paths : ajouter `../` devant tous les chemins
3. Remplacer la sidebar : mettre la navigation Marketing
4. Mettre a jour les liens du footer
5. Implementer le contenu specifique

### Navigation sidebar (campaign_generation)
```html
<!-- Section Marketing -->
<div class="nav-section mb-4">
    <div class="nav-section-title-pill mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-megaphone"></i>
            <span>Marketing</span>
        </div>
    </div>
    <div class="nav-section-content">
        <a href="dashboard_*.html" class="sidebar-link">
            <i class="bi bi-grid"></i>
            <span>Mes campagnes</span>
        </a>
        <a href="step1_create_*.html" class="sidebar-link">
            <i class="bi bi-plus-circle"></i>
            <span>Nouvelle campagne</span>
        </a>
        <a href="analytics_*.html" class="sidebar-link">
            <i class="bi bi-bar-chart-line"></i>
            <span>Analytics</span>
        </a>
    </div>
</div>
```

---

## Contraintes design system

### Architecture AssetMapper (obligatoire)
- ZERO `style=""` inline
- ZERO `<style>` dans les templates
- ZERO `onclick=""` ou event handlers inline
- ZERO `<script>` dans les templates
- CSS dans fichiers dedies (`assets/css/`)
- JS dans fichiers dedies (`assets/js/`)
- Classes Bootstrap uniquement dans HTML

### Tokens a utiliser
- Couleurs : `var(--color-primary)`, `var(--bg-card)`, etc.
- Classes Bootstrap 5 : `.card`, `.btn`, `.badge`, etc.
- Icones Bootstrap Icons

---

## Etapes de realisation

### Phase 1 : Modification campaign_show (3 fichiers)
- [ ] Ajouter CTA "Planifier la publication" dans les actions
- [ ] Lien vers `campaign_schedule_*.html`
- [ ] Appliquer aux 3 themes

### Phase 2 : Creation campaign_schedule (3 fichiers)
- [ ] Structure HTML de base (depuis template)
- [ ] Layout calendrier + liste assets
- [ ] Integration visuelle FullCalendar (mockup statique)
- [ ] Liste assets draggables stylisee
- [ ] Appliquer aux 3 themes

### Phase 3 : CSS specifique
- [ ] Styles calendrier adaptes au design system
- [ ] Styles cartes assets par type (couleurs/icones)
- [ ] Responsive design

### Phase 4 : JS simulation (optionnel pour mockup)
- [ ] Simulation drag & drop visuelle
- [ ] Interactions basiques

### Phase 5 : Validation
- [ ] Validation design system (`ui-component-validator`)
- [ ] Test 3 themes
- [ ] Navigation fonctionnelle
- [ ] Ajout index.html

---

## Questions resolues

| Question | Reponse |
|----------|---------|
| Assets planifiables ? | Tout sauf SMS/Courrier/Email/Article |
| Granularite ? | Jour + Heure |
| Composant calendrier ? | FullCalendar (compatible Bootstrap) |
| Personnalisation UI ? | Libre (CSS personnalisable) |
| Retour arriere ? | Oui, vers campaign_show |

---

## Prochaine etape

**Phase 1** : Modification des fichiers `campaign_show_*.html` pour ajouter le CTA de planification.

---

**Derniere mise a jour** : 2026-01-08
