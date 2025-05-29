class AIAssistant {
    constructor() {
        this.chatHistory = [];
        this.isTyping = false;
        this.chatOpen = false;
        this.commonQuestions = [
            "¿Cómo puedo solicitar una cita médica?",
            "¿Qué debo hacer antes de mi consulta?",
            "¿Cuáles son los síntomas de la gripe?",
            "¿Cuándo debo acudir a urgencias?",
            "¿Cómo puedo ver mi historial médico?"
        ];
        this.init();
    }
    
    init() {
        this.createChatElements();
        this.addEventListeners();
        this.addAIMessage("¡Hola! Soy tu asistente médico virtual de Salutia. ¿En qué puedo ayudarte hoy?");
        this.showSuggestions();
    }
    
    createChatElements() {
        const chatToggleBtn = document.createElement('div');
        chatToggleBtn.className = 'chat-toggle-btn';
        chatToggleBtn.innerHTML = '<i class="fas fa-comment-medical"></i>';
        chatToggleBtn.id = 'chatToggleBtn';
        document.body.appendChild(chatToggleBtn);
        
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chat-window';
        chatWindow.id = 'chatWindow';
        
        chatWindow.innerHTML = `
            <div class="chat-container">
                <div class="chat-header">
                    <h5><i class="fas fa-robot me-2"></i> Asistente Médico Virtual</h5>
                    <button class="btn btn-sm text-white" id="closeChatBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-body" id="chatBody">
                </div>
                <div class="chat-footer">
                    <div class="chat-input-container">
                        <input type="text" class="chat-input" id="chatInput" 
                               placeholder="Escribe tu mensaje aquí...">
                        <button class="chat-send-btn" id="chatSendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="ai-suggestions" id="aiSuggestions">
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(chatWindow);
    }
    
    addEventListeners() {
        document.getElementById('chatToggleBtn').addEventListener('click', () => {
            this.toggleChat();
        });
        
        document.getElementById('closeChatBtn').addEventListener('click', () => {
            this.toggleChat(false);
        });
        
        document.getElementById('chatSendBtn').addEventListener('click', () => {
            this.sendMessage();
        });
        
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }
    
    toggleChat(forceState) {
        const chatWindow = document.getElementById('chatWindow');
        
        if (forceState !== undefined) {
            this.chatOpen = forceState;
        } else {
            this.chatOpen = !this.chatOpen;
        }
        
        if (this.chatOpen) {
            chatWindow.classList.add('active');
            document.getElementById('chatInput').focus();
        } else {
            chatWindow.classList.remove('active');
        }
    }
    
    sendMessage() {
        const chatInput = document.getElementById('chatInput');
        const message = chatInput.value.trim();
        
        if (message) {
            this.addUserMessage(message);
            chatInput.value = '';
            this.showTypingIndicator();
            this.processMessage(message);
        }
    }
    
    addUserMessage(message) {
        const chatBody = document.getElementById('chatBody');
        const time = this.getCurrentTime();
        
        const messageElement = document.createElement('div');
        messageElement.className = 'message message-user';
        messageElement.innerHTML = `
            ${message}
            <div class="message-time">${time}</div>
        `;
        
        chatBody.appendChild(messageElement);
        this.scrollToBottom();
        
        this.chatHistory.push({
            role: 'user',
            content: message,
            time: time
        });
    }
    
    addAIMessage(message) {
        const chatBody = document.getElementById('chatBody');
        const time = this.getCurrentTime();
        
        this.removeTypingIndicator();
        
        const messageElement = document.createElement('div');
        messageElement.className = 'message message-ai';
        messageElement.innerHTML = `
            ${message}
            <div class="message-time">${time}</div>
        `;
        
        chatBody.appendChild(messageElement);
        this.scrollToBottom();
        
        this.chatHistory.push({
            role: 'assistant',
            content: message,
            time: time
        });
    }
    
    showTypingIndicator() {
        if (this.isTyping) return;
        
        this.isTyping = true;
        const chatBody = document.getElementById('chatBody');
        
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator';
        typingIndicator.id = 'typingIndicator';
        typingIndicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        
        chatBody.appendChild(typingIndicator);
        this.scrollToBottom();
    }
    
    removeTypingIndicator() {
        this.isTyping = false;
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    scrollToBottom() {
        const chatBody = document.getElementById('chatBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    }
    
    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    showSuggestions() {
        const suggestionsContainer = document.getElementById('aiSuggestions');
        suggestionsContainer.innerHTML = '';
        
        // Mostrar 3 sugerencias aleatorias
        const randomSuggestions = this.getRandomSuggestions(3);
        
        randomSuggestions.forEach(suggestion => {
            const suggestionChip = document.createElement('div');
            suggestionChip.className = 'ai-suggestion-chip';
            suggestionChip.textContent = suggestion;
            suggestionChip.addEventListener('click', () => {
                document.getElementById('chatInput').value = suggestion;
                this.sendMessage();
            });
            
            suggestionsContainer.appendChild(suggestionChip);
        });
    }
    
    getRandomSuggestions(count) {
        const shuffled = [...this.commonQuestions].sort(() => 0.5 - Math.random());
        return shuffled.slice(0, count);
    }
    
    processMessage(message) {
        const requestData = {
            message: message,
            history: this.chatHistory.slice(-5)
        };
        
        fetch('../backend/api/chat_gpt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.addAIMessage(data.message);
            } else {
                this.addAIMessage('Lo siento, estoy teniendo problemas para procesar tu solicitud. Por favor, intenta de nuevo más tarde.');
            }
            
            this.showSuggestions();
        })
        .catch(error => {
            console.error('Error al procesar el mensaje:', error);
            const response = this.generateResponse(message);
            this.addAIMessage(response);
            this.showSuggestions();
        });
    }
    
    generateResponse(message) {
        const lowerMessage = message.toLowerCase();
        if (lowerMessage.includes('cita') || lowerMessage.includes('reservar') || lowerMessage.includes('agendar')) {
            return 'Para solicitar una cita, puedes usar la sección "Buscar Médico" en el dashboard, seleccionar la especialidad deseada, elegir un médico disponible y seleccionar una fecha y hora que te convenga. ¿Necesitas ayuda con alguna especialidad en particular?';
        }
        
        if (lowerMessage.includes('urgencia') || lowerMessage.includes('emergencia')) {
            return 'Si estás experimentando una emergencia médica, debes llamar inmediatamente al 112 o acudir al servicio de urgencias más cercano. No esperes a una cita programada para situaciones que requieren atención inmediata.';
        }
        
        if (lowerMessage.includes('síntoma') || lowerMessage.includes('dolor') || lowerMessage.includes('enfermo')) {
            return 'Aunque puedo proporcionarte información general, es importante que consultes con un médico para un diagnóstico adecuado. Puedes describir tus síntomas durante la cita médica. ¿Te gustaría que te ayude a programar una cita con un especialista?';
        }
        
        if (lowerMessage.includes('historial') || lowerMessage.includes('expediente') || lowerMessage.includes('resultados')) {
            return 'Puedes acceder a tu historial médico desde la sección "Mi Historial" en el menú principal. Allí encontrarás tus consultas anteriores, recetas y resultados de pruebas. Si no puedes acceder a alguna información, contacta con soporte técnico.';
        }
        
        if (lowerMessage.includes('gracias') || lowerMessage.includes('muchas gracias')) {
            return '¡De nada! Estoy aquí para ayudarte. ¿Hay algo más en lo que pueda asistirte?';
        }
        
        if (lowerMessage.includes('hola') || lowerMessage.includes('buenos días') || lowerMessage.includes('buenas tardes')) {
            return '¡Hola! ¿En qué puedo ayudarte hoy con respecto a tu salud o al uso de Salutia?';
        }
        
        return 'Gracias por tu mensaje. Para brindarte la mejor atención, te recomiendo consultar directamente con uno de nuestros médicos. Puedes programar una cita fácilmente desde la sección "Buscar Médico". ¿Hay algo específico en lo que pueda orientarte mientras tanto?';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.aiAssistant = new AIAssistant();
});
