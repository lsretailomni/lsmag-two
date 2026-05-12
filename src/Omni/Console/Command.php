<?php
declare(strict_types=1);

namespace Ls\Omni\Console;

use \Ls\Omni\Exception\InvalidServiceTypeException;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    public const TYPE = 'type';

    public const BASE_URL = 'base';

    /** @var InputInterface */
    public $input;

    /** @var OutputInterface */
    public $output;

    /** @var ServiceType */
    public $type;

    /** @var string */
    public $baseUrl;

    /**
     * @var Data
     */
    public $omniDataHelper;

    /**
     * @param Service $service
     * @param Reader $dirReader
     */
    public function __construct(
        public Service $service,
        public Reader $dirReader
    ) {
        parent::__construct();
    }

    /**
     * Initialize required properties
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidServiceTypeException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->input = $input;
        $this->output = $output;

        if ($this->type == null) {
            $type = $input->getOption(self::TYPE);
            if (!ServiceType::isValid($type)) {
                throw new InvalidServiceTypeException();
            }
            $this->type = new ServiceType($type);
        }

        // use LSR function to get base url.
        $this->baseUrl = $input->getOption(self::BASE_URL);
        !empty($this->baseUrl) || $this->baseUrl = $this->getBaseUrl();
    }

    /**
     * Get base Url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $objectManager = ObjectManager::getInstance();

        /** @var Service $service */
        $service = $objectManager->create(Service::class);

        return $service->getOmniBaseUrl();
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
