<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Console\Command;
use Ls\Omni\Helper\BasketHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientBasketHelperTest extends Command {

    const COMMAND_NAME = 'omni:client:basket-helper-test';



    protected function configure () {

        $this->setName( self::COMMAND_NAME )
            ->setDescription( 'Basket Helper Test' )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
            ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $helper = new BasketHelper();
        $oneList = $helper->fetchOneList();
        var_dump($oneList);
    }

}