<?php

namespace Ls\Replication\Block\Adminhtml\Logs;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Class Report
 * @package Ls\Replication\Block\Adminhtml\Logs
 */
class Report extends Template
{
    /**
     * @var Registry
     */
    public $coreRegistry;

    /**
     * Report constructor.
     * @param Template\Context $context
     * @param Registry $coreRegistry
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        array $layoutProcessors = [],
        array $data = []
    ) {

        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
        $this->layoutProcessors = $layoutProcessors;
    }

    /**
     * @return mixed
     */
    public function getLogData()
    {
        return $this->coreRegistry->registry("display_log");
    }

    /**
     * @return mixed
     */
    public function getQueryUrlData()
    {
        return $logFileName = $this->_request->getParam('log_filename');
    }
}
