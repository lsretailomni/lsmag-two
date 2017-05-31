<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command\Options;

/**
 * Abstract class for option containers
 */
abstract class AbstractOptions
{
    /**
     * Get option config
     *
     * @param string $name
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public static function get($name, $options = [])
    {
        if (!array_key_exists($name, static::getOptions())) {
            throw new \Exception(sprintf('Option "%s" does not exist!', $name));
        }

        return array_replace_recursive(static::getOptions()[$name], $options);
    }

    /**
     * Get all options of one type
     *
     * @return array
     */
    protected static abstract function getOptions();

    /**
     * Check environment variable for value and return it if exists, otherwise return $value
     *
     * @param string $envName
     * @param mixed $default
     *
     * @return mixed
     */
    public static function getDefaultValue($envName, $default)
    {
        $ret = $default;
        if (strlen(getenv($envName)) > 0) {
            $ret = (is_bool($default)) ? (boolean)getenv($envName) : getenv($envName);
        }
        return $ret;
    }
}
