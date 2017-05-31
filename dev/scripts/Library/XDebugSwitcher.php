<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Library;

/**
 * Class for switch xDebug for cli
 */
class XDebugSwitcher
{
    /**
     * xDebug .ini file path
     */
    const XDEBUG_CONFIG_FILE = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';

    /**
     * Temporary .ini file path
     */
    const TMP_CONFIG_FILE = '/tmp/xdebug.ini';

    /**
     * Switch Off xDebug
     *
     * @return void
     */
    public static function switchOff()
    {
        $command = sprintf(
            "sudo mv %s %s",
            static::XDEBUG_CONFIG_FILE,
            static::TMP_CONFIG_FILE
        );
        shell_exec($command);
    }

    /**
     * Switch On xDebug
     *
     * @return void
     */
    public static function switchOn()
    {
        $command = sprintf(
            "sudo mv %s %s",
            static::TMP_CONFIG_FILE,
            static::XDEBUG_CONFIG_FILE
        );
        shell_exec($command);
    }
}
