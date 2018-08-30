<?php

namespace Cto\Rabbit\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Cto\Rabbit\Publisher\Publisher;

class PublishCommand extends RabbitCommand
{
    public function configure()
    {
        $this->setName("rabbit:publish");
        $this->addArgument("publisher", InputArgument::REQUIRED, "publisher name in rabbit.yml");
        $this->addArgument("message", InputArgument::REQUIRED, "string message to publish");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->info("starting"));
        $publisher = $input->getArgument("publisher");
        $message = $input->getArgument("message");
        Publisher::publish($publisher, $message);
        $output->writeln($this->info("completes"));
    }
}