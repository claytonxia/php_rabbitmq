<?php

require_once __DIR__ . '/../../autoload.php';

use Cto\Rabbit\Publisher\Publisher;


Publisher::publish('publisher1', "hello world");