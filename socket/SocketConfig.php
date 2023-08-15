<?php

declare(strict_types=1);

final class SocketConfig {
    public static function getData(): ?array {
        $contents = file_get_contents(__DIR__ . '\socket.json');
        $contents = json_decode($contents, true);

        if ($contents === null) {
            return null;
        }
        return $contents;
    }
}