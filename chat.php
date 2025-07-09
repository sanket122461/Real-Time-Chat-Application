<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
redirect_if_not_logged_in();

$rooms = get_chat_rooms();
$current_room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 1;
$messages = get_room_messages($current_room_id);
$active_users = get_active_users_in_room($current_room_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="chat-container">
        <div class="sidebar">
            <div class="user-info">
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                <a href="logout.php" class="btn logout">Logout</a>
            </div>
            
            <div class="rooms">
                <h3>Chat Rooms</h3>
                <ul>
                    <?php foreach ($rooms as $room): ?>
                        <li class="<?php echo $room['id'] == $current_room_id ? 'active' : ''; ?>">
                            <a href="chat.php?room_id=<?php echo $room['id']; ?>">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="active-users">
                <h3>Active Users</h3>
                <ul>
                    <?php foreach ($active_users as $user): ?>
                        <li><?php echo htmlspecialchars($user['username']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="chat-area">
            <div class="messages" id="messages">
                <?php foreach ($messages as $message): ?>
                    <div class="message">
                        <span class="username"><?php echo htmlspecialchars($message['username']); ?></span>
                        <span class="timestamp"><?php echo date('H:i', strtotime($message['timestamp'])); ?></span>
                        <div class="message-text"><?php echo htmlspecialchars($message['message_text']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="message-input">
                <input type="text" id="message-text" placeholder="Type your message here...">
                <button id="send-message" class="btn">Send</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chat.js"></script>
</body>
</html>