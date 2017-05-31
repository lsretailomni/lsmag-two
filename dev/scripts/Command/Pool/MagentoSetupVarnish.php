<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Pool;

use MagentoDevBox\Command\AbstractCommand;
use MagentoDevBox\Command\Options\Magento as MagentoOptions;
use MagentoDevBox\Command\Options\WebServer as WebServerOptions;
use MagentoDevBox\Command\Options\Db as DbOptions;
use MagentoDevBox\Command\Options\Varnish as VarnishOptions;
use MagentoDevBox\Library\Registry;
use MagentoDevBox\Library\Db;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Bootstrap;
use Magento\PageCache\Model\Config;

/**
 * Command for Varnish setup
 */
class MagentoSetupVarnish extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:varnish')
            ->setDescription('Setup varnish')
            ->setHelp('This command allows you to setup Varnish inside magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $markerFile = $input->getOption(VarnishOptions::MARKER_FILE);
        $generateConfig = $input->getOption(VarnishOptions::GENERATE_CONFIG);
        if (Registry::get(VarnishOptions::FPC_INSTALLED)
            || !$this->requestOption(VarnishOptions::FPC_SETUP, $input, $output)) {

            if ($markerFile && file_exists($markerFile) && $generateConfig) {
                unlink($markerFile);
            }
            return;
        }

        $varnishHost = $this->requestOption(VarnishOptions::HOST, $input, $output);

        $this->setHttpCacheHost($input, $output, $varnishHost);
        $this->saveConfig($input, $output);

        if ($markerFile && $generateConfig) {
            touch($markerFile);
            $this->generateConfig($input, $output, $varnishHost);
        }

        Registry::set(MagentoOptions::PORT, $this->requestOption(VarnishOptions::HOME_PORT, $input, $output));
        Registry::set(VarnishOptions::FPC_INSTALLED, true);
        Registry::set(VarnishOptions::HOST, $varnishHost);
    }

    /**
     * Customize varnish timeout
     *
     * @param $content
     * @return string
     */
    private function customizeTimeOut($content)
    {
        $content = preg_replace(
            "/(\.port\s\=\s\"80\"\;\n\})/",
            "$1\n\nbackend web_setup {\n    .host = \"web\";\n    .port = \"80\";\n    .first_byte_timeout = 600s;\n}",
            $content
        );

        $content = preg_replace(
            '/(sub vcl_recv\s\{)/',
            "$1\n    set req.backend_hint = default;\n    if (req.url ~ \"/setup\") {\n"
            . "        set req.backend_hint = web_setup;\n    }",
            $content
        );

        return $content;
    }

    /**
     * Save config for Magento
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function saveConfig(InputInterface $input, OutputInterface $output)
    {
        $dbConnection = Db::getConnection(
            $input->getOption(DbOptions::HOST),
            $input->getOption(DbOptions::USER),
            $input->getOption(DbOptions::PASSWORD),
            $input->getOption(DbOptions::NAME)
        );

        $dbConnection->exec(
            'DELETE FROM core_config_data'
                . ' WHERE path = "system/full_page_cache/caching_application" '
                . ' OR path LIKE "system/full_page_cache/varnish/%";'
        );

        $config = [
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/caching_application',
                'value' => 2
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/access_list',
                'value' => $this->requestOption(WebServerOptions::HOST, $input, $output)
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/backend_host',
                'value' => $this->requestOption(WebServerOptions::HOST, $input, $output)
            ],
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'system/full_page_cache/varnish/backend_port',
                'value' => $this->requestOption(WebServerOptions::PORT, $input, $output)
            ]
        ];

        $statement = $dbConnection->prepare(
            'INSERT INTO core_config_data (scope, scope_id, path, `value`) VALUES (:scope, :scope_id, :path, :value);'
        );

        foreach ($config as $item) {
            $statement->bindParam(':scope', $item['scope']);
            $statement->bindParam(':scope_id', $item['scope_id']);
            $statement->bindParam(':path', $item['path']);
            $statement->bindParam(':value', $item['value']);
            $statement->execute();
        }

        $this->executeCommands(
            sprintf(
                'cd %s && php bin/magento cache:clean config',
                $this->requestOption(MagentoOptions::PATH, $input, $output)
            ),
            $output
        );

        $homePort = $this->requestOption(VarnishOptions::HOME_PORT, $input, $output);
        $magentoHost = $input->getOption('magento-host');
        $options = [
            'web/unsecure/base_url' => 'http',
            'web/secure/base_url' => 'https'
        ];

        foreach ($options as $optionPath => $protocol) {
            $statement = $dbConnection->prepare(
                'UPDATE `core_config_data` SET `value`=:url WHERE `path`=:path'
            );
            $statement->bindParam(':url', sprintf('%s://%s:%s', $protocol, $magentoHost, $homePort));
            $statement->bindParam(':path', $optionPath);
            $statement->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            VarnishOptions::FPC_SETUP => VarnishOptions::get(VarnishOptions::FPC_SETUP),
            VarnishOptions::CONFIG_PATH => VarnishOptions::get(VarnishOptions::CONFIG_PATH),
            VarnishOptions::HOME_PORT => VarnishOptions::get(VarnishOptions::HOME_PORT),
            VarnishOptions::HOST => VarnishOptions::get(VarnishOptions::HOST),
            VarnishOptions::MARKER_FILE => VarnishOptions::get(VarnishOptions::MARKER_FILE),
            WebServerOptions::HOST => WebServerOptions::get(WebServerOptions::HOST),
            WebServerOptions::PORT => WebServerOptions::get(WebServerOptions::PORT),
            DbOptions::HOST => DbOptions::get(DbOptions::HOST),
            DbOptions::PORT => DbOptions::get(DbOptions::PORT),
            DbOptions::USER => DbOptions::get(DbOptions::USER),
            DbOptions::PASSWORD => DbOptions::get(DbOptions::PASSWORD),
            DbOptions::NAME => DbOptions::get(DbOptions::NAME),
            MagentoOptions::HOST => MagentoOptions::get(MagentoOptions::HOST),
            MagentoOptions::PATH => MagentoOptions::get(MagentoOptions::PATH),
            VarnishOptions::GENERATE_CONFIG => VarnishOptions::get(VarnishOptions::GENERATE_CONFIG)
        ];
    }

    /**
     * Set HTTP cache host
     *
     * Command php bin/magento setup:config:set --http-cache-hosts=%s:6081' is not used by intense
     * because of bug in 2.0.0 branch which corrupt structure of env.php
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function setHttpCacheHost(InputInterface $input, OutputInterface $output, $varnishHost)
    {
        $envPath = sprintf('%s/app/etc/env.php', $this->requestOption(MagentoOptions::PATH, $input, $output));
        $env = include $envPath;
        $env['http_cache_hosts'][] = [
            'host' => $varnishHost,
            'port' => '6081',
        ];
        file_put_contents($envPath, sprintf("<?php\n return %s;", var_export($env, true)));
    }

    /**
     * Generate varnish config
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $varnishHost
     */
    private function generateConfig(InputInterface $input, OutputInterface $output, $varnishHost)
    {
        require_once sprintf('%s/app/bootstrap.php', $this->requestOption('magento-path', $input, $output));

        $bootstrap = Bootstrap::create(BP, $_SERVER);
        $objectManager = $bootstrap->getObjectManager();
        /** @var Config $config */
        $config = $objectManager->get(Config::class);
        $content = $config->getVclFile(Config::VARNISH_4_CONFIGURATION_PATH);
        $content = $this->customizeTimeOut($content);
        file_put_contents($this->requestOption(VarnishOptions::CONFIG_PATH, $input, $output), $content);
    }
}
