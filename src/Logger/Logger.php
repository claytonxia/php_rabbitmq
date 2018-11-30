<?php

namespace Cto\Rabbit\Logger;

use Cto\Rabbit\Helper\RabbitHelper;
use Monolog\Logger as MLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    public static $defaultLoggerName = "php_rabbit_logger";

    public static $defaultLoggerLevel = MLogger::INFO;

    public static $funcArray = ["debug", "info", "warn", "error", "critical"];

    public static function __callStatic($name, $arguments)
    {
        $config = RabbitHelper::getConfig();
        $logConfig = $config["rabbitmq"]["log"];
        if (!$logConfig["log_on"]) {
            return;
        }
        if (!in_array($name, self::$funcArray)) {
            throw new \Exception("logger function $name not supported");
        }
        if (!$logPath = $logConfig["log_file_path"]) {
            throw new \Exception("log file not configured");
        }
        $logLevel = $logConfig["log_level"] !== null ? $logConfig["log_level"] : self::$defaultLoggerLevel;
        $logger = new MLogger($logConfig["log_name"] !== null ? $logConfig["log_name"] : self::$defaultLoggerName);
        $streamHandler = new StreamHandler($logPath);
        $logger->pushHandler($streamHandler);
        call_user_func_array(array($logger, $name), $arguments);
    }
}