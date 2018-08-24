<?php

namespace Cto\Rabbit\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Process\Process;
use Cto\Rabbit\Helper\RabbitHelper;

class ConsumerPcntlCommand extends Command
{
    public static $supportedAction = ['config', 'init', 'start', 'stop', 'reload', 'status'];

    public static $config;

    public $pcntlPath;

    public $projectPcntlPath;

    public function __construct()
    {
        parent::__construct();
        $this->pcntlPath = __DIR__ . '/../Pcntl';
    }

    public function configure()
    {
        $this->setName("rabbit:consumer:pcntl");
        $this->addArgument("action", InputArgument::REQUIRED, sprintf("supported actions: %s", implode(" ", self::$supportedAction)));
        $this->addArgument("param", InputArgument::OPTIONAL, "action parameter");
        $this->addArgument("connection", InputArgument::OPTIONAL, "connection");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        self::$config = RabbitHelper::getConfig();
        $this->initProjectPcntlDir();
        $action = $input->getArgument("action");
        $param = $input->getArgument("param");
        $connection = $input->getArgument("connection") ? : self::$config['rabbitmq']['default_connection'];
        $this->checkActionSuppored($action);
        call_user_func_array([$this, $action], [$param, $connection]);
    }

    private function initProjectPcntlDir()
    {
        if (!isset(self::$config['rabbitmq']['project_root_path']) || !self::$config['rabbitmq']['project_root_path']) {
            throw new \Exception("please config project root path in rabbit.yml");
        }
        $projectRootPath = self::$config['rabbitmq']['project_root_path'];
        if (!file_exists($projectRootPath)) {
            throw new \Exception("project root path not exists");
        }
        if (substr($projectRootPath, -1) != DIRECTORY_SEPARATOR) {
            $projectRootPath = $projectRootPath . DIRECTORY_SEPARATOR;
        }
        $projectPcntlPath = $projectRootPath . 'pcntl';
        if (!file_exists($projectPcntlPath)) {
            mkdir($projectPcntlPath);
        }
        $this->projectPcntlPath = $projectPcntlPath;
        $relativeDirArray = ['/Conf', '/Run', '/Log'];
        foreach ($relativeDirArray as $dir) {
            !file_exists($projectPcntlPath . $dir) && mkdir($projectPcntlPath . $dir);
        }
    }

    private function checkActionSuppored($action)
    {
        if (!in_array($action, self::$supportedAction)) {
            throw new \Exception("unsupported action");
        }
    }

    private function config($consumer, $connection)
    {
        $consumerDetail = $this->getConsumerDetail($consumer, $connection);

        $configTemplateLoader = new FilesystemLoader($this->pcntlPath . '/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $configTemplateLoader);

        $phpBin = isset(self::$config['rabbitmq']['php_bin_path']) && self::$config['rabbitmq']['php_bin_path'] !== null ? self::$config['rabbitmq']['php_bin_path'] : "php";

        $consumerCommand = sprintf('%s %s rabbit:consume-queue %s', $phpBin, realpath(__DIR__ . '/../../rabbit_manager'), $consumer);
        $consumerNumProcs = isset($consumerDetail['num_procs']) && $consumerDetail['num_procs'] !== null ? $consumerDetail['num_procs'] : 1;

        $configValArray = [
            'consumer_name' => sprintf("%s_%s", $connection, $consumer),
            'consumer_command' => $consumerCommand,
            'consumer_num' => $consumerNumProcs,
            'consumer_autostart' => isset($consumerDetail['autostart']) && $consumerDetail['autostart'] !== null ? $consumerDetail['autostart'] : true,
            'consumer_autorestart' => isset($consumerDetail['autorestart']) && $consumerDetail['autorestart'] !== null ? $consumerDetail['autorestart'] : true,
            'consumer_startsecs' => isset($consumerDetail['startsecs']) && $consumerDetail['startsecs'] !== null ? $consumerDetail['startsecs'] : 10
        ];

        isset($consumerDetail['out_log']) && $consumerDetail['out_log'] !== null && $configValArray['consumer_out_log'] = $consumerDetail['out_log'];
        isset($consumerDetail['error_log']) && $consumerDetail['error_log'] !== null && $configValArray['consumer_error_log'] = $consumerDetail['error_log'];

        $config = $templating->render("program.php", $configValArray);

        $targetConfigFile = sprintf("%s/Conf/%s.ini", $this->projectPcntlPath, $consumer);
        file_put_contents($targetConfigFile, $config);

        $this->renderPcntlConfig();
    }

    private function init($consumer, $connection)
    {
        $this->checkPcntlConfigExist($consumer);
        $cmd = sprintf("/usr/bin/supervisord -c %s", $this->projectPcntlPath . '/supervisord.conf');
        $this->runCommand($cmd);
    }

    private function start($consumer, $connection)
    {
        $this->checkPcntlConfigExist($consumer);
        $procNum = $this->getConsumerProcNum($consumer, $connection);
        for ($i = 0; $i < $procNum; $i++) {
            $cmd = sprintf("/usr/bin/supervisorctl -c %s start %s", $this->projectPcntlPath . '/supervisord.conf', sprintf("%s_%s:%s_%s_%02d", $connection, $consumer, $connection, $consumer, $i));
            $this->runCommand($cmd);
        }
        $this->status($consumer, $connection);
    }

    private function stop($consumer, $connection)
    {
        $this->checkPcntlConfigExist($consumer);
        $procNum = $this->getConsumerProcNum($consumer, $connection);
        for ($i = 0; $i < $procNum; $i++) {
            $cmd = sprintf("/usr/bin/supervisorctl -c %s stop %s", $this->projectPcntlPath . '/supervisord.conf', sprintf("%s_%s:%s_%s_%02d", $connection, $consumer, $connection, $consumer, $i));
            $this->runCommand($cmd);
        }
        $this->status($consumer, $connection);
    }

    private function reload($consumer = null, $connection = null)
    {
        $this->checkPcntlConfigExist($consumer);
        $cmd = sprintf("/usr/bin/supervisorctl -c %s reload", $this->projectPcntlPath . '/supervisord.conf');
        $this->runCommand($cmd);
    }

    private function status($consumer, $connection)
    {
        $this->checkPcntlConfigExist($consumer);
        $procNum = $this->getConsumerProcNum($consumer, $connection);
        for ($i = 0; $i < $procNum; $i++) {
            $cmd = sprintf("/usr/bin/supervisorctl -c %s status %s", $this->projectPcntlPath . '/supervisord.conf', sprintf("%s_%s:%s_%s_%02d", $connection, $consumer, $connection, $consumer, $i));
            $status = $this->runCommand($cmd);
            echo $status;
        }
    }

    private function getConsumerDetail($consumer, $connection)
    {
        $config = self::$config;
        if (!isset($config['rabbitmq']['connections'][$connection]['consumers'][$consumer])) {
            throw new \Exception("consumer $consumer not exits");
        }
        return $config['rabbitmq']['connections'][$connection]['consumers'][$consumer];
    }

    private function checkPcntlConfigExist($consumer)
    {
        $configPath = sprintf("%s/Conf/%s.ini", $this->projectPcntlPath, $consumer);
        if (!file_exists($configPath)) {
            throw new \Exception("config file not found, init first");
        }
    }

    private function runCommand($cmd)
    {
        $proc = new Process($cmd);
        $proc->run();
        return $proc->getOutput();
    }

    private function renderPcntlConfig()
    {
        $fileLoader = new FilesystemLoader($this->pcntlPath . '/%name%');
        $templating = new PhpEngine(new TemplateNameParser, $fileLoader);
        $targetConfigContent = $templating->render("supervisord.conf.tpl", [
            "sock_path" => realpath($this->projectPcntlPath) . '/Run/supervisord.sock',
            "log_file" => realpath($this->projectPcntlPath) . '/Log/consumer.log',
            "pid_file" => realpath($this->projectPcntlPath) . '/Run/supervisord.pid',
            "config_pattern" => realpath($this->projectPcntlPath) . '/Conf/*.ini'
        ]);
        file_put_contents($this->projectPcntlPath . '/supervisord.conf', $targetConfigContent);
    }

    private function getConsumerProcNum($consumer, $connection)
    {
        $consumerDetail = $this->getConsumerDetail($consumer, $connection);
        $procNum = isset($consumerDetail['num_procs']) && $consumerDetail['num_procs'] !== null ? $consumerDetail['num_procs'] : 1;
        return $procNum;
    }
}
