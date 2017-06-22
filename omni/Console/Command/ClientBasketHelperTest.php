<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Console\Command;
use Ls\Omni\Helper\BasketHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Bootstrap;

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
        $helper = $objectManager->get('Ls\Omni\Helper\BasketHelper');
        $itemHelper = $objectManager->get('Ls\Omni\Helper\ItemHelper');
        #$item = $itemHelper->get(66010);
        #$uom = $itemHelper->uom($item);
        #$sku = $itemHelper->sku($item);

        $oneList = $helper->fetch();
        #var_dump($oneList);
        if (!is_null($oneList)) {
            #$cart = $helper->storeAsCart($oneList);
            #$availability = $helper->availability($oneList);
            #$helper->delete($oneList);
        } else {
            throw new Exception("OneList is null");
        }
    }

}