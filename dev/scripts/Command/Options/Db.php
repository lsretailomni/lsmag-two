<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for database options
 */
class Db extends AbstractOptions
{
    const HOST = 'db-host';
    const PORT = 'db-port';
    const USER = 'db-user';
    const PASSWORD = 'db-password';
    const NAME = 'db-name';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::HOST => [
                'initial' => true,
                'default' => 'db',
                'description' => 'Magento Mysql host',
                'question' => 'Please enter magento Mysql host %default%'
            ],
            static::PORT => [
                'initial' => true,
                'default' => '3306',
                'description' => 'Magento Mysql port',
                'question' => 'Please enter magento Mysql port %default%'
            ],
            static::USER => [
                'initial' => true,
                'default' => 'root',
                'description' => 'Magento Mysql user',
                'question' => 'Please enter magento Mysql user %default%'
            ],
            static::PASSWORD => [
                'initial' => true,
                'default' => 'root',
                'description' => 'Magento Mysql password',
                'question' => 'Please enter magento Mysql password %default%'
            ],
            static::NAME => [
                'initial' => true,
                'default' => 'magento2',
                'description' => 'Magento Mysql database',
                'question' => 'Please enter magento Mysql database %default%'
            ]
        ];
    }
}
