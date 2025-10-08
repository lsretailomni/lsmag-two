<?php
declare(strict_types=1);

namespace Ls\Replication\Block\Adminhtml\Logs;

use Magento\Framework\View\Element\Template;

class Report extends Template
{
    /**
     * @param Template\Context $context
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        public array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
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
