<?php

namespace Ls\Replication\Block\Adminhtml\Logs;

use Magento\Framework\View\Element\Template;

/**
 * Class Report
 * @package Ls\Replication\Block\Adminhtml\Logs
 */
class Report extends Template
{

    /**
     * @param Template\Context $context
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->layoutProcessors = $layoutProcessors;
    }

    /**
     * Return log file name
     *
     * @return mixed
     */
    public function getQueryUrlData()
    {
        return $this->_request->getParam('log_filename');
    }
}
