<?php

namespace Cto\Rabbit\Message;

class Message
{
    const START_DECLARING_QUEUE = "starting to declare queue";

    const START_DECLARING_EXCHANGE = "starting to declare exchange";

    const START_DECLARING_BINDING = "starting to bind queue and exchange";

    const START_DELETING_QUEUE = "starting to delete queue";

    const START_DELETING_EXCHANGE = "starting to delete exchange";

    const START_DELETING_BINDING = "starting to unbind queue and exchange";

    const START_PURGING_QUEUE = "starting to purge queue";

    const SUCCESS = "completes";

    const ERROR = "error";

    CONST START = "starting";

    const QUEUE_NAME = "queue";

    const EXCHANGE_NAME = "exchange";

    const BINDING = "binding";

    const CONNECTION = "connection";

    const CONSUMER = "consumer";

    const FORCE = "force to delete";

    const FORCE_WARNING = "use force option to delete all queues";

    const QUEUE_PARAMETER = "queue";

    const EXCHANGE_PARAMETER = "exchange";

    const BINDING_PARAMETER = "binding";

    const CONNECTION_PARAMETER = "connection";

    const CONSUMER_PARAMETER = "consumer";

    const FORCE_PARAMETER = "force";

    const CONFIG_PARAMETER = "project_root";

    const CONFIG = "project root path";

    CONST CONFIG_DESCRIPTION = "init project root path for php_rabbitmq";

    const CONFIG_COMMAND ="rabbit:config";

    const DECLARE_ALL_COMMAND = "rabbit:declare-all";
    
    const DECLARE_ALL_DESCRIPTION = "generate all the queues,exchanges,bindings in configuration file";

    const DECLARE_QUEUE_COMMAND = "rabbit:declare-queue";

    const DECLARE_QUEUE_DESCRIPTION = "generate queues";

    const DECLARE_EXCHANGE_COMMAND = "rabbit:declare-exchange";

    const DECLARE_EXCHANGE_DESCRIPTION = "generate exchanges";

    const DECLARE_BINDING_COMMAND = "rabbit:declare-binding";

    const DECLARE_BINDING_DECRIPTION = "bind queues and exchanges";

    const DELETE_ALL_COMMAND = "rabbit:delete-all";

    const DELETE_ALL_DESCRIPTION = "delete all the queues,exchanges,bindings in the configuration file";

    const DELETE_QUEUE_COMMAND = "rabbit:delete-queue";

    const DELETE_QUEUE_DESCRIPTION = "delete queues";

    const DELETE_EXCHANGE_COMMAND = "rabbit:delete-exchange";

    const DELETE_EXCHANGE_DESCRIPTION = "delete exchanges";

    const DELETE_BINDING_COMMAND = "rabbit:delete-binding";

    const DELETE_BINDING_DESCRIPTION = "unbind queues and exchanges";

    const PURGE_QUEUE_COMMAND = "rabbit:purge-queue";

    const PURGE_QUEUE_DESCRIPTION = "purge queues";

    const CONSUME_COMMAND = "rabbit:consume-queue";

    const CONSUME_DESCRIPTION = "consume from queue";

}