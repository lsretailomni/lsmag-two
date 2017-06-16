<?php
namespace Ls\Omni\Console\Command;

use Composer\Autoload\ClassLoader;
use Ls\Omni\Code\ClassMapGenerator;
use Ls\Omni\Code\EntityGenerator;
use Ls\Omni\Code\OperationGenerator;
use Ls\Omni\Code\RestrictionGenerator;
use Ls\Omni\Console\Command;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\Soap\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ClientGenerate extends Command
{
    const COMMAND_NAME = 'omni:client:generate';

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
             ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $fs = new Filesystem();
        $cwd = getcwd();

        $wsdl = Service::getUrl( $this->type, $this->base_url );
        $client = new Client( $wsdl, $this->type );
        $metadata = $client->getMetadata();
        $restrictions = array_keys( $metadata->getRestrictions() );

        /** @var ClassLoader $loader */
        $loader = $GLOBALS[ 'loader' ];

        $interface_folder = ucfirst( $this->type->getValue() );

        // single line that is going for the composer autoloader to grab the location of our namespace + client
        $base_dir = $this->path( $loader->getPrefixesPsr4()[ 'Ls\\Omni\\' ][ 0 ], 'Client', $interface_folder );
        $operation_dir = $this->path( $base_dir, 'Operation' );
        $entity_dir = $this->path( $base_dir, 'Entity' );
        $this->clean( $base_dir );

        foreach ( $metadata->getEntities() as $entity ) {
            // RESTRICTIONS ARE CREATED IN ANOTHER LOOP SO WE FILTER THEM OUT
            if ( array_search( $entity->getName(), $restrictions ) === FALSE ) {
                $filename = $this->path( $entity_dir, "{$entity->getName()}.php" );
                $generator = new EntityGenerator( $entity, $metadata );
                $content = $generator->generate();
                file_put_contents( $filename, $content );

                $ok = sprintf( 'generated entity ( %1$s )', $fs->makePathRelative( $filename, $cwd ) );
                $this->output->writeln( $ok );
            }
        }

        $restriction_blacklist = [ 'char', 'duration', 'guid', 'StreamBody' ];
        foreach ( $metadata->getRestrictions() as $restriction ) {
            if ( array_search( $restriction->getName(), $restriction_blacklist ) === FALSE ) {
                $filename = $this->path( $entity_dir, 'Enum', "{$restriction->getName()}.php" );
                $generator = new RestrictionGenerator( $restriction, $metadata );
                $content = $generator->generate();
                file_put_contents( $filename, $content );

                $ok = sprintf( 'generated restriction ( %1$s )', $fs->makePathRelative( $filename, $cwd ) );
                $this->output->writeln( $ok );
            }
        }

        foreach ( $metadata->getOperations() as $operation ) {
            $filename = $this->path( $operation_dir, "{$operation->getName()}.php" );
            $generator = new OperationGenerator( $operation, $metadata );
            $content = $generator->generate();
            file_put_contents( $filename, $content );

            $ok = sprintf( 'generated operation ( %1$s )', $fs->makePathRelative( $filename, $cwd ) );
            $this->output->writeln( $ok );
        }

        $filename = $this->path( $base_dir, 'ClassMap.php' );
        $generator = new ClassMapGenerator( $metadata );
        $content = $generator->generate();
        file_put_contents( $filename, $content );

        $ok = sprintf( 'generated classmap ( %1$s )', $fs->makePathRelative( $filename, $cwd ) );
        $this->output->writeln( $ok );
        $this->output->writeln( '- - - - - - - - - - ' );
        $this->output->writeln( 'OK' );
    }

    /**
     * @param string $folder
     */
    private function clean ( $folder ) {

        $fs = new Filesystem();

        if ( $fs->exists( $folder ) ) $fs->remove( $folder );
        $fs->mkdir( $this->path( $folder, 'Operation' ) );
        $fs->mkdir( $this->path( $folder, 'Entity', 'Enum' ) );

        $ok = sprintf( 'done cleaning folder ( %1$s )', $fs->makePathRelative( $folder, getcwd() ) );
        $this->output->writeln( $ok );

    }
}
