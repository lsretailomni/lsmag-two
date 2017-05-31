<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Command\Options\WebServer as WebServerOptions;
use MagentoDevBox\Command\Options\RabbitMq as RabbitMqOptions;
use MagentoDevBox\Library\Registry;
use MagentoDevBox\Library\XDebugSwitcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento installation
 */
class MagentoSetup extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup')
            ->setDescription('Install Magento')
            ->setHelp('This command allows you to install Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magentoPath = $input->getOption(MagentoOptions::PATH);

        $this->executeCommands(
            sprintf('cd %s && rm -rf var/* pub/static/* app/etc/env.php app/etc/config.php', $magentoPath)
        );

        $webserverHomePort = $this->requestOption(WebServerOptions::HOME_PORT, $input, $output);
        $magentoHost = $input->getOption(MagentoOptions::HOST);
        $magentoBackendPath = $this->requestOption(MagentoOptions::BACKEND_PATH, $input, $output);
        $magentoAdminUser = $this->requestOption(MagentoOptions::ADMIN_USER, $input, $output);
        $magentoAdminPassword = $this->requestOption(MagentoOptions::ADMIN_PASSWORD, $input, $output);

        $command = sprintf(
            'cd %s && php bin/magento setup:install'
                . ' --base-url=http://%s:%s/ --db-host=%s --db-name=%s'
                . ' --db-user=%s --db-password=%s --admin-firstname=Magento --admin-lastname=User'
                . ' --admin-email=user@example.com --admin-user=%s --admin-password=%s'
                . ' --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1'
                . ' --backend-frontname=%s',
            $magentoPath,
            $magentoHost,
            $webserverHomePort,
            $input->getOption(DbOptions::HOST),
            $input->getOption(DbOptions::NAME),
            $input->getOption(DbOptions::USER),
            $input->getOption(DbOptions::PASSWORD),
            $magentoAdminUser,
            $magentoAdminPassword,
            $magentoBackendPath
        );

        if ($this->requestOption(RabbitMqOptions::SETUP, $input, $output)) {
            $amqpModuleExist = exec(
                sprintf('cd %s && php bin/magento module:status | grep Magento_Amqp', $magentoPath)
            );

            if ($amqpModuleExist) {
                $rabbitmqHost = $this->requestOption(RabbitMqOptions::HOST, $input, $output);
                $rabbitmqPort = $this->requestOption(RabbitMqOptions::PORT, $input, $output);

                $command .= sprintf(
                    ' --amqp-virtualhost=/ --amqp-host=%s --amqp-port=%s --amqp-user=guest --amqp-password=guest',
                    $rabbitmqHost,
                    $rabbitmqPort
                );
            }
        }

        $this->executeCommands($command, $output);

        $composerAuthSourcePath = '/home/magento2/.composer/auth.json';
        $composerHomePath = sprintf('%s/var/composer_home', $magentoPath);
        $composerAuthPath = sprintf('%s/auth.json', $composerHomePath);

        if (!file_exists($composerAuthPath) && file_exists($composerAuthSourcePath)) {
            if (!file_exists($composerHomePath)) {
                mkdir($composerHomePath, 0777, true);
            }

            copy($composerAuthSourcePath, $composerAuthPath);
        }

        if (!Registry::get(MagentoOptions::SOURCES_REUSE)
            && $this->requestOption(MagentoOptions::SAMPLE_DATA_INSTALL, $input, $output)
        ) {
            XDebugSwitcher::switchOff();
            $this->executeCommands(
                [
                    sprintf('cd %s && php bin/magento sampledata:deploy', $magentoPath),
                    sprintf('cd %s && php bin/magento setup:upgrade', $magentoPath)
                ],
                $output
            );
            XDebugSwitcher::switchOn();
        }

        Registry::setData(
            [
                MagentoOptions::HOST => $magentoHost,
                MagentoOptions::PORT => $webserverHomePort,
                MagentoOptions::BACKEND_PATH => $magentoBackendPath,
                MagentoOptions::ADMIN_USER => $magentoAdminUser,
                MagentoOptions::ADMIN_PASSWORD => $magentoAdminPassword
            ]
        );

        if (!Registry::get(static::CHAINED_EXECUTION_FLAG)) {
            $output->writeln('To prepare magento sources run <info>m2init magento:finalize</info> command next');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::HOST => MagentoOptions::get(MagentoOptions::HOST),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            MagentoOptions::BACKEND_PATH => MagentoOptions::get(MagentoOptions::BACKEND_PATH),
            MagentoOptions::ADMIN_USER => MagentoOptions::get(MagentoOptions::ADMIN_USER),
            MagentoOptions::ADMIN_PASSWORD => MagentoOptions::get(MagentoOptions::ADMIN_PASSWORD),
            MagentoOptions::SAMPLE_DATA_INSTALL => MagentoOptions::get(MagentoOptions::SAMPLE_DATA_INSTALL),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME),
            WebServerOptions::HOME_PORT => WebServerOptions::get(WebServerOptions::HOME_PORT),
            RabbitMqOptions::SETUP => RabbitMqOptions::get(RabbitMqOptions::SETUP),
            RabbitMqOptions::HOST => RabbitMqOptions::get(RabbitMqOptions::HOST),
            RabbitMqOptions::PORT => RabbitMqOptions::get(RabbitMqOptions::PORT)
        ];
    }
}
