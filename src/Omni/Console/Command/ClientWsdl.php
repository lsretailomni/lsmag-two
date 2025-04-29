<?php

namespace Ls\Omni\Console\Command;

use \Ls\Omni\Console\Command;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\Soap\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientWsdl extends Command
{
    public const COMMAND_NAME = 'omni:client:wsdl';

    /**
     * Configures the command by setting its name, description, and available options.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Show WSDL contents')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Omni service type', 'ecommerce')
            ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'Omni service base URL');
    }

    /**
     * Executes the command to fetch and display the WSDL XML of the specified Omni service.
     *
     * @param InputInterface  $input  The input interface containing command options
     * @param OutputInterface $output The output interface for displaying results
     *
     * @return int Return code (0 indicates success)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch the WSDL URL for the specified Omni service
        $wsdlUrl = Service::getUrl($this->type, $this->baseUrl);

        // Instantiate the SOAP client with the WSDL URL and service type
        /** @var Client $soapClient */
        // @codingStandardsIgnoreLine
        $soapClient = new Client($wsdlUrl, $this->type);

        // Output the WSDL XML content
        $output->writeln($soapClient->getWsdlXml()->saveXML());

        // Return success code
        return 0;
    }
}
