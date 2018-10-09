<?php

namespace Ls\Omni\Console;

use Ls\Omni\Exception\InvalidServiceTypeException;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    const TYPE = 'type';

    const BASE_URL = 'base';

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var ServiceType */
    protected $type;

    /** @var string */
    protected $base_url;

    /**
     * @var Service
     */
    protected $_service;


    /** @var \Magento\Framework\Module\Dir\Reader  */
    protected $_dirReader;



    public function __construct(
        \Ls\Omni\Service\Service $service,
        \Magento\Framework\Module\Dir\Reader $dirReader

    )
    {
        $this->_service     =   $service;
        $this->_dirReader   =   $dirReader;
        parent::__construct();

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidServiceTypeException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        parent::initialize($input, $output);

        $this->input = $input;
        $this->output = $output;

        if (is_null($this->type)) {
            $type = $input->getOption(self::TYPE);
            if (!ServiceType::isValid($type)) throw new InvalidServiceTypeException();
            $this->type = new ServiceType($type);
        }

        // user lSR function to get base url.
        $this->base_url = $input->getOption(self::BASE_URL);
        !empty($this->base_url) or $this->base_url = $this->getBaseUrl();
    }

    /**
     * @param string $path,...
     *
     * @return string
     */
    protected function path($path)
    {
        $parts = func_get_args();

        return join(DIRECTORY_SEPARATOR, $parts);
    }

    protected function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Ls\Omni\Service\Service  $service */
        $service = $objectManager->create('Ls\Omni\Service\Service');
        $service->getOmniBaseUrl();


    }
}
