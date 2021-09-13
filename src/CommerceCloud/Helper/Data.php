<?php

namespace Ls\CommerceCloud\Helper;

use Exception;
use \Ls\CommerceCloud\Model\LSR;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Ramsey\Uuid\Uuid;

/**
 * Helper class to define utility functions
 */
class Data extends AbstractHelper
{
    /**
     * @var LSR
     */
    public $commerceCloudLsr;

    /**
     * @param LSR $commerceCloudLsr
     * @param Context $context
     */
    public function __construct(
        LSR $commerceCloudLsr,
        Context $context
    ) {
        parent::__construct($context);
        $this->commerceCloudLsr = $commerceCloudLsr;
    }

    /**
     * Generate a new uuid using ramsey library
     *
     * @return string
     * @throws Exception
     */
    public function generateUuid()
    {
        $exists = true;
        $appId  = '';

        while ($exists) {
            $appId  = Uuid::uuid4()->toString();
            $exists = $this->commerceCloudLsr->configValueExists($appId);
        }

        return $appId;
    }
}
