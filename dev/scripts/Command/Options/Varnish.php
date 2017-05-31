<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Varnish options
 */
class Varnish extends AbstractOptions
{
    const FPC_INSTALLED = 'fpc-installed';
    const FPC_SETUP = 'varnish-fpc-setup';
    const CONFIG_PATH = 'varnish-config-path';
    const HOME_PORT = 'varnish-home-port';
    const HOST = 'varnish-host';
    const MARKER_FILE = 'varnish-marker-file';
    const GENERATE_CONFIG = 'generate-varnish-config';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::FPC_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => static::getDefaultValue('USE_VARNISH', true),
                'description' => 'Whether to use Varnish as Magento full page cache.',
                'question' => 'Do you want to use Varnish as Magento full page cache? %default%'
            ],
            static::CONFIG_PATH => [
                'default' => '/home/magento2/configs/varnish/default.vcl',
                'description' => 'Configuration file path for Varnish.',
                'question' => 'Please enter configuration file path for Varnish %default%'
            ],
            static::HOME_PORT => [
                'default' => 1749,
                'description' => 'Varnish port on home machine.',
                'question' => 'Please enter Varnish port on home machine.'
                    . ' See docker-compose.yml -> varnish -> ports -> xxxx:6081, where xxxx is port on home  %default%'
            ],
            static::MARKER_FILE => [
                'default' =>   '/home/magento2/configs/varnish/varnish_used',
                'description' => 'Varnish usage marker file',
                'question' => 'Please enter file that will serve as Varnish usage marker %default%'
            ],
            static::HOST => [
                'default' => 'varnish',
                'description' => 'Varnish host',
                'question' => 'Please enter Varnish host %default%'
            ],
            static::GENERATE_CONFIG => [
                'boolean' => true,
                'default' => static::getDefaultValue('GENERATE_VARNISH_CONFIG', false),
                'description' => 'Generate Varnish config.',
                'question' => 'Do you wish to generate varnish config? %default%'
            ]
        ];
    }
}
