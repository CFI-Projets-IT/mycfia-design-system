import { Controller } from '@hotwired/stimulus';

/**
 * Chat Controller - Gestion de l'interface de chat IA
 * Envoi de messages, auto-resize textarea, gestion du scroll
 */
export default class extends Controller {
    static targets = ['input', 'messages', 'sendButton'];

    connect() {
        console.log('Chat controller connected');
        this.scrollToBottom();
    }

    /**
     * Gère la soumission du formulaire
     */
    sendMessage(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();

        if (!message) {
            return;
        }

        // Ajouter le message utilisateur
        this.addMessage(message, 'user');

        // Réinitialiser le champ
        this.inputTarget.value = '';
        this.autoResize();

        // Simuler une réponse de l'IA (à remplacer par LiveComponent plus tard)
        this.simulateAIResponse();
    }

    /**
     * Ajoute un message au chat
     */
    addMessage(content, type = 'user') {
        const messagesContainer = this.messagesTarget;
        const timestamp = new Date();

        const messageHTML = `
            <div class="chat-message chat-message-${type}" data-message-type="${type}">
                <div class="chat-message-avatar">
                    ${type === 'user' ? '<i class="bi bi-person-circle"></i>' : '<i class="bi bi-robot"></i>'}
                </div>

                <div class="chat-message-content">
                    <div class="chat-message-header">
                        <span class="chat-message-author">
                            ${type === 'user' ? 'Vous' : 'Assistant IA'}
                        </span>
                        <span class="chat-message-time">
                            ${timestamp.getHours().toString().padStart(2, '0')}:${timestamp.getMinutes().toString().padStart(2, '0')}
                        </span>
                    </div>

                    <div class="chat-message-text">
                        ${this.formatMessage(content)}
                    </div>

                    ${type === 'assistant' ? `
                        <div class="chat-message-actions">
                            <button type="button" class="btn-message-action" title="Copier" data-action="click->chat#copyMessage">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    /**
     * Formate le message (remplace \n par <br>)
     */
    formatMessage(text) {
        return text.replace(/\n/g, '<br>');
    }

    /**
     * Simule une réponse de l'IA (placeholder pour LiveComponent)
     */
    simulateAIResponse() {
        // Désactiver le bouton d'envoi pendant la réponse
        if (this.hasSendButtonTarget) {
            this.sendButtonTarget.disabled = true;
        }

        setTimeout(() => {
            const responses = [
                "Je suis un assistant IA placeholder. L'intégration complète avec Symfony AI Bundle sera réalisée dans les sprints S0-S11.",
                "Cette interface sera connectée à l'API CFI et au Symfony AI Bundle lors des prochaines étapes.",
                "L'interface chat est prête ! L'intégration avec LiveComponent et l'IA conversationnelle sera implémentée dans les sprints suivants."
            ];

            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            this.addMessage(randomResponse, 'assistant');

            // Réactiver le bouton d'envoi
            if (this.hasSendButtonTarget) {
                this.sendButtonTarget.disabled = false;
            }

            this.inputTarget.focus();
        }, 1000);
    }

    /**
     * Gère les touches clavier (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)
     */
    handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage(event);
        }
    }

    /**
     * Auto-resize du textarea
     */
    autoResize() {
        const textarea = this.inputTarget;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
    }

    /**
     * Scroll vers le bas des messages
     */
    scrollToBottom() {
        if (this.hasMessagesTarget) {
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
        }
    }

    /**
     * Copie le contenu d'un message dans le presse-papier
     */
    copyMessage(event) {
        const messageElement = event.target.closest('.chat-message');
        const textElement = messageElement.querySelector('.chat-message-text');
        const text = textElement.innerText;

        navigator.clipboard.writeText(text).then(() => {
            // Afficher un feedback visuel
            const icon = event.target.closest('button').querySelector('i');
            icon.classList.remove('bi-clipboard');
            icon.classList.add('bi-check2');

            setTimeout(() => {
                icon.classList.remove('bi-check2');
                icon.classList.add('bi-clipboard');
            }, 2000);
        });
    }
}
