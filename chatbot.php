<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chatbot AI - Quản lý kho Piano</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; }
        .chat-container { max-width: 450px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        #chatbox { height: 350px; border: 1px solid #ddd; overflow-y: auto; padding: 15px; margin-bottom: 15px; border-radius: 5px; background: #fafafa; }
        .msg { margin-bottom: 12px; padding: 10px; border-radius: 8px; line-height: 1.4; }
        .user-msg { background: #007bff; color: white; text-align: right; margin-left: 20%; }
        .ai-msg { background: #e9ecef; color: black; margin-right: 20%; }
        .input-area { display: flex; gap: 10px; }
        input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>

<div class="chat-container">
    <h3 style="text-align: center; margin-top: 0;">Trợ lý AI Kho Đàn Piano</h3>
    <div id="chatbox"></div>
    <div class="input-area">
        <input type="text" id="userInput" placeholder="Hỏi AI về kho đàn..." onkeypress="handleKeyPress(event)">
        <button onclick="sendMessage()">Gửi</button>
    </div>
</div>

<script>
    function handleKeyPress(e) {
        if (e.key === 'Enter') sendMessage();
    }

    function sendMessage() {
        const input = document.getElementById('userInput');
        const message = input.value.trim();
        const chatbox = document.getElementById('chatbox');
        if (!message) return;

        // In tin nhắn user
        chatbox.innerHTML += `<div class="msg user-msg">${message}</div>`;
        input.value = '';
        
        // Hiển thị trạng thái chờ
        const loadingId = 'loading-' + Date.now();
        chatbox.innerHTML += `<div class="msg ai-msg" id="${loadingId}"><i>Đang gõ...</i></div>`;
        chatbox.scrollTop = chatbox.scrollHeight;

        // Gọi API
        fetch('api_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById(loadingId).remove();
            // In tin nhắn AI (thay thế dấu * cho bớt rối nếu AI dùng Markdown)
            let cleanReply = data.reply.replace(/\*\*/g, ''); 
            chatbox.innerHTML += `<div class="msg ai-msg">${cleanReply}</div>`;
            chatbox.scrollTop = chatbox.scrollHeight;
        })
        .catch(error => {
            document.getElementById(loadingId).remove();
            chatbox.innerHTML += `<div class="msg ai-msg" style="color:red;">Lỗi kết nối Server.</div>`;
        });
    }
</script>

</body>
</html>