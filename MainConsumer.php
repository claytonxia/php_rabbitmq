<?php

require_once __DIR__ . '/../../autoload.php';

use Cto\Rabbit\Consumer\AbstractConsumer;
use PhpAMqplib\Message\AMQPMessage;

class MainConsumer extends AbstractConsumer
{
    public function consume(AMQPMessage $message)
    {
        echo $message->body, PHP_EOL;
        return null;
    }
}