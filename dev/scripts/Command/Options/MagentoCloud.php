<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Container for Magento Cloud options
 */
class MagentoCloud extends AbstractOptions
{
    const INSTALL = 'magento-cloud-install';
    const KEY_REUSE = 'magento-cloud-key-reuse';
    const KEY_CREATE = 'magento-cloud-key-create';
    const KEY_NAME = 'magento-cloud-key-name';
    const KEY_SWITCH = 'magento-cloud-key-switch';
    const KEY_ADD = 'magento-cloud-key-add';
    const PROJECT = 'magento-cloud-project';
    const PROJECT_SKIP = 'magento-cloud-project-skip';
    const BRANCH = 'magento-cloud-branch';
    const BRANCH_SKIP = 'magento-cloud-branch-skip';
    const SILENT_INSTALL = 'magento-cloud-silent-install';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::INSTALL => [
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to get sources from Magento Cloud.',
                'question' => 'Do you want to initialize from Magento Cloud? %default%'
            ],
            static::SILENT_INSTALL => [
                'boolean' => true,
                'default' => static::getDefaultValue('MAGENTO_DOWNLOAD_SOURCES_CLOUD', false),
                'description' => 'Whether to get sources from Magento Cloud silently (without interaction).',
                'question' => 'Do you want to initialize from Magento Cloud silently (without interaction)? %default%'
            ],
            static::KEY_REUSE => [
                'boolean' => true,
                'default' => !static::getDefaultValue('MCLOUD_GENERATE_NEW_TOKEN', false),
                'description' => 'Whether to use existing SSH key from a local file.',
                'question' => 'Do you want to use existing SSH key from a local file? %default%'
            ],
            static::KEY_CREATE => [
                'boolean' => true,
                'default' => static::getDefaultValue('MCLOUD_GENERATE_NEW_TOKEN', true),
                'description' => 'Do you want to create new SSH key?',
                'question' => 'Do you want to create new SSH key? %default%'
            ],
            static::KEY_NAME => [
                'default' => static::getDefaultValue('MCLOUD_KEY_NAME', 'id_rsa'),
                'description' => 'Name of the local file with SSH key to use with Magento Cloud.',
                'question' => 'What is the name of the local file with SSH key to use with Magento Cloud? %default%'
            ],
            static::KEY_SWITCH => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'File with the key does not exists, do you want to enter different name? %default%'
            ],
            static::KEY_ADD => [
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to add SSH key from created local file to Magento Cloud account.',
                'question' => 'Do you want to add SSH key from created local file to your Magento Cloud account?'
                    . ' %default%'
            ],
            static::PROJECT => [
                'default' => static::getDefaultValue('MCLOUD_PROJECT', ''),
                'description' => 'Magento Cloud project to clone.',
                'question' => 'Please select project to clone'
            ],
            static::PROJECT_SKIP => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered project name. Do you want to continue? %default%'
            ],
            static::BRANCH => [
                'default' => static::getDefaultValue('MCLOUD_BRANCH', 'master'),
                'description' => 'Magento Cloud branch to clone from.',
                'question' => 'What branch do you want to clone from? %default%'
            ],
            static::BRANCH_SKIP => [
                'virtual' => true,
                'boolean' => true,
                'default' => true,
                'question' => 'You haven\'t entered branch name. Do you want to continue? %default%'
            ]
        ];
    }
}
