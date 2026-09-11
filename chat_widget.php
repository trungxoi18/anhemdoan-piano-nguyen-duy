<!-- CHATBOT WIDGET FOR PIANO STORE -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<button type="button" id="ai-chat-toggle" onclick="toggleAIChat()" title="Tư vấn AI">
    <i class="fa-solid fa-headset"></i>
</button>

<div id="ai-chat-box" class="ai-chat-hidden">
    <div class="ai-chat-header">
        <div class="ai-chat-avatar">
            <i class="fa-solid fa-robot"></i>
        </div>
        <div class="ai-chat-title">
            <h4>Trợ Lý AI Kho Đàn</h4>
            <span class="ai-status"><span class="status-dot"></span> Sẵn sàng tư vấn</span>
        </div>
        <button type="button" class="ai-chat-close" onclick="toggleAIChat()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="ai-chat-body" id="ai-chat-body">
        <div class="ai-msg ai-msg-bot">
            <div class="msg-content">
                Xin chào! Tôi là Trợ lý AI của cửa hàng. Bạn cần tư vấn mẫu đàn piano hay nhạc cụ nào trong kho hôm nay? 🎹
            </div>
        </div>
        
        <div class="ai-suggestions" id="ai-suggestions">
            <div class="suggestion-tag" onclick="sendQuickMessage('Trong kho có những mẫu đàn nào?')">📦 Mẫu đàn trong kho</div>
            <div class="suggestion-tag" onclick="sendQuickMessage('Có những mẫu Grand Piano nào?')">🎹 Grand Piano</div>
            <div class="suggestion-tag" onclick="sendQuickMessage('Tư vấn đàn dưới 50 triệu')">💰 Đàn dưới 50 triệu</div>
        </div>
    </div>

    <div class="ai-chat-footer">
        <input type="text" id="ai-chat-input" placeholder="Nhập câu hỏi về đàn piano..." onkeypress="handleKeyPress(event)">
        <button type="button" id="ai-chat-send" onclick="sendAIMessage()">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
#ai-chat-toggle {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border: none;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(30, 60, 114, 0.4);
    z-index: 9999;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
#ai-chat-toggle:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 12px 30px rgba(30, 60, 114, 0.6);
}

#ai-chat-box {
    position: fixed;
    bottom: 95px;
    right: 25px;
    width: 360px;
    height: 520px;
    max-width: calc(100vw - 40px);
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9999;
    transition: all 0.3s ease;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}
#ai-chat-box.ai-chat-hidden {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    pointer-events: none;
}

.ai-chat-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.ai-chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.ai-chat-title h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}
.ai-status {
    font-size: 12px;
    opacity: 0.85;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}
.status-dot {
    width: 8px;
    height: 8px;
    background-color: #2ecc71;
    border-radius: 50%;
    display: inline-block;
}
.ai-chat-close {
    margin-left: auto;
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.8;
    transition: 0.2s;
}
.ai-chat-close:hover { opacity: 1; transform: scale(1.1); }

.ai-chat-body {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ai-chat-body::-webkit-scrollbar { width: 5px; }
.ai-chat-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }

.ai-msg { display: flex; flex-direction: column; max-width: 82%; }
.ai-msg-bot { align-self: flex-start; }
.ai-msg-user { align-self: flex-end; }

.msg-content {
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
}
.ai-msg-bot .msg-content {
    background: white;
    color: #333;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.ai-msg-user .msg-content {
    background: #2a5298;
    color: white;
    border-bottom-right-radius: 4px;
}

.ai-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
}
.suggestion-tag {
    background: #eef2f5;
    color: #1e3c72;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #dce4ec;
    transition: all 0.2s;
}
.suggestion-tag:hover {
    background: #1e3c72;
    color: white;
    border-color: #1e3c72;
}

.typing-dots {
    display: flex;
    gap: 4px;
    align-items: center;
    padding: 4px 0;
}
.typing-dots span {
    width: 6px;
    height: 6px;
    background: #999;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.ai-chat-footer {
    padding: 12px 16px;
    background: white;
    border-top: 1px solid #eee;
    display: flex;
    gap: 8px;
    align-items: center;
}
#ai-chat-input {
    flex: 1;
    border: 1px solid #e0e0e0;
    padding: 10px 14px;
    border-radius: 25px;
    outline: none;
    font-size: 14px;
    transition: 0.2s;
}
#ai-chat-input:focus { border-color: #2a5298; }
#ai-chat-send {
    background: #2a5298;
    color: white;
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}
#ai-chat-send:hover { background: #1e3c72; transform: scale(1.05); }

@media (max-width: 576px) {
    #ai-chat-toggle {
        bottom: 16px;
        right: 16px;
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    #ai-chat-box {
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh; height: 100dvh;
        max-width: 100vw; max-height: 100vh;
        border-radius: 0;
    }
    #ai-chat-input { font-size: 16px; }
}

button:disabled { opacity: .55; cursor: wait; }
#ai-chat-body { min-height: 0; }
</style>

<script>
(() => {
    const ui = {input:'ai-chat-input',body:'ai-chat-body',send:'ai-chat-send',wrap:'ai-msg',user:'ai-msg-user',bot:'ai-msg-bot',content:'msg-content'};
    const input = document.getElementById(ui.input);
    const body = document.getElementById(ui.body);
    const sendButton = document.getElementById(ui.send) || document.querySelector('.btn-send');
    let busy = false;
    // Một ID riêng cho mỗi lần mở trang: nội dung nhìn thấy khớp ngữ cảnh máy chủ.
    const random = new Uint32Array(4);
    crypto.getRandomValues(random);
    const conversationId = Array.from(random, n => n.toString(16)).join('-');
    input.maxLength = 3000;
    body.setAttribute('aria-live', 'polite');
    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    }
    function append(text, user = false) {
        const row = document.createElement('div');
        row.className = ui.wrap + ' ' + (user ? ui.user : ui.bot);
        const bubble = document.createElement('div');
        bubble.className = ui.content;
        bubble.innerHTML = escapeHTML(text).replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
        row.appendChild(bubble);
        body.appendChild(row);
        body.scrollTop = body.scrollHeight;
        return row;
    }
    async function send() {
        const message = input.value.trim();
        if (busy || !message) return;
        busy = true;
        if (sendButton) sendButton.disabled = true;
        const suggestions = document.getElementById('ai-suggestions');
        if (suggestions) suggestions.style.display = 'none';
        append(message, true);
        input.value = '';
        const loading = append('Đang đọc câu hỏi và dữ liệu kho…');
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 75000);
        try {
            const form = new FormData();
            form.append('noidung_chat', message);
            form.append('conversation_id', conversationId);
            const response = await fetch('api_chat.php', {
                method: 'POST', credentials: 'same-origin', body: form, signal: controller.signal
            });
            let data;
            try { data = await response.json(); }
            catch (_) { throw new Error('Máy chủ trả dữ liệu không hợp lệ. Hãy kiểm tra api_chat.php và nhật ký lỗi PHP.'); }
            if (!response.ok || data.ok === false) throw new Error(data.reply || 'Dịch vụ AI đang bận.');
            if (typeof data.reply !== 'string' || !data.reply.trim()) throw new Error('AI trả về nội dung trống. Hãy thử lại.');
            append(data.reply);
        } catch (error) {
            append(error.name === 'AbortError' ? 'AI phản hồi quá lâu. Vui lòng thử lại sau.' : error.message);
            if (!input.value) input.value = message;
        } finally {
            clearTimeout(timeout);
            loading.remove();
            busy = false;
            if (sendButton) sendButton.disabled = false;
            body.scrollTop = body.scrollHeight;
            input.focus();
        }
    }
    function quick(text) {
        if (busy) return;
        input.value = text;
        send();
    }
    function key(event) {
        if (event.key === 'Enter' && !event.isComposing) { event.preventDefault(); send(); }
    }
    window.sendAIMessage = send; window.sendQuickMessage = quick; window.handleKeyPress = key; window.toggleAIChat = () => { const box = document.getElementById('ai-chat-box'); box.classList.toggle('ai-chat-hidden'); if (!box.classList.contains('ai-chat-hidden')) input.focus(); };
})();
</script>