<?php

namespace Cto\Rabbit\Helper;

use Symfony\Component\Yaml\Yaml;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


class RabbitHelper
{
    public static $config = [];

    public static $conn = null;

    public static $defaultConnectionConfig = [
        'host' => 'localhost',
        'port' => 5672,
        'vhost' => '/',
        'user' => 'guest',
        'password' => 'guest',
        'connection_timeout' => 30,
        'read_write_timeout' => 30,
        'heartbeat' => 10
    ];

    public static $defaultQueueConfig = [
        'passive' => false,
        'durable' => false,
        'exclusive' => false,
        'auto_delete' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null,
    ];

    public static $defaultExchangeConfig = [
        'type' => 'direct',
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    ];

    public static $defaultBindingConfig = [
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    ];

    public static $defaultQosConfig = [
        'qos_prefetch_size' => null,
        'qos_prefetch_count' => 1
    ];

    public static function getConfig()
    {
        $autoloadFileArray = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../../autoload.php'
        ];

        foreach ($autoloadFileArray as $autoloadFile) {
            if (file_exists($autoloadFile)) {
                require_once $autoloadFile;
            }
        }
        $file = __DIR__ . '/../../../../../rabbit.yml';
        $config = Yaml::parseFile($file);
        return $config;
    }

    public static function init()
    {
        self::$config = self::getConfig();
    }

    public static function getConnection($name = null)
    {
        $connectionArray = self::$config['rabbitmq']['connections'];
        if (!array_key_exists($name, $connectionArray)) {
            throw new \Exception("missing connection");
        }
        $connectionDetail = $connectionArray[$name];
        try {
            $conn = new AMQPStreamConnection(
                $connectionDetail['host'] !== null ? $connectionDetail['host'] : self::$defaultConnectionConfig['host'],
                $connectionDetail['port'] !== null ? $connectionDetail['port'] : self::$defaultConnectionConfig['port'],
                $connectionDetail['user'] !== null ? $connectionDetail['user'] : self::$defaultConnectionConfig['user'],
                $connectionDetail['password'] !== null ? $connectionDetail['password'] : self::$defaultConnectionConfig['password'],
                $connectionDetail['vhost'] !== null ? $connectionDetail['vhost'] : self::$defaultConnectionConfig['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $connectionDetail['connection_timeout'] !== null ? $connectionDetail['connection_timeout'] : self::$defaultConnectionConfig['connection_timeout'],
                $connectionDetail['read_write_timeout'] !== null ? $connectionDetail['read_write_timeout'] : self::$defaultConnectionConfig['read_write_timeout'],
                null,
                true,
                $connectionDetail['heartbeat'] !== null ? $connectionDetail['heartbeat'] : self::$defaultConnectionConfig['heartbeat']
            );
        } catch (\Exception $e) {
            sleep(2);
            exit("Error in connecting to queue" . PHP_EOL);
        }
        return $conn;
    }

    public static function manageQueue($name = null, $connection = null, $isDelete = false, $isPurge = false)
    {
        self::checkConfigIsLoaded();
        $connection or $connection = self::$config['rabbitmq']['default_connection'];
        $conn = self::getConnection($connection);
        $queueArray = self::$config['rabbitmq']['connections'][$connection]['queues'];
        $declaringQueueArray = [];
        if ($name && !array_key_exists($name, $queueArray)) {
            throw new \Exception("queue not found");
        }
        if ($name) {
            array_push($declaringQueueArray, $queueArray[$name]);
        } else {
            $declaringQueueArray = array_values($queueArray);
        }
        $chan = $conn->channel();
        foreach ($declaringQueueArray as $queue) {
            if ($isPurge === true) {
                $chan->queue_purge($queue['name']);
            } elseif ($isDelete === true) {
                $chan->queue_delete($queue['name']);
            } else {
                $chan->queue_declare(
                    $queue['name'],
                    $queue['passive'] !== null ? $queue['passive'] : self::$defaultQueueConfig['passive'],
                    $queue['durable'] !== null ? $queue['durable'] : self::$defaultQueueConfig['durable'],
                    $queue['exclusive'] !== null ? $queue['exclusive'] : self::$defaultQueueConfig['exclusive'],
                    $queue['auto_delete'] !== null ? $queue['auto_delete'] : self::$defaultQueueConfig['auto_delete'],
                    $queue['nowait'] !== null ? $queue['nowait'] : self::$defaultQueueConfig['nowait'],
                    $queue['arguments'] !== null ? $queue['arguments'] : self::$defaultQueueConfig['arguments'],
                    $queue['ticket'] !== null ? $queue['ticket'] : self::$defaultQueueConfig['ticket']
                );
            }
        }
    }

    public static function manageExchange($name = null, $connection = null, $isDelete = false)
    {
        self::checkConfigIsLoaded();
        $connection or $connection = self::$config['rabbitmq']['default_connection'];
        $conn = self::getConnection($connection);
        $exchangeArray = self::$config['rabbitmq']['connections'][$connection]['exchanges'];
        $declaringExchangeArray = [];
        if ($name && !array_key_exists($name, $exchangeArray)) {
            throw new \Exception("exchange not found");
        }
        if ($name) {
            array_push($declaringExchangeArray, $exchangeArray[$name]);
        } else {
            $declaringExchangeArray = array_values($exchangeArray);
        }
        $chan = $conn->channel();
        foreach ($declaringExchangeArray as $exchange) {
            $isDelete === false ?
            $chan->exchange_declare(
                $exchange['name'],
                $exchange['type'] !== null ? $exchange['type'] : self::$defaultExchangeConfig['type'],
                $exchange['passive'] !== null ? $exchange['passive'] : self::$defaultExchangeConfig['passive'],
                $exchange['durable'] !== null ? $exchange['durable'] : self::$defaultExchangeConfig['durable'],
                $exchange['auto_delete'] !== null ? $exchange['auto_delete'] : self::$defaultExchangeConfig['auto_delete'],
                $exchange['nowait'] !== null ? $exchange['nowait'] : self::$defaultExchangeConfig['nowait'],
                $exchange['arguments'] !== null ? $exchange['arguments'] : self::$defaultExchangeConfig['arguments'],
                $exchange['ticket'] !== null ? $exchange['ticket'] : self::$defaultExchangeConfig['ticket']
            ) : 
            $chan->exchange_delete($exchange['name']);
        }
    }

    public static function manageBinding($name = null, $connection = null, $isDelete = false)
    {
        self::checkConfigIsLoaded();
        $connection or $connection = self::$config['rabbitmq']['default_connection'];
        $conn = self::getConnection($connection);
        $bindingArray = self::$config['rabbitmq']['connections'][$connection]['bindings'];
        $declaringBindingArray = [];
        if ($name && !array_key_exists($name, $bindingArray)) {
            throw new \Exception("binding not found");
        }
        if ($name) {
            array_push($declaringBindingArray, $bindingArray[$name]);
        } else {
            $declaringBindingArray = array_values($bindingArray);
        }
        $chan = $conn->channel();
        foreach ($declaringBindingArray as $binding) {
            $isDelete === false ?
            $chan->queue_bind(
                $binding['queue'],
                $binding['exchange'],
                $binding['routing_key'],
                false,
                [],
                null
            ) :
            $chan->queue_unbind($binding['queue'], $binding['exchange'], $binding['routing_key']);
        }
    }

    public static function checkConfigIsLoaded()
    {
        if (!self::$config) {
            self::init();
        }
    }

    public static function publish($publisher, $message, $attribute = [])
    {
        self::checkConfigIsLoaded();
        $producerInfo = self::extractPublisher($publisher);
        $conn = self::getConnection($producerInfo['connection']);
        $chan = $conn->channel();
        $msg = new AMQPMessage($message, ['delivery_mode' => $producerInfo['producer']['delivery_mode'] !== null ? $producerInfo['producer']['delivery_mode'] : 2]);
        $msg->set("application_headers", new AMQPTable($attribute));
        $chan->basic_publish($msg, $producerInfo['producer']['exchange'], $producerInfo['producer']['routing_key']);
        $chan->close();
        $conn->close();
    }

    public static function consume($consumer)
    {
        self::checkConfigIsLoaded();
        $consumerInfo = self::extractConsumer($consumer);
        while (true) {
            try {
                $conn = self::getConnection($consumerInfo['connection']);
                $chan = $conn->channel();
                $chan->basic_qos(
                    $consumerInfo['consumer']['qos_prefetch_size'] !== null ? $consumerInfo['consumer']['qos_prefetch_size'] : self::$defaultQosConfig['qos_prefetch_size'],
                    $consumerInfo['consumer']['qos_prefetch_count'] !== null ? $consumerInfo['consumer']['qos_prefetch_count'] : self::$defaultQosConfig['qos_prefetch_count'],
                    null
                );
                $callback = new $consumerInfo['consumer']['callback'];
                $chan->basic_consume(
                    $consumerInfo['consumer']['queue'],
                    '',
                    false,
                    $consumerInfo['consumer']['no_ack'] !== null ? $consumerInfo['consumer']['no_ack'] : false,
                    false,
                    false,
                    [$callback, "execute"],
                    null,
                    []
                );
                while (count($chan->callbacks)) {
                    $chan->wait();
                }
                $chan->close();
                $conn->close();
            } catch (\Exception $e) {
                sleep(10);
            }
        }
    }

    public static function extractPublisher($publisher)
    {
        $config = self::$config['rabbitmq']['connections'];
        foreach ($config as $connName => $connArray) {
            $producers = $connArray['publishers'];
            if (isset($producers[$publisher])) {
                return ['connection' => $connName, 'producer' => $producers[$publisher]];
            }
        }
        throw new \Exception("producer not found");
    }

    public static function extractConsumer($consumer)
    {
        $config = self::$config['rabbitmq']['connections'];
        foreach ($config as $connName => $connArray) {
            $consumers = $connArray['consumers'];
            if (isset($consumers[$consumer])) {
                return ['connection' => $connName, 'consumer' => $consumers[$consumer]];
            }
        }
        throw new \Exception("consumer not found");
    }
}