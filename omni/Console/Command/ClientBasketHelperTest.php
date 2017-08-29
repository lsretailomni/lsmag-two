<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Console\Command;
use Ls\Omni\Helper\BasketHelper;
use Magento\Sales\Model\Order;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Bootstrap;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Customer\Model\LSR;

class ClientBasketHelperTest extends Command {

    const COMMAND_NAME = 'omni:client:basket-helper-test';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
            ->setDescription( 'Basket Helper Test' )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
            ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {
        # ugly hack to get ObjectManager to use autoloading
        require '/var/www/magento2/app/bootstrap.php';
        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        $objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        # get helper with enabled autoloading
        /** @var \Ls\Omni\Helper\BasketHelper $helper */
        $helper = $objectManager->get('Ls\Omni\Helper\BasketHelper');
        $itemHelper = $objectManager->get('Ls\Omni\Helper\ItemHelper');
        /** @var \Ls\Omni\Helper\ContactHelper $contactHelper */
        $contactHelper = $objectManager->get('Ls\Omni\Helper\ContactHelper');
        /** @var \Ls\Omni\Helper\OrderHelper $orderHelper */
        $orderHelper = $objectManager->get('Ls\Omni\Helper\OrderHelper');

        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $registry = $objectManager->get('\Magento\Framework\Registry');
        #$item = $itemHelper->get(66010);
        #$uom = $itemHelper->uom($item);
        #$sku = $itemHelper->sku($item);

        $loginResult = $contactHelper->login("tom", "tom.1");
        $registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
        $customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $loginResult->getDevice()->getSecurityToken());
        $customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $loginResult->getId() );

        $oneList = $helper->fetch();
        #var_dump($oneList);
        if (!is_null($oneList)) {
            #$cart = $helper->storeAsCart($oneList);
            #$availability = $helper->availability($oneList);
            #$helper->delete($oneList);
            /** @var Entity\BasketCalcResponse $result */
            $result = $helper->calculate($oneList);
            var_dump($result);

            /** @var Magento\Sales\Model\Order $order */
            $order = $objectManager->get('Magento\Sales\Model\Order');
            $order->setShippingMethod("homedelivery");

            $orderHelper->placeOrder($orderHelper->prepareOrder($order, $result));
        } else {
            throw new Exception("OneList is null");
        }
    }

}