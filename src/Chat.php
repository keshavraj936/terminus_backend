<?php
namespace CampusConnect;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    private $db;

    public function __construct($dbConnection) {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->db = $dbConnection;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (isset($data['type'])) {
            if ($data['type'] === 'auth') {
                $user_id = $data['user_id'];
                $this->userConnections[$user_id] = $from;
                $from->user_id = $user_id; 
                echo "User {$user_id} authenticated on connection {$from->resourceId}\n";
                return;
            }

            if ($data['type'] === 'message') {
                $sender_id = $from->user_id ?? null;
                if (!$sender_id) return;

                $receiver_id = $data['receiver_id'];
                $messageText = $data['message'];

                // Save to database
                $stmt = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $stmt->execute([$sender_id, $receiver_id, $messageText]);
                $msg_id = $this->db->lastInsertId();

                // Fetch the message back with timestamp for exact sync
                $stmt = $this->db->prepare("SELECT id, sender_id, receiver_id, message, created_at FROM messages WHERE id = ?");
                $stmt->execute([$msg_id]);
                $savedMsg = $stmt->fetch(PDO::FETCH_ASSOC);

                $payload = json_encode([
                    'type' => 'message',
                    'data' => $savedMsg
                ]);

                // Send to receiver if online
                if (isset($this->userConnections[$receiver_id])) {
                    $this->userConnections[$receiver_id]->send($payload);
                }

                // Send back to sender for immediate local state update
                $from->send($payload);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($conn->user_id)) {
            unset($this->userConnections[$conn->user_id]);
            echo "Connection {$conn->resourceId} (User {$conn->user_id}) has disconnected\n";
        } else {
            echo "Connection {$conn->resourceId} has disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
