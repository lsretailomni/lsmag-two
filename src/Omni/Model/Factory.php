<?php

namespace Ls\Omni\Model;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class to handle 3rd party modules that are not installed but there are references in our module
 */
class Factory
{
    /**
     * @var Manager
     */
    protected $moduleManager;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    /**
     * Insitiate the class
     *
     * @param $moduleName
     * @param $fileNameWithPath
     * @return mixed
     */
    public function create($moduleName, $fileNameWithPath)
    {
        if ($this->moduleManager->isEnabled($moduleName)) {
            $instanceName = $fileNameWithPath;
            return $this->objectManager->create($instanceName);
        }
        return null;
    }
}
