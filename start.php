<?php

use WebSocket\logger\MainLogger;
use WebSocket\Server;

$bootstrap = 'vendor/autoload.php';

if (!is_file($bootstrap)) {
    fwrite(STDERR, "Please run 'composer install' first to set up autoload.\n");
    exit(1);
}
set_time_limit(0);
require_once $bootstrap;

$logger = new MainLogger();

try {
    $logger->info('Starting server... please wait');
    $logger->info('Loading socket server...');

    include_once 'socket/SocketConfig.php';
    $socket = SocketConfig::getData();

    if ($socket === null) {
        throw new Exception('Socket configuration not found!');
    }

    $server = new Server($logger, $socket);
} catch (Exception $e) {
    $logger->error($e->getMessage());
    $logger->info('Server stopped!');
    exit(1);
}

$logger->info('Server stopped!');

exit(0);