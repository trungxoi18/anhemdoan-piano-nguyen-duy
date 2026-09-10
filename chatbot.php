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

        /* HEADER */
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

        /* CHAT BODY */
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

        .msg-wrap.user {
            align-self: flex-end;
        }
        .msg-wrap.ai {
            align-self: flex-start;
        }

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

        /* SUGGESTIONS */
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

        /* INPUT AREA */
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
            font-size: 16px; /* 16px prevents iOS Safari auto-zoom */
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
        .btn-send:active {
            transform: scale(0.95);
        }

        /* Typing dots */
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
    </style>
</head>
<body>

<div class="chat-app-container">
    <!-- Header -->
    <div class="chat-header">
        <a href="index.php" class="btn-back" title="Quay lại Dashboard">
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

    <!-- Khung tin nhắn -->
    <div id="chatbox">
        <div class="msg-wrap ai">
            <div class="msg">
                Xin chào! Tôi là Trợ lý AI của Kho Đàn Piano Nguyễn Duy. 🎹<br>
                Bạn muốn tìm hiểu mẫu đàn nào, kiểm tra giá bán hay tra cứu tồn kho hiện tại?
            </div>
            <div class="msg-time">Vừa xong</div>
        </div>
    </div>

    <!-- Gợi ý câu hỏi nhanh -->
    <div class="suggestions-bar">
        <button type="button" class="chip" onclick="quickSend('Có những mẫu Grand Piano nào trong kho?')">🎹 Grand Piano</button>
        <button type="button" class="chip" onclick="quickSend('Tư vấn các cây đàn giá dưới 50 triệu')">💰 Đàn dưới 50 triệu</button>
        <button type="button" class="chip" onclick="quickSend('Đàn Kawai K-300 giá bao nhiêu và còn hàng không?')">🏷️ Kawai K-300</button>
        <button type="button" class="chip" onclick="quickSend('Chính sách bảo hành đàn piano thế nào?')">🛡️ Bảo hành</button>
    </div>

    <!-- Thanh nhập tin nhắn -->
    <div class="input-area">
        <div class="input-box-wrapper">
            <input type="text" id="userInput" placeholder="Hỏi AI về mẫu đàn, giá, tồn kho..." onkeypress="handleKeyPress(event)" autocomplete="off">
        </div>
        <button class="btn-send" onclick="sendMessage()" title="Gửi tin nhắn">
            <span class="material-symbols-rounded">send</span>
        </button>
    </div>
</div>

<script>
    function handleKeyPress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    }

    function quickSend(text) {
        const input = document.getElementById('userInput');
        input.value = text;
        sendMessage();
    }

    function getTimeString() {
        const d = new Date();
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function sendMessage() {
        const input = document.getElementById('userInput');
        const message = input.value.trim();
        const chatbox = document.getElementById('chatbox');
        if (!message) return;

        const timeStr = getTimeString();

        // Thêm tin nhắn của người dùng
        const userHtml = `
            <div class="msg-wrap user">
                <div class="msg">${escapeHtml(message)}</div>
                <div class="msg-time">${timeStr}</div>
            </div>
        `;
        chatbox.insertAdjacentHTML('beforeend', userHtml);
        input.value = '';
        chatbox.scrollTop = chatbox.scrollHeight;

        // Trạng thái chờ gõ
        const loadingId = 'loading-' + Date.now();
        const loadingHtml = `
            <div class="msg-wrap ai" id="${loadingId}">
                <div class="msg">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>
        `;
        chatbox.insertAdjacentHTML('beforeend', loadingHtml);
        chatbox.scrollTop = chatbox.scrollHeight;

        // Gọi backend xử lý chat bằng FormData
        const formData = new FormData();
        formData.append('message', message);

        fetch('chat_process.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch(e) {
                return { reply: text || 'Lỗi phản hồi máy chủ.' };
            }
        })
        .then(data => {
            const loadingElem = document.getElementById(loadingId);
            if (loadingElem) loadingElem.remove();

            let replyText = data.reply || 'Xin lỗi, tôi chưa có câu trả lời cho câu hỏi này.';
            let cleanReply = replyText.replace(/\*\*/g, '').replace(/\n/g, '<br>');

            const aiHtml = `
                <div class="msg-wrap ai">
                    <div class="msg">${cleanReply}</div>
                    <div class="msg-time">${getTimeString()}</div>
                </div>
            `;
            chatbox.insertAdjacentHTML('beforeend', aiHtml);
            chatbox.scrollTop = chatbox.scrollHeight;
        })
        .catch(error => {
            const loadingElem = document.getElementById(loadingId);
            if (loadingElem) loadingElem.remove();

            const errHtml = `
                <div class="msg-wrap ai">
                    <div class="msg" style="color: #ef4444;">Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại đường truyền mạng.</div>
                    <div class="msg-time">${getTimeString()}</div>
                </div>
            `;
            chatbox.insertAdjacentHTML('beforeend', errHtml);
            chatbox.scrollTop = chatbox.scrollHeight;
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>