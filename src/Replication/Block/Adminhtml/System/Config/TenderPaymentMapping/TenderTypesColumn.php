<?php

namespace Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @throws GuzzleException
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    private function getSourceOptions(): array
    {
        $storeTenderTypes = [];

        $websiteId = (int)$this->request->getParam('website');

        if ($websiteId == 0 && $this->lsr->getStoreManagerObject()->isSingleStoreMode()) {
            $stores               = $this->lsr->getAllStores();
            $store                = reset($stores);
            $storeTenderTypeArray = $this->helper->getTenderTypes($store->getId());
        } else {
            $storeTenderTypeArray = $this->helper->getTenderTypes($websiteId);
        }
        $type = 0;
        if (empty($storeTenderTypeArray)) {
            $storeTenderTypeArray = $this->dataHelper->fetchWebStoreTenderTypes();
            $type = 1;
        }
        $storeTenderTypes[] = ['value' => '', 'label' => __('Select tender type')];
        if (!empty($storeTenderTypeArray)) {
            foreach ($storeTenderTypeArray as $storeTenderType) {
                $storeTenderTypes[] = [
                    'value' => $type == 1 ? $storeTenderType->getCode() : $storeTenderType->getTenderTypeId(),
                    'label' => $type == 1 ? $storeTenderType->getDescription() : $storeTenderType->getName()
                ];
            }
        }

        return $storeTenderTypes;
    }
}
