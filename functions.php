<?php
require_once __DIR__ . '/../config/database.php';

function get_chat_rooms() {
    global $conn;
    $rooms = [];
    $sql = "SELECT * FROM chat_rooms";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    return $rooms;
}

function get_room_messages($room_id, $limit = 50) {
    global $conn;
    $messages = [];
    $sql = "SELECT m.*, u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? 
            ORDER BY m.timestamp DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $room_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    return array_reverse($messages);
}

function get_active_users_in_room($room_id) {
    global $conn;
    // This is a simplified version - in a real app, you'd track active connections
    $users = [];
    $sql = "SELECT DISTINCT u.id, u.username 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? 
            AND m.timestamp > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}
?>