<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Library;

/**
 * Class for communication with database
 */
class Db
{
    /**
     * @var \PDO[]
     */
    private static $connections;

    /**
     * Get connection to database
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbName
     * @return \PDO
     */
    public static function getConnection($host, $user, $password, $dbName)
    {
        $key = sprintf('%s/%s/%s/%s', $host, $user, $password, $dbName);

        if (empty(static::$connections[$key])) {
            static::$connections[$key] = new \PDO(sprintf('mysql:dbname=%s;host=%s', $dbName, $host), $user, $password);
        }

        return static::$connections[$key];
    }
}
