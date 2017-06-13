<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for web server options
 */
class WebServer extends AbstractOptions
{
    const HOST = 'webserver-host';
    const PORT = 'webserver-port';
    const HOME_PORT = 'webserver-home-port';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::HOST => [
                'default' => 'web',
                'description' => 'Web server host.',
                'question' => 'Please enter web server host %default%'
            ],
            static::PORT => [
                'default' => '80',
                'description' => 'Web server port.',
                'question' => 'Please enter web server port %default%'
            ],
            static::HOME_PORT => [
                'default' => '10080',
                'description' => 'Web server port for the home machine.',
                'question' => 'Please enter web server port for the home machine'
                    . '. See docker-compose.yml -> web -> ports -> xxxx:80, where xxxx - is home port %default%'
            ]
        ];
    }
}
