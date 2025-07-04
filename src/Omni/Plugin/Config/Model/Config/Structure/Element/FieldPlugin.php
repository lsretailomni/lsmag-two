<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Config\Model\Config\Structure\Element;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\Config\Structure\Element\Field;

/**
 * Before plugin for modifying show in website to show in default for admin configuration when single store enabled
 */
class FieldPlugin
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        public StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Add show in default value for those field which is only for show in website
     *
     * @param Field $subject
     * @return Field[]
     */
    public function beforeShowInDefault(Field $subject)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            if ($subject->showInWebsite()) {
                $data                  = $subject->getData();
                $data['showInDefault'] = 1;
                unset($data['showInWebsite']);
                $subject->setData($data, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
            }
        }

        return [$subject];
    }
}
