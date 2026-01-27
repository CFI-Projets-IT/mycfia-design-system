/**
 * Video Demo Component
 * Lecteur vidéo démo pour showcaser le flow complet de création de campagne
 */

class VideoDemoPlayer {
    constructor(modal) {
        this.modal = modal;
        this.viewport = modal.querySelector('.video-demo-viewport');
        this.iframe = modal.querySelector('[data-video-demo-target="iframe"]');
        this.playButton = modal.querySelector('[data-video-demo-target="playButton"]');
        this.progressBar = modal.querySelector('[data-video-demo-target="progressBar"]');
        this.progressFill = modal.querySelector('[data-video-demo-target="progressFill"]');
        this.currentTimeEl = modal.querySelector('[data-video-demo-target="currentTime"]');
        this.totalTimeEl = modal.querySelector('[data-video-demo-target="totalTime"]');
        this.tooltip = modal.querySelector('[data-video-demo-target="tooltip"]');
        this.playOverlay = modal.querySelector('.video-demo-play-overlay');
        this.player = modal.querySelector('.video-demo-player');

        // Configuration
        this.duration = 120000; // 120 secondes (2 minutes) - ~5s par slide
        this.currentSlide = 0;
        this.isPlaying = false;
        this.startTime = null;
        this.animationFrame = null;

        // Double buffering pour transitions smoothes
        this.iframeA = null;
        this.iframeB = null;
        this.currentIframe = null;

        // Séquence complète du flow avec tooltips contextuels enrichis
        this.slides = [
            {
                url: "dashboard_light.html",
                tooltips: [
                    "📊 Tableau de bord : Vue d'ensemble de toutes vos campagnes marketing"
                ]
            },
            {
                url: "step1_create_light.html",
                tooltips: [
                    "🚀 Étape 1 : Décrivez votre projet marketing en quelques lignes",
                    "💡 L'IA va analyser votre brief et générer automatiquement objectifs, cibles et recommandations"
                ]
            },
            {
                url: "step1_loading_light.html",
                tooltips: [
                    "⚡ L'IA analyse votre projet en temps réel",
                    "🧠 Elle identifie vos objectifs, votre marché et vos concurrents"
                ]
            },
            {
                url: "step1_review_light.html",
                tooltips: [
                    "✅ Validez le brief généré par l'IA",
                    "✏️ Modifiez ce qui ne vous convient pas - Vous gardez le contrôle total"
                ]
            },
            {
                url: "step2_loading_light.html",
                tooltips: [
                    "🔍 Détection intelligente de vos concurrents",
                    "🎯 L'IA analyse votre marché et identifie vos concurrents directs"
                ]
            },
            {
                url: "step2_validate_light.html",
                tooltips: [
                    "✨ Vos concurrents ont été identifiés",
                    "☑️ Sélectionnez ceux que vous souhaitez analyser pour votre stratégie"
                ]
            },
            {
                url: "step3_loading_light.html",
                tooltips: [
                    "👥 Génération automatique de vos personas",
                    "🎯 L'IA analyse votre audience et crée des profils détaillés de vos cibles"
                ]
            },
            {
                url: "step3_select_light.html",
                tooltips: [
                    "✨ Vos personas ont été générés",
                    "☑️ Sélectionnez les personas les plus pertinents pour votre campagne"
                ]
            },
            {
                url: "contact_upload_empty_light.html",
                tooltips: [
                    "📋 Étape 4 : Importez votre base de contacts",
                    "📁 Formats acceptés : CSV, Excel, TXT"
                ]
            },
            {
                url: "contact_upload_validating_light.html",
                tooltips: [
                    "🔍 Validation du format de votre fichier",
                    "✅ Vérification de l'intégrité des données"
                ]
            },
            {
                url: "contact_upload_analyzing_light.html",
                tooltips: [
                    "🧠 L'IA analyse la structure de vos données",
                    "🔎 Détection automatique des colonnes : nom, email, entreprise..."
                ]
            },
            {
                url: "contact_upload_suggestions_light.html",
                tooltips: [
                    "💡 L'IA suggère le mapping optimal de vos champs",
                    "⚡ Gain de temps : mapping intelligent automatique"
                ]
            },
            {
                url: "contact_upload_mapping_light.html",
                tooltips: [
                    "🔗 Associez vos colonnes aux champs de la plateforme",
                    "✏️ Ajustez le mapping automatique si nécessaire"
                ]
            },
            {
                url: "contact_upload_preview_light.html",
                tooltips: [
                    "👀 Aperçu de vos contacts importés",
                    "✅ Validez avant l'import final dans la campagne"
                ]
            },
            {
                url: "step5_loading_light.html",
                tooltips: [
                    "✍️ Génération des contenus marketing par l'IA",
                    "🎯 Accroches, posts LinkedIn, annonces Google Ads, emails..."
                ]
            },
            {
                url: "step5_recap_light.html",
                tooltips: [
                    "📝 Récapitulatif de tous les contenus générés",
                    "🎨 L'IA a créé des contenus personnalisés pour chaque canal"
                ]
            },
            {
                url: "step5_result_light.html",
                tooltips: [
                    "⭐ Tous vos contenus prêts à l'emploi !",
                    "✏️ Personnalisez chaque contenu selon vos besoins"
                ]
            },
            {
                url: "step6_select_light.html",
                tooltips: [
                    "🖼️ Étape 6 : Sélectionnez vos visuels et assets",
                    "📸 Images, vidéos, infographies pour chaque canal"
                ]
            },
            {
                url: "step7_config_light.html",
                tooltips: [
                    "⚙️ Étape 7 : Configuration de la diffusion",
                    "💰 Budget, durée, fréquence, ciblage avancé..."
                ]
            },
            {
                url: "step7_loading_light.html",
                tooltips: [
                    "🔄 Finalisation de votre campagne",
                    "✅ Vérification des paramètres avant validation"
                ]
            },
            {
                url: "step7_validate_light.html",
                tooltips: [
                    "🎯 Validation finale de votre campagne multicanale",
                    "🚀 Tout est prêt pour le lancement !"
                ]
            },
            {
                url: "step8_schedule_light.html",
                tooltips: [
                    "📅 Étape 8 : Planifiez le lancement",
                    "⏰ Choisissez la date et l'heure optimales pour votre audience"
                ]
            },
            {
                url: "campaign_show_light.html",
                tooltips: [
                    "🎉 Campagne créée avec succès !",
                    "📊 Suivez vos performances en temps réel : leads, conversions, ROI..."
                ]
            }
        ];

        this.slideDuration = this.duration / this.slides.length;

        this.init();
    }

    init() {
        // Setup double buffering iframes
        this.setupDoubleBuffering();

        // Bind des événements
        this.playButton?.addEventListener('click', () => this.togglePlay());
        this.playOverlay?.addEventListener('click', () => this.togglePlay());

        // Barre de progression (scrubbing)
        this.progressBar?.addEventListener('mousedown', (e) => this.seekStart(e));
        this.progressBar?.addEventListener('mousemove', (e) => this.seeking(e));
        this.progressBar?.addEventListener('mouseup', (e) => this.seekEnd(e));
        this.progressBar?.addEventListener('mouseleave', (e) => this.seekEnd(e));

        // Restart button
        const restartBtn = this.modal.querySelector('[data-action-restart]');
        restartBtn?.addEventListener('click', () => this.restart());

        // Recalculer zoom au resize (recharger la slide courante)
        window.addEventListener('resize', () => {
            if (this.currentIframe) {
                this.adjustIframeZoom(this.currentIframe);
            }
        });

        // Initialisation
        this.updateTotalTime();
        this.loadSlide(0);

        // Cleanup au fermeture du modal
        const modalEl = this.modal.closest('.modal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', () => this.cleanup());
        }
    }

    setupDoubleBuffering() {
        // Remplacer l'iframe unique par 2 iframes pour le fondu enchaîné
        if (this.iframe) {
            this.iframe.remove();
        }

        // Créer iframe A
        this.iframeA = document.createElement('iframe');
        this.iframeA.className = 'video-demo-iframe scaled';
        this.iframeA.title = 'Démo campagne A';
        // Sandbox pour désactiver JS (empêche les redirections auto des loaders)
        this.iframeA.setAttribute('sandbox', 'allow-same-origin');
        this.viewport.appendChild(this.iframeA);

        // Créer iframe B
        this.iframeB = document.createElement('iframe');
        this.iframeB.className = 'video-demo-iframe scaled';
        this.iframeB.title = 'Démo campagne B';
        // Sandbox pour désactiver JS (empêche les redirections auto des loaders)
        this.iframeB.setAttribute('sandbox', 'allow-same-origin');
        this.viewport.appendChild(this.iframeB);

        // A est actif au départ
        this.currentIframe = this.iframeA;
        this.iframeA.classList.add('active');
    }

    // ========================================
    // Contrôles lecture
    // ========================================

    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    play() {
        if (this.currentSlide >= this.slides.length) {
            this.restart();
            return;
        }

        this.isPlaying = true;
        this.startTime = Date.now() - (this.currentSlide * this.slideDuration);
        this.updatePlayButton();
        this.player?.classList.add('playing');
        this.animate();
    }

    pause() {
        this.isPlaying = false;
        this.updatePlayButton();
        this.player?.classList.remove('playing');
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }

    restart() {
        this.currentSlide = 0;
        this.loadSlide(0);
        this.play();
    }

    // ========================================
    // Animation et progression
    // ========================================

    animate() {
        if (!this.isPlaying) return;

        const elapsed = Date.now() - this.startTime;
        const progress = Math.min(elapsed / this.duration, 1);
        const slideIndex = Math.floor((elapsed / this.duration) * this.slides.length);

        // Mise à jour de la progression
        this.updateProgress(progress);
        this.updateCurrentTime(elapsed);

        // Changement de slide si nécessaire
        if (slideIndex !== this.currentSlide && slideIndex < this.slides.length) {
            this.currentSlide = slideIndex;
            this.loadSlide(slideIndex);
        }

        // Fin de la vidéo
        if (progress >= 1) {
            this.currentSlide = this.slides.length;
            this.pause();
            this.updatePlayButton(true); // Afficher icône replay
            return;
        }

        this.animationFrame = requestAnimationFrame(() => this.animate());
    }

    loadSlide(index) {
        if (index < 0 || index >= this.slides.length) return;

        const slide = this.slides[index];

        // Déterminer quel iframe utiliser pour le fondu enchaîné
        const nextIframe = this.currentIframe === this.iframeA ? this.iframeB : this.iframeA;

        // Charger le nouveau slide dans l'iframe inactif
        nextIframe.src = slide.url;

        // Attendre que l'iframe soit chargée pour calculer le zoom optimal
        nextIframe.onload = () => {
            this.adjustIframeZoom(nextIframe);

            // Crossfade : fade out l'ancien, fade in le nouveau
            setTimeout(() => {
                this.currentIframe.classList.remove('active');
                nextIframe.classList.add('active');

                // Swap : le nouveau devient l'actif
                this.currentIframe = nextIframe;
            }, 100);
        };

        // Afficher les tooltips séquentiellement
        this.showTooltipsSequence(slide.tooltips || []);
    }

    showTooltipsSequence(tooltips) {
        if (!tooltips || tooltips.length === 0 || !this.tooltip) return;

        // Vider le contenu actuel
        this.tooltip.innerHTML = '';
        this.tooltip.classList.remove('show');

        // Créer un élément par tooltip et les afficher tous ensemble
        tooltips.forEach((text) => {
            const tooltipItem = document.createElement('div');
            tooltipItem.className = 'video-demo-tooltip-item';
            tooltipItem.textContent = text;
            this.tooltip.appendChild(tooltipItem);
        });

        // Afficher le conteneur
        setTimeout(() => {
            this.tooltip.classList.add('show');
        }, 200);
    }

    adjustIframeZoom(iframe) {
        if (!iframe || !this.viewport) return;

        try {
            const viewportRect = this.viewport.getBoundingClientRect();
            const viewportWidth = viewportRect.width;
            const viewportHeight = viewportRect.height;

            // Lire les dimensions réelles de la page chargée dans l'iframe
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const pageWidth = iframeDoc.documentElement.scrollWidth;
            const pageHeight = iframeDoc.documentElement.scrollHeight;

            // Calculer le zoom pour que TOUTE la page rentre (largeur ET hauteur)
            const zoomX = viewportWidth / pageWidth;
            const zoomY = viewportHeight / pageHeight;
            const zoom = Math.min(zoomX, zoomY, 1); // Ne jamais zoomer au-delà de 100%

            // Appliquer le zoom
            iframe.style.zoom = zoom;

            console.log(`[video-demo] Page: ${pageWidth}x${pageHeight}, Viewport: ${viewportWidth}x${viewportHeight}, Zoom: ${(zoom * 100).toFixed(1)}%`);
        } catch (error) {
            // Si on ne peut pas accéder au contenu de l'iframe (cross-origin), utiliser un zoom par défaut
            console.warn('[video-demo] Impossible de lire les dimensions de la page (cross-origin?), zoom par défaut appliqué');
            const viewportRect = this.viewport.getBoundingClientRect();
            iframe.style.zoom = Math.min(viewportRect.width / 1600, 1);
        }
    }

    // ========================================
    // Barre de progression (scrubbing)
    // ========================================

    seekStart(event) {
        this.pause();
        this.updateSeekPosition(event);
    }

    seeking(event) {
        if (event.buttons !== 1) return; // Seulement si bouton gauche enfoncé
        this.updateSeekPosition(event);
    }

    seekEnd(event) {
        if (event.buttons !== 1) return;
        this.updateSeekPosition(event);
        this.play();
    }

    updateSeekPosition(event) {
        if (!this.progressBar) return;

        const rect = this.progressBar.getBoundingClientRect();
        const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));

        const newTime = percent * this.duration;
        const newSlideIndex = Math.floor(percent * this.slides.length);

        this.currentSlide = newSlideIndex;
        this.startTime = Date.now() - newTime;

        this.updateProgress(percent);
        this.updateCurrentTime(newTime);
        this.loadSlide(newSlideIndex);
    }

    // ========================================
    // Mise à jour UI
    // ========================================

    updateProgress(percent) {
        if (this.progressFill) {
            this.progressFill.style.width = `${percent * 100}%`;
        }
    }

    updateCurrentTime(milliseconds) {
        if (this.currentTimeEl) {
            this.currentTimeEl.textContent = this.formatTime(milliseconds);
        }
    }

    updateTotalTime() {
        if (this.totalTimeEl) {
            this.totalTimeEl.textContent = this.formatTime(this.duration);
        }
    }

    updatePlayButton(isEnded = false) {
        if (!this.playButton) return;

        const icon = this.playButton.querySelector("i");
        if (!icon) return;

        if (isEnded) {
            icon.className = "bi bi-arrow-clockwise";
            this.playButton.setAttribute("aria-label", "Recommencer");
        } else if (this.isPlaying) {
            icon.className = "bi bi-pause-fill";
            this.playButton.setAttribute("aria-label", "Pause");
        } else {
            icon.className = "bi bi-play-fill";
            this.playButton.setAttribute("aria-label", "Lecture");
        }

        // Update play overlay icon
        if (this.playOverlay) {
            const overlayIcon = this.playOverlay.querySelector('i');
            if (overlayIcon && !isEnded) {
                overlayIcon.className = this.isPlaying ? "bi bi-pause-fill" : "bi bi-play-fill";
            }
        }
    }

    // ========================================
    // Utilitaires
    // ========================================

    formatTime(milliseconds) {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds.toString().padStart(2, "0")}`;
    }

    cleanup() {
        this.pause();

        // Masquer le tooltip actif
        if (this.tooltip) {
            this.tooltip.classList.remove('show');
            this.tooltip.innerHTML = '';
        }
    }
}

// ========================================
// Auto-initialisation
// ========================================

export function initVideoDemo() {
    const videoDemoModal = document.getElementById('videoDemoModal');
    if (!videoDemoModal) return;

    console.log('[video-demo] Initialisation du lecteur vidéo démo...');

    // Initialiser le player au premier affichage du modal
    videoDemoModal.addEventListener('shown.bs.modal', function initPlayer() {
        const playerContainer = videoDemoModal.querySelector('.modal-content[data-controller="video-demo"]');
        if (playerContainer && !playerContainer._videoDemoPlayer) {
            playerContainer._videoDemoPlayer = new VideoDemoPlayer(playerContainer);
            console.log('[video-demo] Lecteur vidéo démo initialisé ✓');
        }
    }, { once: false });
}

// Auto-init si DOM déjà chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVideoDemo);
} else {
    initVideoDemo();
}
