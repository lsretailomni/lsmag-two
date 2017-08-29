<?php
namespace Ls\Core\Console;

use Ls\Omni\Console\Command\ClientContactToken;
use Ls\Omni\Console\Command\ClientContactSearch;
use Ls\Omni\Console\Command\ClientGenerate;
use Ls\Omni\Console\Command\ClientPing;
use Ls\Omni\Console\Command\ClientWsdl;
use Ls\Omni\Console\Command\ClientBasketHelperTest;
use Ls\Replication\Console\Command\ReplicationGenerate;
use Ls\Replication\Console\Command\ReplicationTest;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    const APP_NAME = 'ls-mag';
    const APP_VERSION = '1.0.0';

    public function __construct () {
        parent::__construct( self::APP_NAME, self::APP_VERSION );
    }

    protected function getDefaultCommands () {
        $commands = parent::getDefaultCommands();

        $commands[] = new ClientWsdl();
        $commands[] = new ClientGenerate();
        $commands[] = new ClientPing();
        $commands[] = new ClientContactSearch();
        $commands[] = new ClientContactToken();
        $commands[] = new ReplicationGenerate();
        $commands[] = new ReplicationTest();
        $commands[] = new ClientBasketHelperTest();

        return $commands;
    }
}
