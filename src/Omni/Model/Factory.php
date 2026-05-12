<?php
declare(strict_types=1);

namespace Ls\Omni\Model;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class to handle 3rd party modules that are not installed but there are references in our module
 */
class Factory
{
    /**
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        public Manager $moduleManager,
        public ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Initialize module class
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
