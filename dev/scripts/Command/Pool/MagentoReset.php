<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Command\Options\Varnish;
use MagentoDevBox\Command\Options\WebServer as WebServerOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoDevBox\Library\Registry;
use MagentoDevBox\Library\Db;

/**
 * Command for Magento final steps
 */
class MagentoReset extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:reset')
            ->setDescription('Reset Magento after docker compose restart')
            ->setHelp('This command updates magento port and flushes the cache.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbConnection = Db::getConnection(
            $input->getOption(DbOptions::HOST),
            $input->getOption(DbOptions::USER),
            $input->getOption(DbOptions::PASSWORD),
            $input->getOption(DbOptions::NAME)
        );
        $magentoPath = $input->getOption(MagentoOptions::PATH);
        $port = $this->requestOption(WebServerOptions::HOME_PORT, $input, $output);
        $magentoUrl = sprintf(
            'http://%s:%s',
            $this->requestOption(MagentoOptions::HOST, $input, $output),
            $port
        );
        $dbConnection->exec(
            sprintf(
                'UPDATE core_config_data'
                . ' SET value = "%s" '
                . ' WHERE path = "web/unsecure/base_url";',
                $magentoUrl
            )
        );

        exec(
            sprintf('cd %s && php bin/magento cache:flush', $magentoPath)
        );

        $output->writeln(
            sprintf(
                'Magento port reset after docker-compose restart. To open magento go to <info>%s</info>',
                $magentoUrl
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            WebServerOptions::HOME_PORT => WebServerOptions::get(WebServerOptions::HOME_PORT),
            MagentoOptions::HOST => MagentoOptions::get(MagentoOptions::HOST),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME),
        ];
    }
}
