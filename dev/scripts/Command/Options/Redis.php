<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Redis options
 */
class Redis extends AbstractOptions
{
    const FPC_INSTALLED = 'fpc-installed';
    const FPC_SETUP = 'redis-fpc-setup';
    const CACHE_SETUP = 'redis-cache-setup';
    const SESSION_SETUP = 'redis-session-setup';
    const HOST = 'redis-host';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::CACHE_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => static::getDefaultValue('USE_REDIS_CACHE', true),
                'description' => 'Whether to use Redis as Magento default cache.',
                'question' => 'Do you want to use Redis as Magento default cache? %default%'
            ],
            static::SESSION_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => static::getDefaultValue('USE_REDIS_SESSIONS', true),
                'description' => 'Whether to use Redis for storing sessions.',
                'question' => 'Do you want to use Redis for storing sessions? %default%'
            ],
            static::FPC_SETUP => [
                'boolean' => true,
                'default' => static::getDefaultValue('USE_REDIS_FULL_PAGE_CACHE', false),
                'description' => 'Whether to use Redis as Magento full page cache.',
                'question' => 'Do you want to use Redis as Magento full page cache? %default%'
            ],
            static::HOST => [
                'initial' => true,
                'default' => 'redis',
                'requireValue' => false,
                'description' => 'Redis host.',
                'question' => 'Please enter Redis host %default%'
            ]
        ];
    }
}
