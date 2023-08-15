<?php

include_once './socket/SocketConfig.php';

$socketConfig = SocketConfig::getData();
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$connected = socket_connect($socket, $socketConfig['addressIp'], $socketConfig['addressPort']);

function clearConsole(): void {
    echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
}
clearConsole();

if (!$connected) {
    fwrite(STDERR, "Socket connection failed\n");
    exit();
}
fwrite(STDOUT, "Connection established\n\n");
fwrite(STDOUT, "Type 'exit' to close connection\n\n");

$running = true;
$name = null;

while (!$name) {
    fwrite(STDOUT, "\nEnter your name: ");
    $name = rtrim(fgets(STDIN));

    if ($name === 'exit') {
        $running = false;
        break;
    }
}

if ($running) {
    $uuid = uniqid();

    $packet = json_encode([
        'type' => 'connect',
        'data' => [
            'name' => $name,
            'uuid' => $uuid
        ]
    ]);
    $bytes = strlen($packet);
    socket_write($socket, $packet, $bytes);

    clearConsole();
    fwrite(STDOUT, "Connected as $name\n\n");

    while ($running) {
        fwrite(STDOUT, "You: ");
        $message = rtrim(fgets(STDIN));

        if (!$message) {
            continue;
        }

        if ($message === 'exit') {
            $running = false;
            continue;
        }

        $messageData = json_encode([
            'type' => 'message',
            'data' => [
                'message' => $message
            ]
        ]);
        socket_write($socket, $messageData, strlen($messageData));
    }
}
fwrite(STDOUT, "\nClosing connection\n");

$packet = json_encode([
    'type' => 'disconnect'
]);
$bytes = strlen($packet);
socket_write($socket, $packet, $bytes);
socket_close($socket);
exit(0);
