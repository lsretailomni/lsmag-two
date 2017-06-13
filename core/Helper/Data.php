<?php
namespace Ls\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'ls_mag/general/enabled';
    private $object_manager;
    private $store_manager;

    public function __construct ( Context $context,
                                  ObjectManagerInterface $object_manager,
                                  StoreManagerInterface $storeManager
    ) {
        $this->object_manager = $object_manager;
        $this->store_manager = $storeManager;
        parent::__construct( $context );
    }

    public function enabled () {
        $enabled = $this->scopeConfig->getValue( self::XML_PATH_ENABLED );

        return $enabled === '1' or $enabled === 1;
    }
}
