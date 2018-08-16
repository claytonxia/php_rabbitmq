<?php

namespace Cto\Rabbit\Consumer;

use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractConsumer
{
    public function execute(AMQPMessage $message)
    {
        $result = $this->consume($message);
        $chan = $message->delivery_info['channel'];
        $delivery_tag = $message->delivery_info['delivery_tag'];
        $result ? $chan->basic_ack($delivery_tag) : $chan->basic_nack($delivery_tag);
    }

    public abstract function consume(AMQPMessage $message);
}
