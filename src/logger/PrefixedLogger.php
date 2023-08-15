<?php

declare(strict_types=1);

namespace WebSocket\logger;

final class PrefixedLogger {
    public function __construct(private string $prefix, private readonly  MainLogger $logger) {
    }

    public function info(string $message): void {
        $this->logger->info($this->prefix . $message);
    }

    public function error(string $message): void {
        $this->logger->error($this->prefix . $message);
    }

    public function warning(string $message): void {
        $this->logger->warning($this->prefix . $message);
    }

    public function debug(string $message): void {
        $this->logger->debug($this->prefix . $message);
    }

    public function setPrefix(string $prefix): void {
        $this->prefix = $prefix;
    }
}