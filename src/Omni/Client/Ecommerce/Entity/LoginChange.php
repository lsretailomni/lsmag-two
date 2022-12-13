<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class LoginChange implements RequestInterface
{

    /**
     * @property string $oldUserName
     */
    protected $oldUserName = null;

    /**
     * @property string $newUserName
     */
    protected $newUserName = null;

    /**
     * @property string $password
     */
    protected $password = null;

    /**
     * @param string $oldUserName
     * @return $this
     */
    public function setOldUserName($oldUserName)
    {
        $this->oldUserName = $oldUserName;
        return $this;
    }

    /**
     * @return string
     */
    public function getOldUserName()
    {
        return $this->oldUserName;
    }

    /**
     * @param string $newUserName
     * @return $this
     */
    public function setNewUserName($newUserName)
    {
        $this->newUserName = $newUserName;
        return $this;
    }

    /**
     * @return string
     */
    public function getNewUserName()
    {
        return $this->newUserName;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }


}

