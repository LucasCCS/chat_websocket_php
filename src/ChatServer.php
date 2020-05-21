<?php
namespace Chat;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
        $data = [
            'type' => 'users_connected',
            'users_connected' => count($this->clients)
        ];
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        $msg = json_decode($msg);
        $msg->type = 'message';

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode($msg));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
        $data = [
            'type' => 'users_connected',
            'users_connected' => count($this->clients)
        ];
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}