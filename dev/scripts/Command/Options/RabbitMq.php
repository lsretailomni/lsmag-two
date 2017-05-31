<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for RabbitMQ options
 */
class RabbitMq extends AbstractOptions
{
    const SETUP = 'rabbitmq-setup';
    const HOST = 'rabbitmq-host';
    const PORT = 'rabbitmq-port';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => static::getDefaultValue('USE_RABBITMQ', false),
                'description' => 'Whether to install RabbitMQ.',
                'question' => 'Do you want to install RabbitMQ? %default%'
            ],
            static::HOST => [
                'requireValue' => false,
                'default' => 'rabbit',
                'description' => 'RabbitMQ host.',
                'question' => 'Please specify RabbitMQ host %default%'
            ],
            static::PORT => [
                'requireValue' => false,
                'default' => '5672',
                'description' => 'RabbitMQ port.',
                'question' => 'Please specify RabbitMQ port %default%'
            ]
        ];
    }
}
