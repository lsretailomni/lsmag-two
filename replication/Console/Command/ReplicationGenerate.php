<?php
namespace Ls\Replication\Console\Command;


use Ls\Omni\Client\Code\AbstractGenerator;
use Ls\Omni\Console\Command as OmniCommand;
use Ls\Omni\Exception\InvalidServiceTypeException;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Omni\Service\Soap\Element;
use Ls\Omni\Service\Soap\Operation;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicationGenerate extends OmniCommand
{
    const COMMAND_NAME = 'replication:generate';
    private static $known_result_properties = [ 'LastKey', 'MaxKey', 'RecordsRemaining' ];

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws InvalidServiceTypeException
     */
    protected function initialize ( InputInterface $input, OutputInterface $output ) {
        $this->type = ServiceType::ECOMMERCE();
        parent::initialize( $input, $output );
    }

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( self::BASE_URL, 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $url = Service::getUrl( $this->type, $this->base_url );
        $client = new Client( $url, $this->type );
        $metadata = $client->getMetadata();

        /** @var Operation $operation */
        foreach ( $metadata->getOperations() as $operation_name => $operation ) {
            if ( strpos( $operation_name, 'ReplEcomm' ) !== FALSE ) {
                $this->processOperation( $operation );
            }
        }
        $this->output->writeln( '- - - - - -' );
        $this->output->writeln( 'OK' );
    }

    /**
     * @param Operation $operation
     */
    private function processOperation ( Operation $operation ) {
        $this->output->writeln( "PROCESSING REPLICATION JOB - {$operation->getName()}" );

        $main_entity = $this->discoverMainEntity( $operation->getResponse() );
        $this->updateInstall( $main_entity );
        $this->createResource( $main_entity );
        $this->createReplicationJob( $operation );

        $this->output->writeln( '- - - - -' );
//        $generator = new JobGenerator( $main_entity );
    }

    /**
     * @param Element $response
     *
     * @return string
     */
    private function discoverMainEntity ( Element $response ) {
        $base_namespace =
            AbstractGenerator::fqn( 'Ls', 'Omni', 'Client', ucfirst( $this->type->getValue() ), 'Entity' );
        $this->output->writeln( "\tDISCOVERING MAIN ENTITY - {$response->getName()}" );
        $response_fqn = AbstractGenerator::fqn( $base_namespace, $response->getName() );
        $response_reflection = new ReflectionClass( $response_fqn );
        $result_docbblock = $response_reflection->getMethod( 'getResult' )->getDocComment();

        preg_match( '/@return\s(:?[\w]+)/', $result_docbblock, $matches );
        $result_fqn = AbstractGenerator::fqn( $base_namespace, $matches[ 1 ] );
        $result_reflection = new ReflectionClass( $result_fqn );

        foreach ( $result_reflection->getProperties() as $array_of ) {
            // FILTER OUT THE MAIN ARRAY_OF ENTITY
            if ( array_search( $array_of->getName(), self::$known_result_properties ) === FALSE ) {
                break;
            }
        }
        $array_of_docblock = $array_of->getDocComment();
        preg_match( '/@property\s(:?[\w]+)\s(:?\$[\w]+)/', $array_of_docblock, $matches );
        $array_of_fqn = AbstractGenerator::fqn( $base_namespace, $matches[ 1 ] );
        $array_of_reflection = new ReflectionClass( $array_of_fqn );

        // DRILL INTO THE MAIN ENTIY
        $main_entity = array_pop( $array_of_reflection->getProperties() );
        $main_entity_docblock = $main_entity->getDocComment();
        preg_match( '/@property\s(:?[\w]+)\[\]\s(:?\$[\w]+)/', $main_entity_docblock, $matches );
        $main_entity_fqn = AbstractGenerator::fqn( $base_namespace, $matches[ 1 ] );

        return get_class( $entity = new $main_entity_fqn() );
    }
}
