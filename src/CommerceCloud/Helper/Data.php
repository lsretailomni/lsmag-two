<?php

namespace Ls\CommerceCloud\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Ramsey\Uuid\Uuid;

/**
 * Helper class to define utility functions
 */
class Data extends AbstractHelper
{
    /**
     * Generate a new uuid using ramsey library
     *
     * @return string
     * @throws Exception
     */
    public function generateUuid()
    {
        return Uuid::uuid4()->toString();
    }
}
