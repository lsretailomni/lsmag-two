<?php

namespace Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Tender type class
 */
class TenderTypesColumn extends Select
{
    /**
     * @var ReplicationHelper
     */
    public $helper;

    /** @var RequestInterface */
    public $request;

    /** @var Data */
    public $dataHelper;

    /** @var LSR */
    public $lsr;

    /**
     * @param Context $context
     * @param ReplicationHelper $helper
     * @param Data $dataHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        ReplicationHelper $helper,
        Data $dataHelper,
        LSR $lsr,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper     = $helper;
        $this->dataHelper = $dataHelper;
        $this->lsr        = $lsr;
        $this->request    = $request;
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Return payment options array
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $storeTenderTypes = [];

        $scopeId = (int)$this->request->getParam('website');
        if ($scopeId == 0 && $this->lsr->getStoreManagerObject()->isSingleStoreMode()) {
            $stores               = $this->lsr->getAllStores();
            $store                = reset($stores);
            $storeTenderTypeArray = $this->helper->getTenderTypes($store->getId());
        } else {
            $storeTenderTypeArray = $this->helper->getTenderTypes($scopeId);
        }
        if (empty($storeTenderTypeArray)) {
            $storeTenderTypeArray = $this->dataHelper->getTenderTypesDirectly($scopeId);
        }
        $storeTenderTypes[] = ['value' => '', 'label' => __('Select tender type')];
        if (!empty($storeTenderTypeArray)) {
            foreach ($storeTenderTypeArray as $storeTenderType) {
                $storeTenderTypes[] = [
                    'value' => $storeTenderType->getOmniTenderTypeId(),
                    'label' => __($storeTenderType->getName())
                ];
            }
        }

        return $storeTenderTypes;
    }
}
