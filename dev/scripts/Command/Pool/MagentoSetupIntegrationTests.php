<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Magento installation
 */
class MagentoSetupIntegrationTests extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:integration-tests')
            ->setDescription('Configure Magento to run integration tests')
            ->setHelp('This command allows you to configure Magento to run integration tests.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require_once sprintf(
            '%s/dev/tests/integration/framework/autoload.php',
            $this->requestOption('magento-path', $input, $output)
        );

        $dbName = 'magento_integration_tests';
        $dbUser = $input->getOption(DbOptions::USER);
        $dbPassword = $input->getOption(DbOptions::PASSWORD);
        $dbHost = $input->getOption(DbOptions::HOST);

        $this->executeCommands(
            sprintf('mysql -h db -u %s -p%s -e "CREATE DATABASE IF NOT EXISTS %s;"', $dbUser, $dbPassword, $dbName),
            $output
        );

        $magentoPath = $input->getOption(MagentoOptions::PATH);

        $sourceFile = sprintf('%s/dev/tests/integration/phpunit.xml.dist', $magentoPath);
        $targetFile = sprintf('%s/dev/tests/integration/phpunit.xml', $magentoPath);
        $this->createConfigurationFile($sourceFile, $targetFile);

        $sourceFile = sprintf('%s/dev/tests/integration/etc/config-global.php.dist', $magentoPath);
        $targetFile = sprintf('%s/dev/tests/integration/etc/config-global.php', $magentoPath);
        $this->createConfigurationFile($sourceFile, $targetFile);

        $sourceFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.php.dist', $magentoPath);
        $targetFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.php', $magentoPath);
        $this->createConfigurationFile($sourceFile, $targetFile);
        $this->updateDbCredentials($targetFile, $dbHost, $dbName, $dbUser, $dbPassword);

        $sourceFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.travis.php.dist', $magentoPath);
        $targetFile = sprintf('%s/dev/tests/integration/etc/install-config-mysql.travis.php', $magentoPath);
        $this->createConfigurationFile($sourceFile, $targetFile);
        $this->updateDbCredentials($targetFile, $dbHost, $dbName, $dbUser, $dbPassword);
    }

    /**
     * Create configuration file from *.dist source
     * In case if configuration file don't exists
     *
     * @param string $sourceFileName
     * @param string $targetFileName
     * @return void
     */
    private function createConfigurationFile($sourceFileName, $targetFileName)
    {
        if (file_exists($sourceFileName) && !file_exists($targetFileName)) {
            $this->executeCommands(sprintf('cp %s %s', $sourceFileName, $targetFileName));
        }
    }

    /**
     * Replace database credentials in the config file
     *
     * @param string $sourceFileName
     * @param string $dbHost
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPassword
     * @return void
     */
    private function updateDbCredentials($sourceFileName, $dbHost, $dbName, $dbUser, $dbPassword)
    {
        $config = include $sourceFileName;

        $config['db-host'] = $dbHost;
        $config['db-user'] = $dbUser;
        $config['db-password'] = $dbPassword;
        $config['db-name'] = $dbName;

        file_put_contents($sourceFileName, sprintf("<?php\n return %s;", var_export($config, true)));
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD)
        ];
    }
}
