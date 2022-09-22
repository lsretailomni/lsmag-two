<?php

namespace Ls\Webhooks\Api\Data;

interface SenderInterface
{
    public const SENDER_NAME = 'sender_name';
    public const SENDER_EMAIL = 'sender_email';
    public const SENDER_PHONE = 'sender_phone';
    public const SENDER_ROLE = 'sender_role';
    public const SENDER_ADDRESS = 'sender_address';

    /**
     * Set sender name
     *
     * @param string $senderName
     * @return $this
     */
    public function setSenderName($senderName);

    /**
     * Get sender name
     *
     * @return string
     */
    public function getSenderName();

    /**
     * Set sender email
     *
     * @param string $senderEmail
     * @return $this
     */
    public function setSenderEmail($senderEmail);

    /**
     * Get sender email
     *
     * @return string
     */
    public function getSenderEmail();

    /**
     * Set sender phone
     *
     * @param string $senderPhone
     * @return $this
     */
    public function setSenderPhone($senderPhone);

    /**
     * Get sender phone
     *
     * @return string
     */
    public function getSenderPhone();

    /**
     * Set sender role
     *
     * @param string $senderRole
     * @return $this
     */
    public function setSenderRole($senderRole);

    /**
     * Get sender role
     *
     * @return string
     */
    public function getSenderRole();

    /**
     * Set sender address
     *
     * @param mixed $senderAddress
     * @return $this
     */
    public function setSenderAddress($senderAddress);

    /**
     * Get sender address
     *
     * @return mixed
     */
    public function getSenderAddress();
}
