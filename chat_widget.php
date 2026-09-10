<!-- CHATBOT WIDGET FOR PIANO STORE -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Nút mở Chatbot -->
<button id="ai-chat-toggle" onclick="toggleAIChat()" title="Tư vấn AI">
    <i class="fa-solid fa-headset"></i>
</button>

<!-- Khung Popup Chatbot -->
<div id="ai-chat-box" class="ai-chat-hidden">
    <!-- Header -->
    <div class="ai-chat-header">
        <div class="ai-chat-avatar">
            <i class="fa-solid fa-robot"></i>
        </div>
        <div class="ai-chat-title">
            <h4>Trợ Lý AI Kho Đàn</h4>
            <span class="ai-status"><span class="status-dot"></span> Sẵn sàng tư vấn</span>
        </div>
        <button class="ai-chat-close" onclick="toggleAIChat()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Nội dung trò chuyện -->
    <div class="ai-chat-body" id="ai-chat-body">
        <div class="ai-msg ai-msg-bot">
            <div class="msg-content">
                Xin chào! Tôi là Trợ lý AI của cửa hàng. Bạn cần tư vấn mẫu đàn piano hay nhạc cụ nào trong kho hôm nay? 🎹
            </div>
        </div>
        
        <!-- Các câu hỏi gợi ý nhanh -->
        <div class="ai-suggestions" id="ai-suggestions">
            <div class="suggestion-tag" onclick="sendQuickMessage('Có những mẫu Grand Piano nào?')">🎹 Grand Piano</div>
            <div class="suggestion-tag" onclick="sendQuickMessage('Tư vấn đàn dưới 50 triệu')">💰 Đàn dưới 50 triệu</div>
            <div class="suggestion-tag" onclick="sendQuickMessage('Giá đàn Kawai K-300 bao nhiêu?')">🏷️ Kawai K-300</div>
        </div>
    </div>

    <!-- Khung nhập tin nhắn -->
    <div class="ai-chat-footer">
        <input type="text" id="ai-chat-input" placeholder="Nhập câu hỏi về đàn piano..." onkeypress="handleKeyPress(event)">
        <button id="ai-chat-send" onclick="sendAIMessage()">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
/* 1. NÚT MỞ POPUP */
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

/* 2. KHUNG CHAT POPUP */
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

/* HEADER */
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

/* BODY */
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

/* BONG BÓNG TIN NHẮN */
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

/* CHIPS GỢI Ý */
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

/* TYPING INDICATOR (ANIMATION) */
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

/* FOOTER (INPUT) */
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

/* RESPONSIVE CHO ĐIỆN THOẠI DI ĐỘNG */
@media (max-width: 576px) {
    #ai-chat-toggle {
        bottom: 16px;
        right: 16px;
        width: 50px;
        height: 50px;
        font-size: 20px;
        box-shadow: 0 4px 16px rgba(30, 60, 114, 0.4);
    }
    #ai-chat-box {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        max-width: 100vw;
        max-height: 100vh;
        max-height: 100dvh;
        border-radius: 0;
        z-index: 10000;
    }
    .ai-chat-header {
        padding: 14px 16px;
    }
    .ai-chat-body {
        padding: 12px;
    }
    .ai-chat-footer {
        padding: 10px 12px;
    }
    #ai-chat-input {
        font-size: 16px; /* Ngăn tự zoom trên iOS */
    }
}
</style>

<script>
// Mở/Đóng Popup Chat
function toggleAIChat() {
    const box = document.getElementById('ai-chat-box');
    box.classList.toggle('ai-chat-hidden');
    if (!box.classList.contains('ai-chat-hidden')) {
        document.getElementById('ai-chat-input').focus();
    }
}

// Bắt sự kiện phím Enter
function handleKeyPress(e) {
    if (e.key === 'Enter') sendAIMessage();
}

// Gửi câu hỏi nhanh từ Chips
function sendQuickMessage(text) {
    document.getElementById('ai-chat-input').value = text;
    sendAIMessage();
}

// Hàm gửi tin nhắn chính
function sendAIMessage() {
    const input = document.getElementById('ai-chat-input');
    const message = input.value.trim();
    if (!message) return;

    const chatBody = document.getElementById('ai-chat-body');

    // Ẩn thanh gợi ý sau lần gửi đầu tiên
    const suggestions = document.getElementById('ai-suggestions');
    if (suggestions) suggestions.style.display = 'none';

    // 1. Hiển thị tin nhắn người dùng
    const userMsgHTML = `<div class="ai-msg ai-msg-user"><div class="msg-content">${escapeHTML(message)}</div></div>`;
    chatBody.insertAdjacentHTML('beforeend', userMsgHTML);
    input.value = '';
    scrollToBottom();

    // 2. Hiển thị trạng thái "AI đang gõ..."
    const loadingId = 'loading-' + Date.now();
    const loadingHTML = `
        <div class="ai-msg ai-msg-bot" id="${loadingId}">
            <div class="msg-content">
                <div class="typing-dots"><span></span><span></span><span></span></div>
            </div>
        </div>`;
    chatBody.insertAdjacentHTML('beforeend', loadingHTML);
    scrollToBottom();

    // 3. Gọi API PHP backend
    fetch('api_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Non-JSON API response:", text);
            return { reply: "Phản hồi máy chủ: " + (text.length > 100 ? text.substring(0, 100) + '...' : text) };
        }
    })
    .then(data => {
        // Xóa bong bóng loading
        const loadingElem = document.getElementById(loadingId);
        if (loadingElem) loadingElem.remove();

        // Hiển thị câu trả lời từ AI
        const replyText = data.reply || "Hệ thống bận, vui lòng thử lại sau.";
        const botMsgHTML = `<div class="ai-msg ai-msg-bot"><div class="msg-content">${formatReply(replyText)}</div></div>`;
        chatBody.insertAdjacentHTML('beforeend', botMsgHTML);
        scrollToBottom();
    })
    .catch(err => {
        const loadingElem = document.getElementById(loadingId);
        if (loadingElem) loadingElem.remove();

        const errorHTML = `<div class="ai-msg ai-msg-bot"><div class="msg-content">Lỗi kết nối mạng hoặc máy chủ.</div></div>`;
        chatBody.insertAdjacentHTML('beforeend', errorHTML);
        scrollToBottom();
    });
}

// Tự động cuộn xuống cuối
function scrollToBottom() {
    const chatBody = document.getElementById('ai-chat-body');
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Lọc ký tự HTML tránh lỗi XSS
function escapeHTML(str) {
    return str.replace(/[&<>'"]/g, 
        tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
    );
}

// Định dạng xuống dòng trong tin nhắn AI
function formatReply(str) {
    return escapeHTML(str).replace(/\n/g, '<br>');
}
</script>