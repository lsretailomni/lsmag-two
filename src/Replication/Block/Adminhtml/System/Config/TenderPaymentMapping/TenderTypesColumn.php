<?php

namespace Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class TenderTypesColumn extends Select
{
    /**
     * @var ReplicationHelper
     */
    public $helper;

    /** @var RequestInterface */
    public $request;

    /** @var LSR */
    public $lsr;

    /**
     * TenderTypesColumn constructor.
     * @param Context $context
     * @param ReplicationHelper $helper
     * @param LSR $lsr
     * @param RequestInterface $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        ReplicationHelper $helper,
        LSR $lsr,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper  = $helper;
        $this->lsr     = $lsr;
        $this->request = $request;
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
        $storeTenderTypes     = [];
        $websiteId            = (int)$this->request->getParam('website');

        $storeTenderTypeArray = $this->helper->getTenderTypes($websiteId);
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
