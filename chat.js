document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messages');
    const messageInput = document.getElementById('message-text');
    const sendButton = document.getElementById('send-message');
    
    // Get current room ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get('room_id') || 1;
    const username = document.querySelector('.user-info h3').textContent;
    
    // Connect to WebSocket server
    const socket = new WebSocket('ws://localhost:8080');
    
    socket.onopen = function(e) {
        console.log('WebSocket connection established');
        
        // Send join message to server
        socket.send(JSON.stringify({
            type: 'join',
            username: username,
            room_id: parseInt(roomId)
        }));
    };
    
    socket.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.type === 'message') {
            // Add new message to the chat
            addMessage(data.username, data.message, data.timestamp);
        } else if (data.type === 'user_joined') {
            // Notify that a user has joined
            addSystemMessage(`${data.username} has joined the chat`);
        } else if (data.type === 'user_left') {
            // Notify that a user has left
            addSystemMessage(`${data.username} has left the chat`);
        } else if (data.type === 'user_list') {
            // Update active users list (simplified)
            console.log('Active users:', data.users);
        }
    };
    
    socket.onclose = function(event) {
        if (event.wasClean) {
            console.log(`Connection closed cleanly, code=${event.code}, reason=${event.reason}`);
        } else {
            console.log('Connection died');
        }
    };
    
    socket.onerror = function(error) {
        console.log(`WebSocket error: ${error.message}`);
    };
    
    // Send message when button is clicked
    sendButton.addEventListener('click', sendMessage);
    
    // Send message when Enter key is pressed
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    function sendMessage() {
        const messageText = messageInput.value.trim();
        if (messageText) {
            // Send message to server
            socket.send(JSON.stringify({
                type: 'message',
                room_id: parseInt(roomId),
                user_id: <?php echo $_SESSION['user_id']; ?>,
                username: username,
                message: messageText
            }));
            
            // Clear input
            messageInput.value = '';
        }
    }
    
    function addMessage(username, message, timestamp) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message';
        
        messageElement.innerHTML = `
            <span class="username">${username}</span>
            <span class="timestamp">${timestamp}</span>
            <div class="message-text">${escapeHtml(message)}</div>
        `;
        
        messagesContainer.appendChild(messageElement);
        scrollToBottom();
    }
    
    function addSystemMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message system';
        messageElement.textContent = message;
        messagesContainer.appendChild(messageElement);
        scrollToBottom();
    }
    
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Basic XSS protection
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});