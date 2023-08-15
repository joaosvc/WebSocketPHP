<?php

declare(strict_types=1);

namespace WebSocket\network;

use Exception;
use WebSocket\logger\PrefixedLogger;
use WebSocket\network\session\NetworkSession;
use WebSocket\Server;
use WebSocket\socket\Socket;

final class QueryNetwork {
    private Socket $socket;

    private PrefixedLogger $logger;

    /** @var \Socket[] */
    private array $clients = [];

    /** @var NetworkSession[] */
    private array $sessions = [];

    private string $addressIp;

    private int $addressPort;

    /** @throws Exception */
    public function __construct(Server $server, array $socketConfig) {
        $this->addressIp = $socketConfig['addressIp'];
        $this->addressPort = $socketConfig['addressPort'];

        $this->logger = new PrefixedLogger('[NetworkSession]: ', $server->getLogger());
        $this->socket = new Socket($server->getLogger(), $this->addressIp, $this->addressPort);
    }

    public function getAddressIp(): string {
        return $this->addressIp;
    }

    public function getAddressPort(): int {
        return $this->addressPort;
    }

    public function tick(): void {
        do {
            $socket = $this->receivePacket();
        } while($socket);
    }

    private function onSocketConnect(\Socket $newClient, string $addressIp, int $addressPort): void {
        $this->clients[spl_object_hash($newClient)] = $newClient;
        $this->logger->info('New session opened from ' . $addressIp . ':' . $addressPort);
    }

    private function onSocketDisconnect(\Socket $clientSocket, string $addressIp, int $addressPort): void {
        unset($this->clients[spl_object_hash($clientSocket)]);
        $session = $this->sessions[spl_object_hash($clientSocket)] ?? null;

        $this->logger->info('Session closed from ' . $addressIp . ':' . $addressPort);

        if ($session !== null) {
            $this->onSessionClose($session);
        }
    }

    private function onPostSessionOpenned(\Socket $socket, string $addressIp, int $addressPort, array $data): void {
        $name = $data['name'] ?? null;
        $uuid = $data['uuid'] ?? null;

        if ($name === null || $uuid === null) {
            $this->logger->debug(sprintf('Receveid invalid session data from %s:%s', $addressIp, $addressPort));
            return;
        }

        $this->onSessionOpen(new NetworkSession($this, $socket, $addressIp, $addressPort, $name, $uuid));
    }

    private function onSessionOpen(NetworkSession $session): void {
        $this->sessions[spl_object_hash($session->getSocket())] = $session;
        $this->logger->info('Session opened: ' . $session->getName());
    }

    private function onSessionClose(NetworkSession $session): void {
        unset($this->sessions[spl_object_hash($session->getSocket())]);
        $this->logger->info('Session closed: ' . $session->getName());
    }

    private function receivePacket(): bool {
        $serverSocket = $this->socket->getSocket();

        $read = $this->clients;
        $write = $except = null;
        $read[] = $serverSocket;

        if (@socket_select($read, $write, $except, 0) < 1) {
            return false;
        }

        if (in_array($serverSocket, $read)) {
            $newClient = @socket_accept($serverSocket);
            socket_getpeername($newClient, $addressIp, $addressPort);

            $key = array_search($serverSocket, $read);
            unset($read[$key]);

            $this->onSocketConnect($newClient, $addressIp, $addressPort);
        }

        foreach ($read as $clientSocket) {
            $data = @socket_read($clientSocket, 1024);
            @socket_getpeername($clientSocket, $addressIp, $addressPort);

            if ($data === false) {
                $error = socket_last_error($clientSocket);

                if ($error === SOCKET_ECONNRESET) {
                    socket_close($clientSocket);
                    $this->onSocketDisconnect($clientSocket, $addressIp, $addressPort);
                } else {
                    $this->logger->info('Socket error: ' . socket_strerror($error) . "\n");
                }
                continue;
            }

            try {
                $this->onSocketPacket($clientSocket, $addressIp, $addressPort, $data);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        return false;
    }

    /** @throws Exception */
    private function onSocketPacket(\Socket $clientSocket, string $addressIp, int $addressPort, string $data): void {
        $data = trim($data);

        if (!empty($data)) {
            $data = json_decode($data, true);

            if ($data === false) {
                throw new Exception(sprintf('Receveid bad json data from %s:%s', $addressIp, $addressPort));
            }
            $type = $data['type'] ?? null;

            if ($type === 'connect') {
                $this->onPostSessionOpenned($clientSocket, $addressIp, $addressPort, $data['data'] ?? null);
            } else {
                $session = $this->sessions[spl_object_hash($clientSocket)] ?? null;

                if ($session === null) {
                    throw new Exception(sprintf('Receveid data from %s:%s, but client not connected', $addressIp, $addressPort));
                }
                $this->handlerPacket($session, $data);
            }
        }
    }

    private function handlerPacket(NetworkSession $session, array $packet): void {
        $type = trim($packet['type'] ?? '');
        $data = $packet['data'] ?? [];

        switch ($type) {
            case 'message':
                $message = $data['message'] ?? null;

                if ($message === null) {
                    $this->logger->debug(sprintf('Receveid invalid message from %s:%s', $session->getAddressIp(), $session->getAddressPort()));
                    break;
                }

                foreach ($this->clients as $client) {
                    if ($client === $session->getSocket() || $client === $this->socket->getSocket()) {
                        continue;
                    }
                    socket_write($client, json_encode([
                        'type' => 'message',
                        'data' => [
                            'name' => $session->getName(),
                            'message' => $message
                        ]
                    ]));
                }

                fwrite(STDERR, sprintf('[%s]: %s', $session->getName(), $message));
                break;
            case 'disconnect':
                socket_close($session->getSocket());
                $this->onSocketDisconnect($session->getSocket(), $session->getAddressIp(), $session->getAddressPort());
                break;
            default:
                $this->logger->debug(sprintf('Receveid unknown packet from %s:%s', $session->getAddressIp(), $session->getAddressPort()));
                break;
        }
    }

    public function close(): void {
        $this->socket->close();
    }
}