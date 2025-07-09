<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\Socket\Connector as ReactConnector;

function send_websocket_message($data) {
    $loop = Factory::create();
    $reactConnector = new ReactConnector($loop, [
        'timeout' => 10,
        'tls' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    $connector = new Connector($loop, $reactConnector);
    
    $connector('ws://localhost:8080')
        ->then(function(Ratchet\Client\WebSocket $conn) use ($data) {
            $conn->send(json_encode($data));
            $conn->close();
        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });
    
    $loop->run();
}