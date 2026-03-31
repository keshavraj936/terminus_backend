<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use CampusConnect\Chat;

require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/src/Chat.php';

$host = 'localhost';
$db   = 'campus_connect';
$user = 'campus_user';
$pass = 'Campus@123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($conn)
        )
    ),
    8080,
    '0.0.0.0'
);

echo "CampusConnect WebSocket Server started on ws://0.0.0.0:8080\n";
$server->run();
