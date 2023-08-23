<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ContactCreate implements RequestInterface
{
    /**
     * @property MemberContact $contact
     */
    protected $contact = null;

    /**
     * @property boolean $doLogin
     */
    protected $doLogin = null;

    /**
     * @param MemberContact $contact
     * @return $this
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * @return MemberContact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param boolean $doLogin
     * @return $this
     */
    public function setDoLogin($doLogin)
    {
        $this->doLogin = $doLogin;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDoLogin()
    {
        return $this->doLogin;
    }
}

