<?php

$autoloadFileArray = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

foreach ($autoloadFileArray as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
    }
}

use Cto\Rabbit\Consumer\AbstractConsumer;
use PhpAMqplib\Message\AMQPMessage;

class DemoConsumer extends AbstractConsumer
{
    public function consume(AMQPMessage $message)
    {
        echo $message->body, PHP_EOL;
        return true;
    }
}