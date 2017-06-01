<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Client\Ecommerce\Entity\ContactPOS;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ContactSearchType;
use Ls\Omni\Client\Ecommerce\Operation\ContactSearch;
use Ls\Omni\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientContactSearch extends Command
{
//    use ClassMap;
    const COMMAND_NAME = 'omni:client:contact-search';

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
             ->addOption( 'search', 's', InputOption::VALUE_REQUIRED, 'member username to search', 'tom' )
             ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $uc_type = ucfirst( $this->type->getValue() );
        $class = "Ls\\Omni\\Client\\$uc_type\\Operation\\ContactSearch";

        /** @var ContactSearch $contact_search */
        $contact_search = new $class();
        $contact_search->getOperationInput()
                       ->setSearchType( ContactSearchType::NAME )
                       ->setMaxNumberOfRowsReturned( 1 )
                       ->setSearch( $this->input->getOption( 'search' ) );
        $result = $contact_search->execute();
        $result = $result->getResult();
        $contact_pos = $result->getContactPOS();
        if ( $contact_pos instanceof ContactPOS ) {
            $this->output->writeln( 'MEMBER ID: ' . $contact_pos->getId() );
            $this->output->writeln( 'ACCOUNT ID: ' . $contact_pos->getAccount()->getId() );
            $this->output->writeln( 'CARD ID: ' . $contact_pos->getCard()->getId() );
        } else {
            $this->output->writeln( 'NO LUCK THIS TIME' );
        }
    }
}
