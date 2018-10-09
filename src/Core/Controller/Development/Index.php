<?php

namespace Ls\Core\Controller\Development;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Index extends Action
{


    /**
     * Index constructor.
     * @param Context $context
     */

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {

        //Get Object Manager Instance
        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();

        //Load product by product id
        /** @var \Ls\Replication\Cron\ReplDiscountTask $Service */
        $Service        = $objectManager->create('Ls\Replication\Cron\DiscountCreateTask');
        //$Service        = $objectManager->create('Ls\Replication\Cron\ReplEcommDiscountsTask');

        $Service->execute();

    }

}