<?php

namespace Ls\Omni\Console\Command;

use \Ls\Omni\Client\OperationInterface;
use \Ls\Omni\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientPing extends Command
{
    public const COMMAND_NAME = 'omni:client:ping';

    /**
     * Configure command
     *
     * @return void
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('show WSDL contents')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce')
            ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url');
    }

    /**
     * Entry point for the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $uc_type = ucfirst($this->type->getValue());
        $class   = "Ls\\Omni\\Client\\$uc_type\\Operation\\Ping";
        /** @var OperationInterface $ping */
        // @codingStandardsIgnoreLine
        $ping = new $class();
        $pong = $ping->execute();

        if ($pong) {
            $this->output->writeln($pong->getResult());
        } else {
            $this->output->writeln("ERROR: Unable to establish connection with Commerce Service");
        }

        return 0;
    }
}
