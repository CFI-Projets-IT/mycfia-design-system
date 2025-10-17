/**
 * Chat IA - Interface utilisateur
 *
 * Responsabilités :
 * - Gestion de l'interface chat (messages, scroll, UX)
 * - Envoi des questions à l'API
 * - Affichage des réponses de l'agent IA
 * - Auto-resize du textarea
 * - Quick questions (suggestions)
 * - Export de conversation (future)
 *
 * Architecture : Vanilla JavaScript (ES6+)
 * Pas de framework pour optimiser les performances
 */

// ====================================
// 1. État de l'application
// ====================================

const state = {
    conversationId: null,
    messageUrl: null,
    streamUrl: null,
    mercureUrl: null,
    mercureJwt: null,
    isLoading: false,
    messageHistory: [],
};

// ====================================
// 2. Éléments DOM
// ====================================

let elements = {};

// ====================================
// 3. Initialisation
// ====================================

function initializeChatInterface() {
    console.log('[Chat] Initialisation...');

    // Récupérer les éléments DOM
    elements = {
        chatForm: document.getElementById('chatForm'),
        chatInput: document.getElementById('chatInput'),
        chatMessages: document.getElementById('chatMessages'),
        sendButton: document.getElementById('sendButton'),
        charCount: document.getElementById('charCount'),
        chatLoading: document.getElementById('chatLoading'),
        clearChat: document.getElementById('clearChat'),
        exportChat: document.getElementById('exportChat'),
        quickQuestions: document.querySelectorAll('.quick-question'),
    };

    // Vérifier si on est sur une page avec le chat
    if (!elements.chatForm) {
        console.log('[Chat] Interface chat non trouvée, skip initialisation');
        return;
    }

    // Éviter les initialisations multiples en vérifiant une propriété sur l'élément
    if (elements.chatForm.dataset.chatInitialized === 'true') {
        console.log('[Chat] Déjà initialisé, skip');
        return;
    }

    // Récupérer les données depuis le template
    const chatData = document.getElementById('chatData');
    if (chatData) {
        state.conversationId = chatData.dataset.conversationId;
        state.messageUrl = chatData.dataset.messageUrl;
        state.streamUrl = chatData.dataset.streamUrl;
        state.mercureUrl = chatData.dataset.mercureUrl;
        state.mercureJwt = chatData.dataset.mercureJwt;

        console.log('[Chat] Configuration chargée:', {
            conversationId: state.conversationId,
            messageUrl: state.messageUrl,
            streamUrl: state.streamUrl,
            mercureUrl: state.mercureUrl,
            mercureJwt: state.mercureJwt ? 'présent' : 'absent',
        });
    }

    // Initialiser les event listeners
    initEventListeners();

    // Auto-resize du textarea
    setupTextareaAutoResize();

    // Marquer comme initialisé
    elements.chatForm.dataset.chatInitialized = 'true';
    console.log('[Chat] Initialisation terminée');
}

// Écouter les événements de chargement (DOMContentLoaded + Turbo)
document.addEventListener('DOMContentLoaded', initializeChatInterface);
document.addEventListener('turbo:load', initializeChatInterface);
document.addEventListener('turbo:render', initializeChatInterface);
document.addEventListener('turbo:frame-load', initializeChatInterface);

// ====================================
// 4. Event Listeners
// ====================================

function initEventListeners() {
    // Soumission du formulaire
    elements.chatForm?.addEventListener('submit', handleFormSubmit);

    // Compteur de caractères
    elements.chatInput?.addEventListener('input', handleInputChange);

    // Quick questions
    elements.quickQuestions?.forEach(btn => {
        btn.addEventListener('click', () => {
            const question = btn.dataset.question;
            if (question) {
                elements.chatInput.value = question;
                handleInputChange();
                elements.chatInput.focus();
            }
        });
    });

    // Clear chat
    elements.clearChat?.addEventListener('click', handleClearChat);

    // Export chat
    elements.exportChat?.addEventListener('click', handleExportChat);

    // Touche Enter pour envoyer (Shift+Enter pour nouvelle ligne)
    elements.chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!elements.sendButton.disabled) {
                elements.chatForm.dispatchEvent(new Event('submit'));
            }
        }
    });
}

// ====================================
// 5. Gestion du formulaire
// ====================================

async function handleFormSubmit(e) {
    e.preventDefault();

    const question = elements.chatInput.value.trim();
    if (!question || state.isLoading) {
        return;
    }

    console.log('[Chat] Envoi de la question:', question);

    // Afficher le message utilisateur
    addUserMessage(question);

    // Vider le champ
    elements.chatInput.value = '';
    handleInputChange();

    // Désactiver l'envoi pendant le traitement
    setLoading(true);

    // Utiliser le streaming si Mercure est configuré
    if (state.mercureUrl && state.streamUrl) {
        await handleStreamingSubmit(question);
    } else {
        // Fallback vers mode synchrone si Mercure non disponible
        await handleSyncSubmit(question);
    }
}

/**
 * Envoyer une question en mode streaming avec Mercure SSE
 */
async function handleStreamingSubmit(question) {
    try {
        // 1. Démarrer le streaming via l'API
        const response = await fetch(state.streamUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Envoyer les cookies de session pour l'authentification
            body: JSON.stringify({
                question,
                conversationId: state.conversationId,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        console.log('[Chat] Streaming started:', data);

        // 2. Se connecter au flux Mercure SSE
        const mercureUrl = new URL(state.mercureUrl);
        mercureUrl.searchParams.append('topic', `chat/${state.conversationId}`);

        // Ajouter le JWT d'autorisation Mercure
        if (state.mercureJwt) {
            mercureUrl.searchParams.append('authorization', state.mercureJwt);
        }

        const finalUrl = mercureUrl.toString();
        console.log('[Chat] EventSource URL:', finalUrl);
        console.log('[Chat] Creating EventSource...');

        const eventSource = new EventSource(finalUrl);

        console.log('[Chat] EventSource created, readyState:', eventSource.readyState);
        console.log('[Chat] EventSource.CONNECTING:', EventSource.CONNECTING);
        console.log('[Chat] EventSource.OPEN:', EventSource.OPEN);
        console.log('[Chat] EventSource.CLOSED:', EventSource.CLOSED);

        let currentMessageElement = null;
        let fullAnswer = '';
        let metadata = {};
        let toolsUsed = [];

        eventSource.onopen = () => {
            console.log('[Chat] EventSource OPENED! readyState:', eventSource.readyState);
        };

        eventSource.onmessage = (event) => {
            console.log('[Chat] EventSource onmessage fired!');
            const eventData = JSON.parse(event.data);
            console.log('[Chat] Mercure event:', eventData);

            switch (eventData.type) {
                case 'start':
                    // Créer l'élément message qui sera rempli progressivement
                    currentMessageElement = createStreamingMessageElement();
                    elements.chatMessages.appendChild(currentMessageElement);
                    scrollToBottom();
                    break;

                case 'chunk':
                    // Ajouter le chunk au message
                    fullAnswer += eventData.chunk;
                    if (currentMessageElement) {
                        updateStreamingMessage(currentMessageElement, fullAnswer);
                        scrollToBottom();
                    }
                    break;

                case 'complete':
                    // Finaliser le message avec les métadonnées
                    metadata = eventData.metadata || {};
                    toolsUsed = metadata.tools_used || [];

                    if (currentMessageElement) {
                        finalizeStreamingMessage(currentMessageElement, fullAnswer, metadata, toolsUsed);
                    }

                    // Sauvegarder dans l'historique
                    state.messageHistory.push({
                        question,
                        answer: fullAnswer,
                        metadata,
                        toolsUsed,
                        timestamp: new Date().toISOString(),
                    });

                    console.log('[Chat] Streaming complete');

                    // Fermer la connexion Mercure
                    eventSource.close();
                    setLoading(false);
                    break;

                case 'error':
                    console.error('[Chat] Streaming error:', eventData.error);
                    addErrorMessage(eventData.error);
                    eventSource.close();
                    setLoading(false);
                    break;
            }
        };

        eventSource.onerror = (error) => {
            console.error('[Chat] EventSource ERROR!');
            console.error('[Chat] error object:', error);
            console.error('[Chat] readyState:', eventSource.readyState);
            console.error('[Chat] url:', eventSource.url);
            addErrorMessage('Erreur de connexion au streaming');
            eventSource.close();
            setLoading(false);
        };

    } catch (error) {
        console.error('[Chat] Streaming submit error:', error);
        addErrorMessage(`Erreur : ${error.message}`);
        setLoading(false);
    }
}

/**
 * Envoyer une question en mode synchrone (fallback)
 */
async function handleSyncSubmit(question) {
    try {
        // Envoyer la question à l'API
        const response = await fetch(state.messageUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Envoyer les cookies de session pour l'authentification
            body: JSON.stringify({ question }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        // Afficher la réponse de l'assistant
        addAssistantMessage(data.answer, data.metadata, data.toolsUsed);

        // Sauvegarder dans l'historique
        state.messageHistory.push({
            question,
            answer: data.answer,
            metadata: data.metadata,
            toolsUsed: data.toolsUsed,
            timestamp: new Date().toISOString(),
        });

        console.log('[Chat] Réponse reçue:', data);

    } catch (error) {
        console.error('[Chat] Erreur:', error);
        addErrorMessage(`Erreur : ${error.message}`);
    } finally {
        setLoading(false);
    }
}

// ====================================
// 6. Gestion des messages (UI)
// ====================================

function addUserMessage(text) {
    const messageHtml = `
        <div class="chat-message user-message mb-4" data-message-type="user">
            <div class="d-flex gap-3 flex-row-reverse">
                <div class="message-avatar flex-shrink-0">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="message-content flex-grow-1">
                    <div class="message-header mb-2 text-end">
                        <span class="fw-semibold">Vous</span>
                        <span class="small text-muted ms-2">${getCurrentTime()}</span>
                    </div>
                    <div class="message-text">
                        ${escapeHtml(text)}
                    </div>
                </div>
            </div>
        </div>
    `;

    elements.chatMessages.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
}

function addAssistantMessage(text, metadata = {}, toolsUsed = []) {
    const toolsHtml = toolsUsed.length > 0 ? `
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="bi bi-tools"></i>
                <strong>Outils utilisés :</strong> ${toolsUsed.join(', ')}
            </small>
        </div>
    ` : '';

    const messageHtml = `
        <div class="chat-message assistant-message mb-4" data-message-type="assistant">
            <div class="d-flex gap-3">
                <div class="message-avatar flex-shrink-0">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="message-content flex-grow-1">
                    <div class="message-header mb-2">
                        <span class="fw-semibold text-primary">Assistant IA</span>
                        <span class="small text-muted ms-2">${getCurrentTime()}</span>
                    </div>
                    <div class="message-text">
                        ${formatMessage(text)}
                        ${toolsHtml}
                    </div>
                </div>
            </div>
        </div>
    `;

    elements.chatMessages.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
}

function addErrorMessage(text) {
    const messageHtml = `
        <div class="chat-message assistant-message mb-4" data-message-type="error">
            <div class="d-flex gap-3">
                <div class="message-avatar flex-shrink-0 bg-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="message-content flex-grow-1">
                    <div class="message-header mb-2">
                        <span class="fw-semibold text-danger">Erreur</span>
                        <span class="small text-muted ms-2">${getCurrentTime()}</span>
                    </div>
                    <div class="message-text border-danger">
                        <p class="text-danger mb-0">${escapeHtml(text)}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    elements.chatMessages.insertAdjacentHTML('beforeend', messageHtml);
    scrollToBottom();
}

/**
 * Créer un élément de message vide pour le streaming progressif
 */
function createStreamingMessageElement() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message assistant-message mb-4';
    messageDiv.dataset.messageType = 'assistant-streaming';
    messageDiv.innerHTML = `
        <div class="d-flex gap-3">
            <div class="message-avatar flex-shrink-0">
                <i class="bi bi-robot"></i>
            </div>
            <div class="message-content flex-grow-1">
                <div class="message-header mb-2">
                    <span class="fw-semibold text-primary">Assistant IA</span>
                    <span class="small text-muted ms-2">${getCurrentTime()}</span>
                    <span class="badge bg-info ms-2">
                        <i class="bi bi-three-dots"></i> En cours...
                    </span>
                </div>
                <div class="message-text" data-streaming-content>
                    <span class="text-muted"><i class="bi bi-hourglass-split"></i> Réflexion en cours...</span>
                </div>
            </div>
        </div>
    `;
    return messageDiv;
}

/**
 * Mettre à jour le contenu d'un message en cours de streaming
 */
function updateStreamingMessage(messageElement, text) {
    const contentDiv = messageElement.querySelector('[data-streaming-content]');
    if (contentDiv) {
        contentDiv.innerHTML = formatMessage(text) + '<span class="streaming-cursor">|</span>';
    }
}

/**
 * Finaliser un message streamé avec les métadonnées
 */
function finalizeStreamingMessage(messageElement, text, metadata = {}, toolsUsed = []) {
    const contentDiv = messageElement.querySelector('[data-streaming-content]');
    const badgeDiv = messageElement.querySelector('.badge.bg-info');

    if (badgeDiv) {
        badgeDiv.remove();
    }

    const toolsHtml = toolsUsed.length > 0 ? `
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="bi bi-tools"></i>
                <strong>Outils utilisés :</strong> ${toolsUsed.join(', ')}
            </small>
        </div>
    ` : '';

    if (contentDiv) {
        contentDiv.innerHTML = formatMessage(text) + toolsHtml;
    }

    messageElement.dataset.messageType = 'assistant';
}

// ====================================
// 7. Utilitaires UI
// ====================================

function setLoading(loading) {
    state.isLoading = loading;
    elements.sendButton.disabled = loading || !elements.chatInput.value.trim();
    elements.chatInput.disabled = loading;

    if (loading) {
        elements.chatLoading?.classList.remove('d-none');
    } else {
        elements.chatLoading?.classList.add('d-none');
    }
}

function handleInputChange() {
    const value = elements.chatInput.value;
    const length = value.length;
    const maxLength = 500;

    // Mettre à jour le compteur
    elements.charCount.textContent = `${length} / ${maxLength}`;

    // Activer/désactiver le bouton d'envoi
    elements.sendButton.disabled = length === 0 || length > maxLength || state.isLoading;

    // Limiter la longueur
    if (length > maxLength) {
        elements.charCount.classList.add('text-danger');
    } else {
        elements.charCount.classList.remove('text-danger');
    }
}

function setupTextareaAutoResize() {
    const textarea = elements.chatInput;
    if (!textarea) return;

    textarea.addEventListener('input', () => {
        // Reset height pour recalculer
        textarea.style.height = 'auto';

        // Calculer la nouvelle hauteur
        const newHeight = Math.min(textarea.scrollHeight, 200);
        textarea.style.height = newHeight + 'px';
    });
}

function scrollToBottom() {
    elements.chatMessages.scrollTo({
        top: elements.chatMessages.scrollHeight,
        behavior: 'smooth',
    });
}

function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

// ====================================
// 8. Formatage des messages
// ====================================

function formatMessage(text) {
    // Convertir les retours à la ligne
    let formatted = escapeHtml(text).replace(/\n/g, '<br>');

    // Convertir les liens en balises <a>
    formatted = formatted.replace(
        /(https?:\/\/[^\s<]+)/g,
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
    );

    // Convertir les blocs de code ```code```
    formatted = formatted.replace(
        /```([^`]+)```/g,
        '<pre class="bg-dark text-white p-2 rounded"><code>$1</code></pre>'
    );

    // Convertir le code inline `code`
    formatted = formatted.replace(
        /`([^`]+)`/g,
        '<code>$1</code>'
    );

    // Convertir les listes
    formatted = formatted.replace(
        /^- (.+)$/gm,
        '<li>$1</li>'
    );

    if (formatted.includes('<li>')) {
        formatted = '<ul>' + formatted.replace(/(<li>.*<\/li>)/g, '$1') + '</ul>';
    }

    return formatted;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ====================================
// 9. Actions Chat (Clear, Export)
// ====================================

function handleClearChat() {
    if (confirm('Voulez-vous vraiment effacer cette conversation ?')) {
        // Supprimer tous les messages sauf le message de bienvenue
        const messages = elements.chatMessages.querySelectorAll('.chat-message');
        messages.forEach((msg, index) => {
            if (index > 0) { // Garder le message de bienvenue (index 0)
                msg.remove();
            }
        });

        // Réinitialiser l'historique
        state.messageHistory = [];

        console.log('[Chat] Conversation effacée');
    }
}

function handleExportChat() {
    if (state.messageHistory.length === 0) {
        alert('Aucune conversation à exporter');
        return;
    }

    // Créer un blob avec l'historique
    const exportData = {
        conversationId: state.conversationId,
        exportDate: new Date().toISOString(),
        messages: state.messageHistory,
    };

    const blob = new Blob([JSON.stringify(exportData, null, 2)], {
        type: 'application/json',
    });

    // Créer un lien de téléchargement
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `conversation-${state.conversationId}-${Date.now()}.json`;
    a.click();

    URL.revokeObjectURL(url);

    console.log('[Chat] Conversation exportée');
}

// ====================================
// 10. Logs de débogage (Development)
// ====================================

console.log('[Chat] Module chargé');
