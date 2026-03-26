<style>
    /* Chat Button */
    .chat-button {
        position: fixed;
        bottom: 26px;
        right: 26px;
        width: 54px;
        height: 54px;
        background: linear-gradient(145deg, #d4b56a 0%, #c6a75e 52%, #b79246 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 9999;
        box-shadow:
            0 12px 28px rgba(0, 0, 0, 0.38),
            0 0 0 1px rgba(255, 255, 255, 0.22);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: #111;
        font-size: 1.15rem;
    }
    
    .chat-button.chat-hidden,
    .chat-modal.chat-hidden {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    
    .chat-button:hover {
        transform: translateY(-2px) scale(1.03);
        box-shadow:
            0 14px 30px rgba(0, 0, 0, 0.45),
            0 0 0 1px rgba(255, 255, 255, 0.3);
        background: linear-gradient(145deg, #e1c581 0%, #d0af62 48%, #bd9748 100%);
    }
    
    /* Chat Modal */
    .chat-modal {
        position: fixed;
        bottom: 88px;
        right: 26px;
        width: min(330px, calc(100vw - 32px));
        height: min(500px, calc(100vh - 120px));
        background:
            radial-gradient(circle at 18% -10%, rgba(198, 167, 94, 0.2), rgba(198, 167, 94, 0) 35%),
            linear-gradient(180deg, rgba(8, 12, 20, 0.98) 0%, rgba(7, 14, 28, 0.99) 100%);
        border-radius: 16px;
        box-shadow: 0 24px 55px rgba(0, 0, 0, 0.45);
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.14);
        display: flex;
        flex-direction: column;
        padding-bottom: 10px;
    }
    
    .chat-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }
    
    /* Chat Header */
    .chat-header {
        background: rgba(8, 13, 24, 0.86);
        color: #f7f9fb;
        padding: 13px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(198, 167, 94, 0.35);
    }
    
    .chat-header h3 {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    
    .chat-close {
        background: none;
        border: none;
        color: rgba(245, 248, 252, 0.92);
        font-size: 1.3rem;
        cursor: pointer;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .chat-close:hover {
        background: rgba(255, 255, 255, 0.08);
    }
    
    /* Online Status Indicator */
    .online-status {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 0.78rem;
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
        color: rgba(224, 231, 240, 0.82);
        font-weight: 500;
        animation: none !important;
        transition: none !important;
        opacity: 1 !important;
    }
    
    /* Chat Messages */
    .chat-messages {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 14px 14px 10px;
        background: linear-gradient(180deg, rgba(6, 11, 20, 0.72), rgba(8, 14, 24, 0.8));
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .chat-message {
        background: rgba(255, 255, 255, 0.08);
        padding: 10px 12px;
        border-radius: 13px;
        margin-bottom: 0;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.1);
        width: fit-content;
        max-width: 80%;
    }
    
    .chat-message.bot {
        margin-right: auto;
        color: rgba(235, 241, 248, 0.96);
    }
    
    .chat-message.bot:nth-child(odd) {
        background: linear-gradient(135deg, rgba(18, 35, 58, 0.94), rgba(24, 45, 74, 0.9));
    }
    
    .chat-message.bot:nth-child(even) {
        background: linear-gradient(135deg, rgba(16, 31, 52, 0.94), rgba(22, 40, 66, 0.9));
    }
    
    .chat-message.bot:nth-child(3n) {
        background: linear-gradient(135deg, rgba(15, 29, 48, 0.94), rgba(20, 38, 62, 0.9));
    }
    
    .chat-message.user {
        background: linear-gradient(135deg, rgba(216, 185, 112, 0.98), rgba(198, 167, 94, 0.92));
        color: #171717;
        margin-left: auto;
        text-align: left;
    }
    
    .chat-message-time {
        font-size: 0.68rem;
        opacity: 0.7;
        margin-top: 4px;
    }
    
    /* Chat Input */
    .chat-input-container {
        margin: 0 12px 12px;
        padding: 8px;
        background: rgba(7, 12, 20, 0.86);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        display: flex;
        gap: 8px;
    }
    
    .chat-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid rgba(198, 167, 94, 0.42);
        border-radius: 25px;
        font-size: 0.88rem;
        background: rgba(255, 255, 255, 0.08);
        color: #f4f6f8;
        transition: all 0.2s ease;
    }

    .chat-input::placeholder {
        color: rgba(230, 236, 242, 0.6);
    }
    
    .chat-input:focus {
        outline: none;
        border-color: rgba(216, 185, 112, 0.8);
        background: rgba(255, 255, 255, 0.12);
    }
    
    .chat-send {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, rgba(216, 185, 112, 0.98), rgba(198, 167, 94, 0.95));
        border: none;
        border-radius: 50%;
        color: #171717;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .chat-send:hover {
        transform: scale(1.05);
        background: linear-gradient(135deg, rgba(234, 205, 135, 0.99), rgba(214, 181, 106, 0.96));
    }
    
    /* Typing Indicator */
    .typing-indicator {
        display: none;
        padding: 9px 12px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 15px;
        margin-bottom: 0;
        margin-right: auto;
        border: 1px solid rgba(255, 255, 255, 0.1);
        width: fit-content;
    }
    
    .typing-dots {
        display: flex;
        gap: 4px;
    }
    
    .typing-dot {
        width: 8px;
        height: 8px;
        background: rgba(236, 240, 246, 0.8);
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
            width: calc(100vw - 18px);
            right: 9px;
            left: 9px;
            bottom: 72px;
            height: min(440px, calc(100vh - 90px));
            padding-bottom: 9px;
        }
        
        .chat-button {
            bottom: 16px;
            right: 16px;
            width: 48px;
            height: 48px;
        }

        .chat-input-container {
            margin: 0 10px 10px;
        }
    }
    
    .chat-button::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at 30% 28%, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0) 62%);
        border-radius: 50%;
        pointer-events: none;
    }
    
    /* Chat Tooltip */
    .chat-tooltip {
        display: none;
    }
</style>

<script>
    // ===== CHAT SYSTEM INTERNO =====
    function createChatButton() {
        if (document.querySelector('.chat-button')) {
            return;
        }

        const chatBtn = document.createElement('button');
        chatBtn.className = 'chat-button';
        chatBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
                <path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/>
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
        if (document.getElementById('chatModal')) {
            return;
        }

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
                    <div>Olá! 😊 Boas-vindas à RARE7! Como posso te ajudar hoje?</div>
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
