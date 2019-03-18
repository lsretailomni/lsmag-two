<?php
namespace Ls\Customer\Block\Order;

/**
 * Class View
 * @package Ls\Customer\Block\Order
 */
class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/view.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * View constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    // @codingStandardsIgnoreStart
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getDocumentId()));
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }
}