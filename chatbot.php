<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Trợ lý AI Kho Đàn - Piano Nguyễn Duy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --accent: #0ea5e9;
            --accent-hover: #0284c7;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --radius-lg: 16px;
            --radius-full: 9999px;
            --shadow-md: 0 4px 20px rgba(0,0,0,0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            height: 100vh;
            height: 100dvh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .chat-app-container {
            width: 100%;
            max-width: 520px;
            height: 100%;
            height: 100dvh;
            background: var(--bg-card);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-md);
            position: relative;
        }

        @media (min-width: 576px) {
            .chat-app-container {
                height: 92vh;
                max-height: 820px;
                border-radius: var(--radius-lg);
                border: 1px solid var(--border);
            }
        }

        .chat-header {
            padding: 14px 18px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            z-index: 10;
        }

        .btn-back {
            background: var(--bg-body);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
            cursor: pointer;
        }
        .btn-back:hover {
            color: var(--accent);
            background: rgba(14, 165, 233, 0.1);
        }

        .bot-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .bot-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.35);
        }

        .bot-info h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bot-status {
            font-size: 11.5px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 1px;
            font-weight: 500;
        }
        .status-pulse {
            width: 7px;
            height: 7px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        #chatbox {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8fafc;
        }

        .msg-wrap {
            display: flex;
            flex-direction: column;
            max-width: 84%;
            animation: fadeInMsg 0.25s ease forwards;
        }
        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .msg-wrap.user { align-self: flex-end; }
        .msg-wrap.ai { align-self: flex-start; }

        .msg {
            padding: 11px 15px;
            border-radius: 16px;
            font-size: 14.5px;
            line-height: 1.5;
            word-break: break-word;
        }

        .msg-wrap.user .msg {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.25);
        }

        .msg-wrap.ai .msg {
            background: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .msg-time {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 4px;
            padding: 0 4px;
        }
        .msg-wrap.user .msg-time { text-align: right; }
        .msg-wrap.ai .msg-time { text-align: left; }

        .suggestions-bar {
            padding: 8px 14px 4px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: #ffffff;
            border-top: 1px solid var(--border);
        }
        .suggestions-bar::-webkit-scrollbar { display: none; }
        .chip {
            flex-shrink: 0;
            background: var(--bg-body);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        .chip:hover {
            background: rgba(14, 165, 233, 0.1);
            color: var(--accent);
            border-color: rgba(14, 165, 233, 0.3);
        }

        .input-area {
            padding: 10px 14px 14px;
            background: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .input-box-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }

        #userInput {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 24px;
            font-size: 16px;
            font-family: inherit;
            color: var(--text-primary);
            outline: none;
            background: #f8fafc;
            transition: border-color 0.2s, background 0.2s;
        }
        #userInput:focus {
            border-color: var(--accent);
            background: #ffffff;
        }

        .btn-send {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);
        }
        .btn-send:hover {
            transform: scale(1.05);
            background: #0284c7;
        }

        .typing-dots {
            display: inline-flex;
            gap: 4px;
            align-items: center;
            padding: 4px 0;
        }
        .typing-dots span {
            width: 6px;
            height: 6px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }
        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    
button:disabled { opacity: .55; cursor: wait; }
#chatbox { min-height: 0; }
</style>
</head>
<body>

<div class="chat-app-container">
    <div class="chat-header">
        <a href="index.php" class="btn-back" title="Quay lại">
            <span class="material-symbols-rounded">arrow_back</span>
        </a>
        <div class="bot-profile">
            <div class="bot-avatar">
                <span class="material-symbols-rounded">smart_toy</span>
            </div>
            <div class="bot-info">
                <h3>Trợ lý AI Kho Đàn</h3>
                <div class="bot-status">
                    <span class="status-pulse"></span> Sẵn sàng tư vấn trực tuyến
                </div>
            </div>
        </div>
        <a href="index.php" class="btn-back" title="Về trang chủ">
            <span class="material-symbols-rounded">home</span>
        </a>
    </div>

    <div id="chatbox">
        <div class="msg-wrap ai">
            <div class="msg">
                Xin chào! Tôi là Trợ lý AI của Kho Đàn Piano Nguyễn Duy. 🎹<br>
                Bạn muốn tìm hiểu mẫu đàn nào, kiểm tra giá bán hay tra cứu tồn kho hiện tại?
            </div>
            <div class="msg-time">Vừa xong</div>
        </div>
    </div>

    <div class="suggestions-bar">
        <button type="button" type="button" class="chip" onclick="quickSend('Trong kho có những mẫu đàn nào?')">📦 Mẫu đàn trong kho</button>
        <button type="button" type="button" class="chip" onclick="quickSend('Có những mẫu Grand Piano nào?')">🎹 Grand Piano</button>
        <button type="button" type="button" class="chip" onclick="quickSend('Tư vấn các cây đàn giá dưới 50 triệu')">💰 Đàn dưới 50 triệu</button>
        <button type="button" type="button" class="chip" onclick="quickSend('Chính sách bảo hành đàn piano thế nào?')">🛡️ Bảo hành</button>
    </div>

    <div class="input-area">
        <div class="input-box-wrapper">
            <input type="text" id="userInput" placeholder="Hỏi AI về mẫu đàn, giá, tồn kho..." onkeypress="handleKeyPress(event)" autocomplete="off">
        </div>
        <button type="button" class="btn-send" onclick="sendMessage()" title="Gửi tin nhắn">
            <span class="material-symbols-rounded">send</span>
        </button>
    </div>
</div>

<script>
(() => {
    const ui = {input:'userInput',body:'chatbox',send:'sendBtn',wrap:'msg-wrap',user:'user',bot:'ai',content:'msg'};
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
            const response = await fetch('ai_assistant.php', {
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
    window.sendMessage = send; window.quickSend = quick; window.handleKeyPress = key;
})();
</script>

</body>
</html>