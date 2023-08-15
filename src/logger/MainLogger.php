<?php

declare(strict_types=1);

namespace WebSocket\logger;

final class MainLogger implements Logger {
    public function info(string $message): void {
        $this->send('[INFO] ' . $message);
    }

    public function error(string $message): void {
        $this->send('[ERROR] ' . $message);
    }

    public function warning(string $message): void {
        $this->send('[WARNING] ' . $message);
    }

    public function debug(string $message): void {
        $this->send('[DEBUG] ' . $message);
    }

    private function send(string $message): void {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}