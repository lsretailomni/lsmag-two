<?php
/**
 * Created by PhpStorm.
 * User: Abraham
 * Date: 6/14/2017
 * Time: 10:37 PM
 */

namespace Ls\Customer\Model\Customer\Attribute\Backend;

use Magento\Framework\Exception\LocalizedException;

class Password extends \Magento\Customer\Model\Customer\Attribute\Backend\Password
{
    /**
     * Min password length
     */
    const MIN_PASSWORD_LENGTH = 3;

    /**
     * Min password length from Store Config
     */
    const MIN_PASSWORD_LENGTH_PATH = 'customer/password/minimum_password_length';

    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->string = $string;
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeSave($object)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $min_password_length = $this->scopeConfig->getValue(self::MIN_PASSWORD_LENGTH_PATH, $storeScope) ?
            $this->scopeConfig->getValue(self::MIN_PASSWORD_LENGTH_PATH, $storeScope) :
            self::MIN_PASSWORD_LENGTH;
        $password = $object->getPassword();

        $length = $this->string->strlen($password);
        if ($length > 0) {
            if ($length < $min_password_length ) {
                throw new LocalizedException(
                    __('Please enter a password with at least %1 characters.', $min_password_length )
                );
            }

            if (trim($password) !== $password) {
                throw new LocalizedException(__('The password can not begin or end with a space.'));
            }

            $object->setPasswordHash($object->hashPassword($password));
        }
    }
}