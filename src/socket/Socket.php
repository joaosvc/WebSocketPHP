<?php

declare(strict_types=1);

namespace WebSocket\socket;

use Exception;
use WebSocket\logger\Logger;

final class Socket {
    private \Socket|false $socket;

    private Logger $logger;

    private string $addressIp;
    private int $addressPort;

    /** @throws Exception */
    public function __construct(Logger $logger, string $addressIp, int $addressPort) {
        $this->logger = $logger;
        $this->logger->info('Starting socket...');

        if (!extension_loaded('sockets')) {
            throw new Exception('The sockets extension is not loaded.');
        }
        $this->addressIp = $addressIp;
        $this->addressPort = $addressPort;

        $this->connect();
        $this->logger->info(sprintf('Socket listening on %s:%s', $this->addressIp, $this->addressPort));
    }

    /** @throws Exception */
    private function connect(): void {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            throw new Exception('Failed to create socket!');
        }

        if (@socket_bind($this->socket, $this->addressIp, $this->addressPort) === false) {
            $error = socket_last_error($this->socket);

            if($error === SOCKET_EADDRINUSE){ //platform error messages aren't consistent
                throw new Exception(sprintf('Failed to bind socket: Something else is already running on %s', $this->addressIp), $error);
            }
            throw new Exception(sprintf('Failed to bind to %s: %s', $this->addressIp, trim(socket_strerror($error))), $error);
        }

        if (!socket_set_nonblock($this->socket)) {
            throw new Exception('Failed to set socket nonblock!');
        }

        if (!socket_listen($this->socket)) {
            throw new Exception('Failed to listen to socket!');
        }
    }

    /*** @return \Socket */
    public function getSocket(): \Socket {
        return $this->socket;
    }

    public function close(): void {
        @socket_close($this->socket);
    }
}