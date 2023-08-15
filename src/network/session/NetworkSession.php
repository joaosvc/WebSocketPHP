<?php

declare(strict_types=1);

namespace WebSocket\network\session;

use WebSocket\network\QueryNetwork;

final class NetworkSession {

    public function __construct(
        private readonly QueryNetwork $queryNetwork,
        private readonly \Socket $socket,
        private readonly string $addressIp,
        private readonly int $addressPort,
        private readonly string $name,
        private readonly string $uuid,
    ) {
    }

    public function getSocket(): \Socket {
        return $this->socket;
    }

    public function getAddressIp(): string {
        return $this->addressIp;
    }

    public function getAddressPort(): int {
        return $this->addressPort;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getUuid(): string {
        return $this->uuid;
    }
}