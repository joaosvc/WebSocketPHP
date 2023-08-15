<?php

namespace WebSocket;

use Exception;
use WebSocket\logger\MainLogger;
use WebSocket\network\QueryNetwork;

final class Server {
    private static ?Server $instance = null;

    private bool $running = true;

    private QueryNetwork $queryNetwork;

    /** @throws Exception */
    public function __construct(private readonly MainLogger $logger, array $socketConfig) {
        if (self::$instance instanceof Server) {
            throw new Exception('Server already running!');
        }
        self::$instance = $this;
        $this->queryNetwork = new QueryNetwork($this, $socketConfig);

        $logger->info('Server started!');
        $this->tickProcessor();
    }

    public function tickProcessor(): void {
        while ($this->running) {
            $this->tick();
        }
        $this->queryNetwork->close();
    }

    private function tick(): void {
        $this->queryNetwork->tick();
    }

    public function getLogger(): MainLogger {
        return $this->logger;
    }
}