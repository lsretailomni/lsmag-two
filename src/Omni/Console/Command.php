<?php

namespace Ls\Omni\Console;

use \Ls\Omni\Exception\InvalidServiceTypeException;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package Ls\Omni\Console
 */
class Command extends SymfonyCommand
{
    const TYPE = 'type';

    const BASE_URL = 'base';

    /** @var InputInterface */
    public $input;

    /** @var OutputInterface */
    public $output;

    /** @var ServiceType */
    public $type;

    /** @var string */
    public $base_url;

    /**
     * @var Service
     */
    public $service;

    /** @var Reader */
    public $dirReader;

    public function __construct(
        Service $service,
        Reader $dirReader
    ) {
        $this->service       =   $service;
        $this->dirReader    =   $dirReader;
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidServiceTypeException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {

        parent::initialize($input, $output);

        $this->input = $input;
        $this->output = $output;

        if ($this->type==null) {
            $type = $input->getOption(self::TYPE);
            if (!ServiceType::isValid($type)) {
                throw new InvalidServiceTypeException();
            }
            // @codingStandardsIgnoreLine
            $this->type = new ServiceType($type);
        }

        // user lSR function to get base url.
        $this->base_url = $input->getOption(self::BASE_URL);
        !empty($this->base_url) or $this->base_url = $this->getBaseUrl();
    }

    public function getBaseUrl()
    {
        $objectManager = ObjectManager::getInstance();

        /** @var Service $service */
        // @codingStandardsIgnoreLine
        $service = $objectManager->create('Ls\Omni\Service\Service');
        $service->getOmniBaseUrl();
    }
}
