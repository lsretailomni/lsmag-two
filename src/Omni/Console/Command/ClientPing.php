<?php

namespace Ls\Omni\Console\Command;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Console\Command;
use \Ls\Omni\Helper\Data;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientPing extends Command
{
    public const COMMAND_NAME = 'omni:client:ping';

    /**
     * @var Data
     */
    public $omniDataHelper;

    /**
     * Configures the command options and description.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Show ping response')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Omni service type', 'ecommerce')
            ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'Omni service base URL');
    }

    /**
     * Executes the command by pinging the specified Omni service.
     *
     * @param InputInterface $input The input interface containing command options
     * @param OutputInterface $output The output interface for displaying results
     *
     * @return int Return code (0 indicates success)
     * @throws GuzzleException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pingResponse = $this->getOmniDataHelper()->omniPing();

        // Output the result based on the ping response
        if (is_array($pingResponse) && !empty($pingResponse)) {
            foreach ($pingResponse as $index => $response) {
                $output->writeln(sprintf('%s:%s', $index, $response));
            }
        } else {
            $output->writeln("ERROR: Unable to establish connection with the endpoint");
        }

        // Return success code
        return 0;
    }

    /**
     * Get omni data helper using lazy load
     *
     * @return Data
     */
    public function getOmniDataHelper()
    {
        if ($this->omniDataHelper) {
            return $this->omniDataHelper;
        }

        $this->omniDataHelper = ObjectManager::getInstance()->get(Data::class);

        return $this->omniDataHelper;
    }
}
