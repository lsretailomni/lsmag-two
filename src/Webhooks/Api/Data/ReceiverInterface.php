<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

interface ReceiverInterface
{
    public const RECEIVER_NAME = 'sender_name';
    public const RECEIVER_EMAIL = 'sender_email';
    public const RECEIVER_PHONE = 'sender_phone';
    public const RECEIVER_ROLE = 'sender_role';
    public const RECEIVER_ADDRESS = 'sender_address';

    /**
     * Set sender name
     *
     * @param string $receiverName
     * @return $this
     */
    public function setReceiverName($receiverName);

    /**
     * Get sender name
     *
     * @return string
     */
    public function getReceiverName();

    /**
     * Set sender email
     *
     * @param string $receiverEmail
     * @return $this
     */
    public function setReceiverEmail($receiverEmail);

    /**
     * Get sender email
     *
     * @return string
     */
    public function getReceiverEmail();

    /**
     * Set sender phone
     *
     * @param string $receiverPhone
     * @return $this
     */
    public function setReceiverPhone($receiverPhone);

    /**
     * Get sender phone
     *
     * @return string
     */
    public function getReceiverPhone();

    /**
     * Set sender role
     *
     * @param string $receiverRole
     * @return $this
     */
    public function setReceiverRole($receiverRole);

    /**
     * Get sender role
     *
     * @return string
     */
    public function getReceiverRole();

    /**
     * Set sender address
     *
     * @param mixed $receiverAddress
     * @return $this
     */
    public function setReceiverAddress($receiverAddress);

    /**
     * Get sender address
     *
     * @return mixed
     */
    public function getReceiverAddress();
}
