<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Client\Ecommerce\Entity\Ping;
use Ls\Omni\Client\IOperation;
use Ls\Omni\Console\Command;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\Soap\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientPing extends Command
{
//    use ClassMap;
    const COMMAND_NAME = 'omni:client:ping';

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
             ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $uc_type = ucfirst( $this->type->getValue() );
        $class = "Ls\\Omni\\Client\\$uc_type\\Operation\\Ping";

        /** @var IOperation $ping */
        $ping = new $class();
        $pong = $ping->execute();

//        $wsdl = Service::getUrl( $this->type, $this->base_url );
//        $client = new Client( $wsdl, $this->type );
//        $client->setClassmap( static::getClassMap() );

//        $ping = new Ping();
//        $response = $client->ping( $ping );

        $this->output->writeln( $pong );
    }
}
