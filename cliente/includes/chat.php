<style>
    /* Chat Button */
    .chat-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(230, 0, 126, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        color: white;
        font-size: 1.8rem;
    }
    
    .chat-button.chat-hidden,
    .chat-modal.chat-hidden {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    
    .chat-button:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 35px rgba(230, 0, 126, 0.6);
        background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
    }
    
    /* Chat Modal */
    .chat-modal {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .chat-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    
    /* Chat Header */
    .chat-header {
        background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .chat-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .chat-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .chat-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    /* Online Status Indicator */
    .online-status {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        opacity: 1 !important;
        margin-top: 4px;
    }
    
    .online-indicator {
        width: 8px;
        height: 8px;
        background: #00ff88;
        border-radius: 50%;
    }
    
    .online-status span {
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        animation: none !important;
        transition: none !important;
        opacity: 1 !important;
    }
    
    /* Chat Messages */
    .chat-messages {
        height: 320px;
        overflow-y: auto;
        padding: 20px;
        background: #f8f9fa;
    }
    
    .chat-message {
        background: white;
        padding: 12px 16px;
        border-radius: 15px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        position: relative;
    }
    
    .chat-message.bot {
        margin-right: 40px;
        color: #2d3748;
    }
    
    .chat-message.bot:nth-child(odd) {
        background: linear-gradient(135deg, #e0f2fe, #e1f5fe);
    }
    
    .chat-message.bot:nth-child(even) {
        background: linear-gradient(135deg, #f3e5f5, #fce4ec);
    }
    
    .chat-message.bot:nth-child(3n) {
        background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
    }
    
    .chat-message.user {
        background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
        color: white;
        margin-left: 40px;
        text-align: right;
    }
    
    .chat-message-time {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 5px;
    }
    
    /* Chat Input */
    .chat-input-container {
        padding: 15px 20px;
        background: white;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        display: flex;
        gap: 10px;
    }
    
    .chat-input {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 25px;
        font-size: 0.9rem;
        background: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .chat-input:focus {
        outline: none;
        border-color: var(--color-magenta);
        background: white;
    }
    
    .chat-send {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .chat-send:hover {
        transform: scale(1.05);
        background: linear-gradient(135deg, var(--color-magenta-dark), #a0005a);
    }
    
    /* Typing Indicator */
    .typing-indicator {
        display: none;
        padding: 12px 16px;
        background: white;
        border-radius: 15px;
        margin-bottom: 12px;
        margin-right: 40px;
    }
    
    .typing-dots {
        display: flex;
        gap: 4px;
    }
    
    .typing-dot {
        width: 8px;
        height: 8px;
        background: #666;
        border-radius: 50%;
        animation: typingAnimation 1.5s infinite;
    }
    
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    
    @keyframes typingAnimation {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
        30% { transform: translateY(-10px); opacity: 1; }
    }
    
    /* Responsividade do Chat */
    @media (max-width: 768px) {
        .chat-modal {
            width: calc(100vw - 20px);
            right: 10px;
            left: 10px;
            bottom: 100px;
            height: 450px;
        }
        
        .chat-button {
            bottom: 20px;
            right: 20px;
            width: 55px;
            height: 55px;
        }
    }
    
    .chat-button::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
    }
    
    /* Chat Tooltip */
    .chat-tooltip {
        position: absolute;
        left: -155px;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        padding: 12px 16px;
        border-radius: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        font-size: 0.9rem;
        font-weight: 600;
        color: #2d3748;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        white-space: nowrap;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .chat-tooltip::after {
        content: '';
        position: absolute;
        right: -8px;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-left-color: white;
    }
    
    .chat-button:hover .chat-tooltip {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    // ===== CHAT SYSTEM INTERNO =====
    function createChatButton() {
        const chatBtn = document.createElement('button');
        chatBtn.className = 'chat-button';
        chatBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12c0 1.821.487 3.53 1.338 5L2.5 21.5l4.5-.838A9.955 9.955 0 0 0 12 22Z"/>
                <path d="M8 12h.01M12 12h.01M16 12h.01" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div class="chat-tooltip">Fale conosco!</div>
        `;
        
        chatBtn.addEventListener('click', function() {
            toggleChatModal();
        });
        
        document.body.appendChild(chatBtn);
        createChatModal();
    }
    
    function createChatModal() {
        const chatModal = document.createElement('div');
        chatModal.className = 'chat-modal';
        chatModal.id = 'chatModal';
        
        chatModal.innerHTML = `
            <!-- Header -->
            <div class="chat-header">
                <div>
                    <h3>RARE7 Atendimento</h3>
                    <div class="online-status"><div class="online-indicator"></div><span>Online agora</span></div>
                </div>
                <button class="chat-close" onclick="toggleChatModal()">×</button>
            </div>
            
            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="chat-message bot">
                    <div>Olá! 😊 Seja bem-vinda à RARE7! Como posso te ajudar hoje?</div>
                    <div class="chat-message-time">${getCurrentTime()}</div>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
            
            <!-- Input -->
            <div class="chat-input-container">
                <input type="text" class="chat-input" id="chatInput" placeholder="Digite sua mensagem..." maxlength="500">
                <button class="chat-send" onclick="sendMessage()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="m2 21 21-9L2 3v7l15 2-15 2v7z"/>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(chatModal);
        
        // Enter para enviar mensagem
        const chatInput = chatModal.querySelector('#chatInput');
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    function toggleChatModal() {
        const modal = document.getElementById('chatModal');
        modal.classList.toggle('active');
        
        if (modal.classList.contains('active')) {
            // Focar no input
            setTimeout(() => {
                const input = document.getElementById('chatInput');
                input.focus();
            }, 300);
        }
    }
    
    function getCurrentTime() {
        const now = new Date();
        return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
    }
    
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (message === '') return;
        
        // Adicionar mensagem do usuário
        addMessage(message, 'user');
        input.value = '';
        
        // Simular resposta do bot
        setTimeout(() => {
            showTyping();
            setTimeout(() => {
                hideTyping();
                respondToMessage(message);
            }, Math.random() * 2000 + 1000); // 1-3 segundos
        }, 500);
    }
    
    function addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${sender}`;
        
        messageDiv.innerHTML = `
            <div>${text}</div>
            <div class="chat-message-time">${getCurrentTime()}</div>
        `;
        
        messagesContainer.insertBefore(messageDiv, document.getElementById('typingIndicator'));
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function showTyping() {
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.style.display = 'block';
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function hideTyping() {
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.style.display = 'none';
    }
    
    function respondToMessage(userMessage) {
        const responses = getResponseForMessage(userMessage.toLowerCase());
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addMessage(randomResponse, 'bot');
    }
    
    function getResponseForMessage(message) {
        // Respostas baseadas em palavras-chave
        if (message.includes('preço') || message.includes('valor') || message.includes('quanto custa')) {
            return [
                'Nossas camisas têm preços a partir de R$ 99,90! 😊 Você busca clube, seleção ou modelo retrô?',
                'Temos opções para todos os orçamentos, com modelos premium e versões torcedor. 💰'
            ];
        }
        
        if (message.includes('entrega') || message.includes('frete') || message.includes('envio')) {
            return [
                'Entrega grátis para compras acima de R$ 299! 🚚 Entregamos em todo o Brasil em até 5 dias úteis.',
                'Para pedidos abaixo do frete grátis, o valor varia conforme o CEP e a transportadora. 🚚'
            ];
        }
        
        if (message.includes('time') || message.includes('clube') || message.includes('camisa')) {
            return [
                'Temos camisas de clubes nacionais e internacionais, além de versões retrô incríveis! ⚽',
                'Se quiser, te ajudo a encontrar camisa por time, temporada ou faixa de preço. 👕'
            ];
        }
        
        if (message.includes('seleção') || message.includes('selecao')) {
            return [
                'Temos camisas de seleções clássicas e atuais para você vestir sua paixão em dias de jogo! 🇧🇷',
                'Posso te mostrar opções de seleções por tamanho e disponibilidade em estoque. 🔎'
            ];
        }
        
        if (message.includes('desconto') || message.includes('promoção') || message.includes('cupom')) {
            return [
                'Sempre temos campanhas especiais em dias de jogo e lançamentos da temporada! 🎉',
                'Posso te avisar das promoções ativas e cupons disponíveis no momento. 😎'
            ];
        }
        
        if (message.includes('whatsapp') || message.includes('telefone') || message.includes('contato')) {
            return [
                'Nosso WhatsApp é (11) 99999-9999! Mas aqui no chat também consigo te ajudar perfeitamente! 😊',
                'Para contato direto: contato@rare7.com.br ou (11) 99999-9999. Como posso te ajudar agora? 💬'
            ];
        }
        
        // Respostas padrão
        return [
            'Que interessante! Posso te ajudar com informações sobre camisas de clubes e seleções. O que você procura? 😊',
            'Claro! Estou aqui para esclarecer suas dúvidas sobre tamanhos, modelos e envio. ✨',
            'Entendi! Quer que eu te indique os modelos mais procurados no momento? ⚽',
            'Perfeito! Posso te ajudar com produtos, entrega e disponibilidade em estoque. 🚀'
        ];
    }
    
    // Monitorar mini-cart e esconder chat quando aberto
    function monitorMiniCart() {
        const miniCart = document.getElementById('miniCartDrawer');
        const miniCartOverlay = document.getElementById('miniCartOverlay');
        
        if (!miniCart) return;
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    const chatBtn = document.querySelector('.chat-button');
                    const chatModal = document.getElementById('chatModal');
                    
                    if (miniCart.classList.contains('active')) {
                        // Mini-cart aberto, esconder chat
                        if (chatBtn) chatBtn.classList.add('chat-hidden');
                        if (chatModal) {
                            chatModal.classList.add('chat-hidden');
                            chatModal.classList.remove('active');
                        }
                    } else {
                        // Mini-cart fechado, mostrar chat
                        if (chatBtn) chatBtn.classList.remove('chat-hidden');
                        if (chatModal) chatModal.classList.remove('chat-hidden');
                    }
                }
            });
        });
        
        observer.observe(miniCart, { attributes: true });
    }
    
    // Inicializar chat quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            createChatButton();
            setTimeout(monitorMiniCart, 500);
        });
    } else {
        createChatButton();
        setTimeout(monitorMiniCart, 500);
    }
</script>
