<?php
namespace Ls\Replication\Console\Command;


use Composer\Autoload\ClassLoader;
use Ls\Omni\Client\Code\AbstractGenerator;
use Ls\Omni\Console\Command as OmniCommand;
use Ls\Omni\Exception\InvalidServiceTypeException;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Omni\Service\Soap\Element;
use Ls\Omni\Service\Soap\Operation;
use Ls\Replication\Job\Code\CronJobGenerator;
use Ls\Replication\Job\Code\ModelGenerator;
use Ls\Replication\Job\Code\ModelInterfaceGenerator;
use Ls\Replication\Job\Code\ModuleVersionGenerator;
use Ls\Replication\Job\Code\RepositoryGenerator;
use Ls\Replication\Job\Code\RepositoryInterfaceGenerator;
use Ls\Replication\Job\Code\ResourceCollectionGenerator;
use Ls\Replication\Job\Code\ResourceModelGenerator;
use Ls\Replication\Job\Code\SchemaUpdateGenerator;
use Ls\Replication\Job\Code\SystemConfigGenerator;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplicationGenerate extends OmniCommand
{
    const COMMAND_NAME = 'replication:generate';

    private static $known_result_properties = [ 'LastKey', 'MaxKey', 'RecordsRemaining' ];
    /** @var Metadata */
    private $metadata;
    /** @var  ClassLoader */
    private $loader;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws InvalidServiceTypeException
     */
    protected function initialize ( InputInterface $input, OutputInterface $output ) {
        $this->type = ServiceType::ECOMMERCE();
        parent::initialize( $input, $output );

        $client = new Client( Service::getUrl( $this->type, $this->base_url ), $this->type );
        $this->metadata = $client->getMetadata();
        $this->loader = $GLOBALS[ 'loader' ];
    }

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( self::BASE_URL, 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {


        /** @var Operation $operation */
        foreach ( $this->metadata->getOperations() as $operation_name => $operation ) {
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

        $replication_base_path = $this->loader->getPrefixesPsr4()[ 'Ls\\Replication\\' ][ 0 ];
        $magento_base_path = $this->loader->getPrefixesPsr4()[ 'Magento\\Framework\\' ][ 0 ];

        $module_xml_path = $this->path( $replication_base_path, 'etc', 'module.xml' );
        $module_xsd_path = $this->path( $magento_base_path, 'Module', 'etc', 'module.xsd' );

        $module_version = new ModuleVersionGenerator( $module_xml_path, $module_xsd_path );
        $version = $module_version->getVersion();

        $schema_update_generator = new SchemaUpdateGenerator( $main_entity, $version );
        $table_name = $schema_update_generator->getTableName();
        $model_interface_generator = new ModelInterfaceGenerator( $main_entity );
        $repository_interface_generator = new RepositoryInterfaceGenerator( $main_entity );
        $model_generator = new ModelGenerator( $main_entity, $table_name );
        $repository_generator = new RepositoryGenerator( $main_entity, $table_name );
        $resource_model_generator = new ResourceModelGenerator( $main_entity, $table_name );
        $resource_collection_generator = new ResourceCollectionGenerator( $main_entity, $table_name );
        $system_config = new SystemConfigGenerator( $main_entity );
        $cron_job = new CronJobGenerator( $main_entity );

//        file_put_contents( $module_xml_path, $module_version->generate() );
        file_put_contents( $schema_update_generator->getPath(), $schema_update_generator->generate() );
        file_put_contents( $model_interface_generator->getPath(), $model_interface_generator->generate() );
        file_put_contents( $repository_interface_generator->getPath(), $repository_interface_generator->generate() );
        file_put_contents( $model_generator->getPath(), $model_generator->generate() );
        file_put_contents( $repository_generator->getPath(), $repository_generator->generate() );
        file_put_contents( $resource_model_generator->getPath(), $resource_model_generator->generate() );
        file_put_contents( $resource_collection_generator->getPath(), $resource_collection_generator->generate() );

        $this->output->writeln( '- - - - -' );
//        $this->output->writeln( 'OK' );
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

        $array_of = NULL;
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
        $array_of_properties = $array_of_reflection->getProperties();
        /** @var \ReflectionProperty $main_entity */
        $main_entity = array_pop( $array_of_properties );
        $main_entity_docblock = $main_entity->getDocComment();
        preg_match( '/@property\s(:?[\w]+)\[\]\s(:?\$[\w]+)/', $main_entity_docblock, $matches );
        $main_entity_fqn = AbstractGenerator::fqn( $base_namespace, $matches[ 1 ] );

        return get_class( $entity = new $main_entity_fqn() );
    }
}
