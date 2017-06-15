<?php
namespace Ls\Omni\Console\Command;

use Ls\Omni\Client\Ecommerce\Operation\LoginWeb;
use Ls\Omni\Client\IOperation;
use Ls\Omni\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Code\Reflection\ClassReflection;

class ClientContactToken extends Command
{
    const COMMAND_NAME = 'omni:client:token';

    protected function configure () {

        $this->setName( self::COMMAND_NAME )
             ->setDescription( 'show WSDL contents' )
             ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce' )
             ->addOption( 'username', 'u', InputOption::VALUE_REQUIRED, 'member username', 'tom' )
             ->addOption( 'password', 'p', InputOption::VALUE_REQUIRED, 'member password', 'tom.1' )
             ->addOption( 'base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url' );
    }

    protected function execute ( InputInterface $input, OutputInterface $output ) {

        $username = $this->input->getOption( 'username' );
        $password = $this->input->getOption( 'password' );
        /** @var LoginWeb $login_web */
        $login_web = new LoginWeb();
        $login_web->getOperationInput()
                  ->setUserName( $username )
                  ->setPassword( $password );
        $response = $login_web->execute();

        if ( $response != NULL ) {
            $result = $response->getResult();
            $token = $result->getDevice()->getSecurityToken();

            $this->output->writeln( "LOGIN OK ( $username , $password ) - $token" );

            $fs = new Filesystem();
            $anchor = new ClassReflection( LoginWeb::class );
            $base_namespace = $anchor->getNamespaceName();
            $filename = $anchor->getFileName();
            $folder = dirname( $filename );
            $operation_files = glob( $folder . DIRECTORY_SEPARATOR . '*' );
            $token_error = '/.*SecurityToken do not match.*/';
            $object_reference = '/.*Object reference not set to an instance of an object.*/';
            $known_errors = [
                'Object reference not set to an instance of an object.',
            ];
            $operations = [ ];

            foreach ( $operation_files as $operation_file ) {
                $operation_class = str_replace( '.php', '', $fs->makePathRelative( $operation_file, $folder ) );
                $operation_class_fqn = $base_namespace . '\\' . substr( $operation_class, 0, -1 );

                /** @var IOperation $operation */
                $operation = new $operation_class_fqn();
                try {
                    $operation->execute();
//                    $this->output->writeln( "SUCCESS $operation_class" );
                } catch ( \Exception $e ) {
                    $message = $e->getMessage();
                    if ( preg_match( $token_error, $message ) ) {
                        $operations[] = $operation_class;
                    } elseif ( preg_match($object_reference, $message) ) {
                    } else {
                        $this->output->writeln( "ERROR $operation_class" );
                        $this->output->writeln( $message );
                        $this->output->writeln( '- - - - -' );
                    }
                }
            }

            $this->output->writeln( 'TOKENIZED OPERATIONS: ' );
            foreach ( $operations as $operation ) {
                $this->output->writeln( $operation );
            }
            $this->output->writeln( '- - - - -' );


//            $onelist_by_cid = new OneListGetByContactId();
//            $onelist_by_cid->getOperationInput()
//                           ->setContactId( $result->getId() )
//                           ->setListType( ListType::WISH )
//                           ->setIncludeLines( FALSE );
//
//            try {
//                $onelist_response = $onelist_by_cid->setToken( $token )
//                                                   ->execute();
//                $onelist_result = $onelist_response->getResult();
//                $how_many = count( $onelist_result->getIterator() );
//                $s = $how_many == 1 ? '' : 's';
//
//                $this->output->writeln( "FOUND $how_many result$s" );
//            } catch ( Exception $e ) {
//                $this->output->writeln( 'OOPS...' );
//                $this->output->writeln( $e->getMessage() );
//            }
        } else {
            $this->output->writeln( 'FIRST THINGS, FIRST... GIVE ME VALID CREDENTIALS' );
        }


    }
}
