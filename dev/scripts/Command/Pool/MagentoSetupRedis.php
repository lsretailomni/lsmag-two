<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\Redis as RedisOptions;
use MagentoDevBox\Library\Registry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Redis setup
 */
class MagentoSetupRedis extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:redis')
            ->setDescription('Setup Redis for Magento')
            ->setHelp('This command allows you to setup Redis for Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $this->requestOption(RedisOptions::HOST, $input, $output);
        $configPath = sprintf('%s/app/etc/env.php', $this->requestOption(MagentoOptions::PATH, $input, $output));
        $config = include $configPath;

        if ($this->requestOption(RedisOptions::SESSION_SETUP, $input, $output)) {
            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $host,
                    'port' => '6379',
                    'password' => '',
                    'timeout' => '2.5',
                    'persistent_identifier' => '',
                    'database' => '0',
                    'compression_threshold' => '2048',
                    'compression_library' => 'gzip',
                    'log_level' => '1',
                    'max_concurrency' => '6',
                    'break_after_frontend' => '5',
                    'break_after_adminhtml' => '30',
                    'first_lifetime' => '600',
                    'bot_first_lifetime' => '60',
                    'bot_lifetime' => '7200',
                    'disable_locking' => '0',
                    'min_lifetime' => '60',
                    'max_lifetime' => '2592000'
                ]
            ];
        } else {
            $config['session'] = ['save' => 'files'];
        }

        if ($this->requestOption(RedisOptions::CACHE_SETUP, $input, $output)) {
            $config['cache']['frontend']['default'] = [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => $host,
                    'port' => '6379'
                ]
            ];
        } else {
            unset($config['cache']['frontend']['default']);
        }

        if (!Registry::get(RedisOptions::FPC_INSTALLED)
            && $this->requestOption(RedisOptions::FPC_SETUP, $input, $output)
        ) {
            $config['cache']['frontend']['page_cache'] = [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => $host,
                    'port' => '6379',
                    'database' => '1',
                    'compress_data' => '0'
                ]
            ];

            Registry::set(RedisOptions::FPC_INSTALLED, true);
        } else {
            unset($config['cache']['frontend']['page_cache']);
        }

        file_put_contents($configPath, sprintf("<?php\n return %s;", var_export($config, true)));
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            RedisOptions::FPC_SETUP => RedisOptions::get(RedisOptions::FPC_SETUP),
            RedisOptions::CACHE_SETUP => RedisOptions::get(RedisOptions::CACHE_SETUP),
            RedisOptions::SESSION_SETUP => RedisOptions::get(RedisOptions::SESSION_SETUP),
            RedisOptions::HOST => RedisOptions::get(RedisOptions::HOST)
        ];
    }
}
