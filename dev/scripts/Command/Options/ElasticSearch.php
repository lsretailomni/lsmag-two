<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for ElasticSearch options
 */
class ElasticSearch extends AbstractOptions
{
    const ES_SETUP = 'elastic-setup';
    const HOST = 'elastic-host';
    const PORT = 'elastic-port';
    const ELASTIC_MODULE_NAME = 'Magento_Elasticsearch';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::ES_SETUP => [
                'initial' => true,
                'boolean' => true,
                'default' => static::getDefaultValue('USE_ELASTICSEARCH', false),
                'description' => 'Whether to use ElasticSearch as the search engine.',
                'question' => 'Do you want to use ElasticSearch as the Magento search engine? %default%'
            ],
            static::HOST => [
                'initial' => true,
                'default' => 'elasticsearch',
                'description' => 'Magento ElasticSearch host.',
                'question' => 'Please enter magento ElasticSearch host %default%'
            ],
            static::PORT => [
                'initial' => true,
                'default' => '9200',
                'description' => 'Magento ElasticSearch port.',
                'question' => 'Please enter magento ElasticSearch port %default%'
            ]
        ];
    }
}
