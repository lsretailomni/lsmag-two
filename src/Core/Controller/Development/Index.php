<?php
/**
 * Created by PhpStorm.
 * User: sudhanshubajaj
 * Date: 23/07/2018
 * Time: 10:33 AM
 */

namespace Ls\Core\Controller\Development;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Index extends Action
{
    protected $_lsr;

    public function __construct(
        LSR $LSR,
        Context $context
    )
    {
        $this->_lsr = $LSR;
        parent::__construct($context);
        $this->context = $context;
    }

    public function execute()
    {
        echo "Something anythign"; exit;
        //if ($this->_lsr->isLSR()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
            $categoryObject = $objectManager->get('\Ls\Replication\Cron\ProductCreateTask')->execute();

            var_dump($categoryObject);
        //}
        //exit;
    }
}