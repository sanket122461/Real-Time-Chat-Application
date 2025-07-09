<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $activeUsers;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->activeUsers = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        global $conn;
        
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'join') {
            $this->activeUsers[$from->resourceId] = [
                'username' => $data['username'],
                'room_id' => $data['room_id']
            ];
            
            // Notify others in the room about the new user
            foreach ($this->clients as $client) {
                if ($client !== $from && isset($this->activeUsers[$client->resourceId]) && 
                    $this->activeUsers[$client->resourceId]['room_id'] == $data['room_id']) {
                    $client->send(json_encode([
                        'type' => 'user_joined',
                        'username' => $data['username']
                    ]));
                }
            }
            
            // Send the new user the list of active users in the room
            $usersInRoom = array_filter($this->activeUsers, function($user) use ($data) {
                return $user['room_id'] == $data['room_id'] && $user['username'] != $data['username'];
            });
            
            $from->send(json_encode([
                'type' => 'user_list',
                'users' => array_column($usersInRoom, 'username')
            ]));
            
        } elseif ($data['type'] === 'message') {
            // Save message to database
            $stmt = $conn->prepare("INSERT INTO messages (room_id, user_id, message_text) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $data['room_id'], $data['user_id'], $data['message']);
            $stmt->execute();
            
            // Broadcast message to all clients in the same room
            foreach ($this->clients as $client) {
                if (isset($this->activeUsers[$client->resourceId]) {
                    if ($this->activeUsers[$client->resourceId]['room_id'] == $data['room_id']) {
                        $client->send(json_encode([
                            'type' => 'message',
                            'username' => $data['username'],
                            'message' => $data['message'],
                            'timestamp' => date('H:i')
                        ]));
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($this->activeUsers[$conn->resourceId])) {
            $username = $this->activeUsers[$conn->resourceId]['username'];
            $room_id = $this->activeUsers[$conn->resourceId]['room_id'];
            
            // Notify others in the room about the user leaving
            foreach ($this->clients as $client) {
                if (isset($this->activeUsers[$client->resourceId]) && 
                    $this->activeUsers[$client->resourceId]['room_id'] == $room_id) {
                    $client->send(json_encode([
                        'type' => 'user_left',
                        'username' => $username
                    ]));
                }
            }
            
            unset($this->activeUsers[$conn->resourceId]);
        }
        
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

echo "WebSocket server running on port 8080\n";
$server->run();