# FIXME - Problèmes à Résoudre

Ce fichier liste les problèmes identifiés qui nécessitent une résolution future.

---

## 🔴 CRITIQUE - Token CFI : Expiration 30 minutes

### Problème

Le token d'authentification récupéré via l'API CFI (`/Utilisateurs/VerifToken` ou `/Utilisateurs/getUtilisateurMyCFiA`) a une **durée de vie de 30 minutes**.

### Impact Actuel

- L'utilisateur doit se **reconnecter toutes les 30 minutes**
- Les sessions de travail longues sont interrompues
- Mauvaise UX pour les utilisateurs du chat IA (conversations coupées)
- Aucun mécanisme de refresh automatique du token

### Comportement Observé

1. Utilisateur se connecte → Token CFI valide 30min
2. Après 30min d'inactivité → Token CFI expiré
3. Requête AJAX `/chat/{context}/message` → **302 Redirect /login**
4. Interface chat affiche erreur 500 ou erreur réseau
5. Utilisateur doit recharger la page et se reconnecter manuellement

### Solutions Possibles

#### Option 1 : Refresh Token Automatique (Recommandé)
```php
// Avant chaque appel API CFI dans CfiApiClient
if ($this->isTokenExpiringSoon()) {
    $this->refreshToken(); // Appel API refresh si disponible
}
```

**Prérequis** : Vérifier si l'API CFI expose un endpoint `/refresh-token`

#### Option 2 : Extension TTL Serveur
```php
// Dans CfiAuthenticator après authentification réussie
$session->set('cfi_token_expires_at', time() + 1800); // 30min
$session->set('cfi_token_refresh_threshold', time() + 1500); // Alert 5min avant

// Middleware pour vérifier expiration
if (time() > $session->get('cfi_token_refresh_threshold')) {
    // Tenter refresh ou forcer reconnexion
}
```

#### Option 3 : Notification Utilisateur Proactive
```javascript
// JavaScript côté client
setInterval(() => {
    const expiresAt = sessionStorage.getItem('token_expires_at');
    if (Date.now() > expiresAt - 5*60*1000) { // 5min avant expiration
        showWarning('Votre session expire dans 5 minutes. Sauvegardez votre travail.');
    }
}, 60000); // Check toutes les 1min
```

#### Option 4 : Keepalive Ping (Temporaire)
```javascript
// Ping toutes les 20min pour maintenir session active
setInterval(() => {
    fetch('/api/keepalive', { method: 'POST' });
}, 20 * 60 * 1000);
```

### Fichiers Concernés

- `src/Security/CfiAuthenticator.php` (stockage token + expiration)
- `src/Service/Cfi/CfiApiClient.php` (appels API avec token)
- `src/Security/UserAuthenticationService.php` (vérification validité token)
- `assets/js/chat.js` (gestion erreurs 401/403 côté client)
- `templates/chat/index.html.twig` (affichage warnings expiration)

### Priorité

**🔴 HAUTE** - Affecte directement l'expérience utilisateur des sessions longues.

### Date Identification

2025-10-14

### Assigné à

À déterminer (Sprint S2 ou S3 selon priorités)

---

## 📝 Instructions

- Ajouter les nouveaux problèmes ci-dessous avec la même structure
- Marquer ✅ les problèmes résolus avec date de résolution
- Déplacer les problèmes résolus en fin de fichier dans section "Résolu"

---

## ✅ Problèmes Résolus

_Aucun pour le moment_
