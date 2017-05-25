<?php
namespace Ls\Replication\Console\Command;


use Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use Ls\Omni\Client\Ecommerce\Operation\ReplEcommAttribute;
use Ls\Omni\Client\Ecommerce\Operation\ReplEcommItems;
use Ls\Omni\Console\Command as OmniCommand;
use Ls\Omni\Exception\InvalidServiceTypeException;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicationTest extends OmniCommand
{
    const COMMAND_NAME = 'replication:test';

    const STORE = 'store';
    const BATCH_SIZE = 'batchsize';
    /** @var  string */
    private $store;
    /** @var int */
    private $batch_size = 10;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws InvalidServiceTypeException
     */
    protected function initialize ( InputInterface $input, OutputInterface $output ) {

        $this->input = $input;
        $this->output = $output;

        $this->type = ServiceType::ECOMMERCE();
        $this->base_url = Service::DEFAULT_BASE_URL;

        $this->store = $this->input->getOption( self::STORE );
        $batch_size = $this->input->getOption( self::BATCH_SIZE );
        if ( is_numeric( $batch_size ) && $batch_size > 1 ) {
            $this->batch_size = $batch_size;
        }
    }

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( self::STORE, 's', InputOption::VALUE_REQUIRED, 'omni service associated store', 'S0013' )
             ->addOption( self::BATCH_SIZE, 'r', InputOption::VALUE_OPTIONAL, 'replication batch size' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $remaining = INF;
        $last_key = NULL;
        while ( $remaining != 0 ) {
            $operation = new ReplEcommItems();
            $repl_request = new ReplRequest();
            $repl_request->setBatchSize( $this->batch_size )
                         ->setFullReplication( TRUE )
                         ->setStoreId( $this->store );
            if ( isset( $result ) ) {
                $repl_request->setLastKey( $last_key );
            }

            $operation->getOperationInput()->setReplRequest( $repl_request );
            $response = $operation->execute();
            $result = $response->getResult();

            $remaining = $result->getRecordsRemaining();
            $last_key = $result->getLastKey();

            $this->output->writeln( "RECORDS REMAINING : $remaining" );
        }
        $this->output->writeln( '- - - - - -' );
        $this->output->writeln( 'OK' );
    }
}
