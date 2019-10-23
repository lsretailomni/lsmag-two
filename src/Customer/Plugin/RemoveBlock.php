<?php

namespace Ls\Customer\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\AbstractBlock;

class RemoveBlock
{
    const BLOCK_NAME = 'checkout.registration';

    const CONFIG_PATH = 'ls_mag/service/replicate_hierarchy_code';

    public $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterToHtml(AbstractBlock $subject, $result)
    {
        if ($subject->getNameInLayout() === self::BLOCK_NAME && $this->scopeConfig->getValue(self::class)) {
            return '';
        }

        return $result;
    }
}