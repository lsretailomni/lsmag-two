<?php
namespace Ls\Omni\Console;

use Ls\Omni\Exception\InvalidServiceType;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    const TYPE = 'type';
    const BASE_URL = 'base';

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var ServiceType */
    protected $type;
    /** @var string */
    protected $base_url;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws InvalidServiceType
     */
    protected function initialize ( InputInterface $input, OutputInterface $output ) {

        parent::initialize( $input, $output );

        $this->input = $input;
        $this->output = $output;

        if ( is_null( $this->type ) ) {
            $type = $input->getOption( self::TYPE );
            if ( !ServiceType::isValid( $type ) ) throw new InvalidServiceType();
            $this->type = new ServiceType( $type );
        }

        $this->base_url = $input->getOption( self::BASE_URL );
        !empty( $this->base_url ) or $this->base_url = Service::DEFAULT_BASE_URL;
    }

    /**
     * @param string $path,...
     *
     * @return string
     */
    protected function path ( $path ) {
        $parts = func_get_args();

        return join( DIRECTORY_SEPARATOR, $parts );
    }
}
